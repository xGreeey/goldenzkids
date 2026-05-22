#!/usr/bin/env python3
"""
Incident report scan — OpenCV + NumPy + SciPy layout detection and per-field crops.
Detects stacked mobile forms (boxes on top of each other) OR side-by-side Golden Z template.
"""
from __future__ import annotations

import json
import os
import sys
from pathlib import Path
from typing import Any

import cv2
import numpy as np

try:
    from scipy import ndimage
except ImportError:
    ndimage = None  # type: ignore

FALLBACK_STACKED = {
    "name_of_guard": (0.05, 0.02, 0.95, 0.12),
    "incident_description": (0.04, 0.14, 0.96, 0.48),
    "action_taken": (0.04, 0.50, 0.96, 0.88),
}

FALLBACK_COLUMNS = {
    "name_of_guard": (0.09, 0.142, 0.56, 0.198),
    "incident_description": (0.04, 0.365, 0.465, 0.855),
    "action_taken": (0.535, 0.365, 0.96, 0.855),
}


def order_points(pts: np.ndarray) -> np.ndarray:
    rect = np.zeros((4, 2), dtype="float32")
    s = pts.sum(axis=1)
    rect[0] = pts[np.argmin(s)]
    rect[2] = pts[np.argmax(s)]
    diff = np.diff(pts, axis=1)
    rect[1] = pts[np.argmin(diff)]
    rect[3] = pts[np.argmax(diff)]
    return rect


def four_point_transform(image: np.ndarray, pts: np.ndarray) -> np.ndarray:
    rect = order_points(pts)
    (tl, tr, br, bl) = rect
    width_a = np.linalg.norm(br - bl)
    width_b = np.linalg.norm(tr - tl)
    max_width = int(max(width_a, width_b))
    height_a = np.linalg.norm(tr - br)
    height_b = np.linalg.norm(tl - bl)
    max_height = int(max(height_a, height_b))
    dst = np.array(
        [[0, 0], [max_width - 1, 0], [max_width - 1, max_height - 1], [0, max_height - 1]],
        dtype="float32",
    )
    return cv2.warpPerspective(image, cv2.getPerspectiveTransform(rect, dst), (max_width, max_height))


def find_document_quad(image: np.ndarray) -> np.ndarray | None:
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    edged = cv2.Canny(cv2.GaussianBlur(gray, (5, 5), 0), 50, 150)
    contours, _ = cv2.findContours(edged, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
    h, w = gray.shape[:2]
    for contour in sorted(contours, key=cv2.contourArea, reverse=True)[:12]:
        peri = cv2.arcLength(contour, True)
        approx = cv2.approxPolyDP(contour, 0.02 * peri, True)
        area = cv2.contourArea(contour)
        if len(approx) != 4 or area < h * w * 0.35 or area > h * w * 0.97:
            continue
        pts = approx.reshape(4, 2).astype("float32")
        side_a = np.linalg.norm(pts[0] - pts[1])
        side_b = np.linalg.norm(pts[1] - pts[2])
        aspect = max(side_a, side_b) / max(min(side_a, side_b), 1)
        if 0.5 <= aspect <= 1.4:
            return pts
    return None


def preprocess_color(bgr: np.ndarray) -> np.ndarray:
    gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
    h, w = gray.shape[:2]
    scale = 2.5
    gray = cv2.resize(gray, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_CUBIC)
    gray = cv2.fastNlMeansDenoising(gray, None, h=6, templateWindowSize=7, searchWindowSize=21)
    clahe = cv2.createCLAHE(clipLimit=2.2, tileGridSize=(8, 8))
    gray = clahe.apply(gray)
    blur = cv2.GaussianBlur(gray, (0, 0), 1.0)
    return cv2.addWeighted(gray, 1.3, blur, -0.3, 0)


def add_white_border(gray: np.ndarray, px: int = 32) -> np.ndarray:
    return cv2.copyMakeBorder(gray, px, px, px, px, cv2.BORDER_CONSTANT, value=255)


def body_vertical_bounds(roi: np.ndarray) -> tuple[int, int]:
    """Handwriting rows only — skip header bar and footer (CONFIRMATION BY)."""
    rh, _rw = roi.shape[:2]
    ink = 255 - roi
    row_strength = np.mean(ink, axis=1)
    peak = float(np.max(row_strength)) if row_strength.size else 0.0
    if peak < 5:
        return int(rh * 0.14), rh - 4

    header_limit = int(rh * 0.30)
    threshold = max(5.0, peak * 0.14)
    body_start = int(rh * 0.14)

    for i in range(int(rh * 0.08), header_limit):
        if row_strength[i] >= threshold * 1.4:
            body_start = min(i + 2, int(rh * 0.28))
            break

    for i in range(body_start, min(header_limit, body_start + 8)):
        if row_strength[i] >= threshold:
            body_start = min(i + 1, int(rh * 0.28))

    body_end = rh - 3
    footer_scan = int(rh * 0.72)
    for i in range(rh - 4, footer_scan, -1):
        if row_strength[i] >= threshold and i > rh * 0.55:
            body_end = min(rh - 3, i + int(rh * 0.06))
            break

    if body_end - body_start < int(rh * 0.20):
        body_start = int(rh * 0.16)
        body_end = rh - int(rh * 0.08)

    return body_start, body_end


def crop_handwriting_in_rect(gray: np.ndarray, x: int, y: int, w: int, h: int) -> np.ndarray:
    H, W = gray.shape[:2]
    x1, y1 = max(0, x), max(0, y)
    x2, y2 = min(W, x + w), min(H, y + h)
    if x2 <= x1 + 16 or y2 <= y1 + 16:
        return gray[0:0, 0:0]

    roi = gray[y1:y2, x1:x2]
    top, bottom = body_vertical_bounds(roi)
    body = roi[top:bottom, :]
    if body.shape[0] < 20:
        body = roi[int(roi.shape[0] * 0.15) : int(roi.shape[0] * 0.92), :]

    upscale = cv2.resize(body, None, fx=1.5, fy=1.5, interpolation=cv2.INTER_CUBIC)
    return add_white_border(upscale)


def find_form_field_rectangles(gray: np.ndarray) -> list[tuple[int, int, int, int, float]]:
    H, W = gray.shape[:2]
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    edges = cv2.Canny(blurred, 25, 100)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (5, 5))
    closed = cv2.dilate(edges, kernel, iterations=2)
    closed = cv2.morphologyEx(closed, cv2.MORPH_CLOSE, cv2.getStructuringElement(cv2.MORPH_RECT, (9, 9)), iterations=2)

    contours, _ = cv2.findContours(closed, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    rects: list[tuple[int, int, int, int, float]] = []

    for contour in contours:
        x, y, w, h = cv2.boundingRect(contour)
        area = float(w * h)
        if area < H * W * 0.03 or area > H * W * 0.65:
            continue
        if w < W * 0.38 or h < H * 0.07 or h > H * 0.52:
            continue
        rects.append((x, y, w, h, area))

    rects.sort(key=lambda r: r[4], reverse=True)
    merged: list[tuple[int, int, int, int, float]] = []
    for rect in rects:
        x, y, w, h, area = rect
        cx, cy = x + w / 2, y + h / 2
        if any(abs(cx - (mx + mw / 2)) < W * 0.07 and abs(cy - (my + mh / 2)) < H * 0.05 for mx, my, mw, mh, _ in merged):
            continue
        merged.append(rect)
        if len(merged) >= 5:
            break

    merged.sort(key=lambda r: r[1])
    return merged


def norm_box(rect: tuple[int, int, int, int, float], h: int, w: int) -> tuple[float, float, float, float]:
    x, y, bw, bh, _ = rect
    mx = max(3, int(bw * 0.02))
    my = max(3, int(bh * 0.02))
    return (
        (x + mx) / w,
        (y + my) / h,
        (x + bw - mx) / w,
        (y + bh - my) / h,
    )


def pick_two_stacked_boxes(
    rects: list[tuple[int, int, int, int, float]], h: int, w: int
) -> tuple[tuple[int, int, int, int, float], tuple[int, int, int, int, float]] | None:
    qualified = [r for r in rects if r[2] >= w * 0.42 and r[3] >= h * 0.07 and r[3] <= h * 0.50]
    if len(qualified) < 2:
        return None

    qualified.sort(key=lambda r: r[1])
    best_pair = None
    best_score = -1.0

    for i in range(len(qualified)):
        for j in range(i + 1, len(qualified)):
            top, bottom = qualified[i], qualified[j]
            tcy = top[1] + top[3] / 2
            bcy = bottom[1] + bottom[3] / 2
            if bcy <= tcy + top[3] * 0.35:
                continue
            x_align = abs((top[0] + top[2] / 2) - (bottom[0] + bottom[2] / 2)) < w * 0.12
            gap = bottom[1] - (top[1] + top[3])
            score = top[4] + bottom[4] + (20000 if x_align else 0) + gap
            if score > best_score:
                best_score = score
                best_pair = (top, bottom)

    return best_pair


def layout_from_stacked_pair(
    top: tuple[int, int, int, int, float],
    bottom: tuple[int, int, int, int, float],
    h: int,
    w: int,
) -> dict[str, Any]:
    first_y = top[1]
    return {
        "layout": "stacked",
        "name_of_guard": (0.05, 0.02, 0.95, max(0.08, (first_y - int(0.015 * h)) / h)),
        "incident_description": norm_box(top, h, w),
        "action_taken": norm_box(bottom, h, w),
    }


def detect_stacked_by_projection(gray: np.ndarray) -> dict[str, Any] | None:
    """Two horizontal handwriting bands (mobile stacked form)."""
    h, w = gray.shape[:2]
    roi = gray[int(0.18 * h) : int(0.90 * h), int(0.04 * w) : int(0.96 * w)]
    if roi.size == 0:
        return None

    row_ink = np.mean(255 - roi, axis=1)
    peak = float(np.max(row_ink))
    if peak < 6:
        return None

    threshold = max(6.0, peak * 0.11)
    active = row_ink >= threshold

    runs: list[tuple[int, int]] = []
    start: int | None = None
    for i, on in enumerate(active):
        if on and start is None:
            start = i
        elif not on and start is not None:
            if i - start >= 8:
                runs.append((start, i))
            start = None
    if start is not None and len(active) - start >= 8:
        runs.append((start, len(active)))

    if len(runs) < 2:
        return None

    runs.sort(key=lambda r: r[1] - r[0], reverse=True)
    runs = sorted(runs[:3], key=lambda r: r[0])
    if len(runs) < 2:
        return None

    top_run, bottom_run = runs[0], runs[-1]
    y0 = int(0.18 * h) + top_run[0]
    y1 = int(0.18 * h) + top_run[1]
    y2 = int(0.18 * h) + bottom_run[0]
    y3 = int(0.18 * h) + bottom_run[1]

    if y2 <= y1 + int(0.04 * h):
        return None

    return {
        "layout": "stacked_projection",
        "name_of_guard": (0.05, 0.02, 0.95, max(0.08, (int(0.18 * h) - 5) / h)),
        "incident_description": (0.04, y0 / h, 0.96, y1 / h),
        "action_taken": (0.04, y2 / h, 0.96, min(0.90, y3 / h)),
    }


def detect_layout_columns(gray: np.ndarray) -> dict[str, Any]:
    h, w = gray.shape[:2]
    ink = cv2.GaussianBlur(255 - gray, (5, 5), 0)
    body_top = int(0.34 * h)
    table = ink[body_top : int(0.88 * h), int(0.03 * w) : int(0.97 * w)]
    if table.size == 0:
        out = dict(FALLBACK_COLUMNS)
        out["layout"] = "columns_fallback"
        return out

    col_strength = np.mean(table, axis=0)
    mid_lo, mid_hi = int(0.38 * len(col_strength)), int(0.62 * len(col_strength))
    gutter = mid_lo + int(np.argmin(col_strength[mid_lo:mid_hi]))
    split_x = int(0.03 * w) + gutter
    gutter_px = max(10, int(0.025 * w))

    return {
        "layout": "columns_projection",
        "name_of_guard": (0.09, 0.142, 0.56, max(0.18, (body_top - int(0.02 * h)) / h)),
        "incident_description": (0.04, body_top / h + 0.01, (split_x - gutter_px) / w, 0.86),
        "action_taken": ((split_x + gutter_px) / w, body_top / h + 0.01, 0.96, 0.86),
    }


def detect_layout(gray: np.ndarray) -> dict[str, Any]:
    h, w = gray.shape[:2]
    portrait = h > w * 1.02

    rects = find_form_field_rectangles(gray)
    pair = pick_two_stacked_boxes(rects, h, w)
    if pair is not None:
        return layout_from_stacked_pair(pair[0], pair[1], h, w)

    if len(rects) >= 2:
        rects_by_area = sorted(rects, key=lambda r: r[4], reverse=True)[:2]
        rects_by_area.sort(key=lambda r: r[1])
        return layout_from_stacked_pair(rects_by_area[0], rects_by_area[1], h, w)

    stacked_proj = detect_stacked_by_projection(gray)
    if stacked_proj is not None:
        return stacked_proj

    if portrait:
        out = dict(FALLBACK_STACKED)
        out["layout"] = "stacked_fallback"
        return out

    cols = detect_layout_columns(gray)
    return cols


def save_region_crops(gray: np.ndarray, layout: dict[str, Any], output_dir: Path) -> dict[str, str]:
    h, w = gray.shape[:2]
    paths: dict[str, str] = {}
    for key in ("name_of_guard", "incident_description", "action_taken"):
        box = layout.get(key)
        if not isinstance(box, (list, tuple)) or len(box) != 4:
            continue
        x1, y1, x2, y2 = (float(box[0]), float(box[1]), float(box[2]), float(box[3]))
        crop = crop_handwriting_in_rect(
            gray,
            int(x1 * w),
            int(y1 * h),
            int((x2 - x1) * w),
            int((y2 - y1) * h),
        )
        if crop.size == 0:
            continue
        out = output_dir / f"{key}.jpg"
        cv2.imwrite(str(out), crop, [int(cv2.IMWRITE_JPEG_QUALITY), 96])
        paths[key] = str(out)
    return paths


def main() -> int:
    if len(sys.argv) < 3:
        print(json.dumps({"ok": False, "error": "Usage: incident_form_ocr_prep.py <input_image> <output_dir>"}))
        return 1

    input_path = Path(sys.argv[1]).resolve()
    output_dir = Path(sys.argv[2]).resolve()
    if not input_path.is_file():
        print(json.dumps({"ok": False, "error": f"Input not found: {input_path}"}))
        return 1
    output_dir.mkdir(parents=True, exist_ok=True)

    bgr = cv2.imread(str(input_path))
    if bgr is None:
        print(json.dumps({"ok": False, "error": "Could not read image"}))
        return 1

    if os.environ.get("INCIDENT_OCR_SKIP_WARP", "").lower() not in ("1", "true", "yes"):
        quad = find_document_quad(bgr)
        if quad is not None:
            warped = four_point_transform(bgr, quad)
            if warped.shape[0] > 100 and warped.shape[1] > 100:
                bgr = warped

    gray = preprocess_color(bgr)
    layout = detect_layout(gray)

    full_path = output_dir / "full_preprocessed.jpg"
    cv2.imwrite(str(full_path), add_white_border(gray), [int(cv2.IMWRITE_JPEG_QUALITY), 92])

    region_paths = save_region_crops(gray, layout, output_dir)
    if "incident_description" not in region_paths or "action_taken" not in region_paths:
        print(
            json.dumps(
                {
                    "ok": False,
                    "error": "Could not crop description/action regions",
                    "layout": layout.get("layout"),
                }
            )
        )
        return 1

    boxes_out = {
        k: list(v)
        for k, v in layout.items()
        if k in ("name_of_guard", "incident_description", "action_taken") and isinstance(v, tuple)
    }

    print(
        json.dumps(
            {
                "ok": True,
                "engine": "opencv+scipy" if ndimage is not None else "opencv",
                "layout_mode": layout.get("layout", "unknown"),
                "full": str(full_path),
                "regions": region_paths,
                "boxes": boxes_out,
                "width": int(gray.shape[1]),
                "height": int(gray.shape[0]),
            }
        )
    )
    return 0


if __name__ == "__main__":
    sys.exit(main())
