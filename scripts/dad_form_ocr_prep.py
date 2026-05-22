#!/usr/bin/env python3
"""
Daily Time Record (DTR) — OpenCV table grid detection + per-column crops for isolated OCR.
Columns: Guard Roaster, A.M. Time In/Out, P.M. Time In/Out (+ POST field above table).
"""
from __future__ import annotations

import json
import os
import re
import sys
from pathlib import Path
from typing import Any

import cv2
import numpy as np

try:
    from scipy import ndimage
except ImportError:
    ndimage = None  # type: ignore

# Golden Z DTR template (normalized) after perspective warp — col_name starts after # column
FALLBACK = {
    "post": (0.08, 0.20, 0.92, 0.28),
    "col_name": (0.14, 0.30, 0.38, 0.88),
    "col_am_in": (0.38, 0.30, 0.52, 0.88),
    "col_am_out": (0.52, 0.30, 0.66, 0.88),
    "col_pm_in": (0.66, 0.30, 0.80, 0.88),
    "col_pm_out": (0.80, 0.30, 0.96, 0.88),
}

# Narrow left column is row # (1–15); guard roster starts after it
ROW_NUM_COL_MAX_FRAC = 0.14


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
    w = int(max(np.linalg.norm(br - bl), np.linalg.norm(tr - tl)))
    h = int(max(np.linalg.norm(tr - br), np.linalg.norm(tl - bl)))
    dst = np.array([[0, 0], [w - 1, 0], [w - 1, h - 1], [0, h - 1]], dtype="float32")
    return cv2.warpPerspective(image, cv2.getPerspectiveTransform(rect, dst), (w, h))


def find_document_quad(bgr: np.ndarray) -> np.ndarray | None:
    gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
    edged = cv2.Canny(cv2.GaussianBlur(gray, (5, 5), 0), 50, 150)
    h, w = gray.shape[:2]
    for contour in sorted(cv2.findContours(edged, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)[0], key=cv2.contourArea, reverse=True)[:12]:
        peri = cv2.arcLength(contour, True)
        approx = cv2.approxPolyDP(contour, 0.02 * peri, True)
        if len(approx) != 4 or cv2.contourArea(contour) < h * w * 0.30:
            continue
        pts = approx.reshape(4, 2).astype("float32")
        sides = [np.linalg.norm(pts[i] - pts[(i + 1) % 4]) for i in range(4)]
        if max(sides) / max(min(sides), 1) < 3.5:
            return pts
    return None


def preprocess(bgr: np.ndarray) -> np.ndarray:
    gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
    h, w = gray.shape[:2]
    gray = cv2.resize(gray, (int(w * 2.5), int(h * 2.5)), interpolation=cv2.INTER_CUBIC)
    gray = cv2.fastNlMeansDenoising(gray, None, h=6, templateWindowSize=7, searchWindowSize=21)
    clahe = cv2.createCLAHE(clipLimit=2.2, tileGridSize=(8, 8))
    gray = clahe.apply(gray)
    blur = cv2.GaussianBlur(gray, (0, 0), 1.0)
    return cv2.addWeighted(gray, 1.3, blur, -0.3, 0)


def add_border(gray: np.ndarray, px: int = 28) -> np.ndarray:
    return cv2.copyMakeBorder(gray, px, px, px, px, cv2.BORDER_CONSTANT, value=255)


def table_structure(gray: np.ndarray) -> dict[str, Any]:
    """Detect table outer box + column x positions via grid lines."""
    h, w = gray.shape[:2]
    inv = 255 - gray
    _, binary = cv2.threshold(inv, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

    hk = cv2.getStructuringElement(cv2.MORPH_RECT, (max(40, w // 12), 1))
    vk = cv2.getStructuringElement(cv2.MORPH_RECT, (1, max(40, h // 15)))
    horiz = cv2.morphologyEx(binary, cv2.MORPH_OPEN, hk, iterations=2)
    vert = cv2.morphologyEx(binary, cv2.MORPH_OPEN, vk, iterations=2)

    row_strength = np.sum(horiz > 0, axis=1)
    col_strength = np.sum(vert > 0, axis=0)

    row_thresh = max(8, int(w * 0.12))
    col_thresh = max(8, int(h * 0.04))

    row_lines = [i for i, v in enumerate(row_strength) if v >= row_thresh]
    col_lines = [i for i, v in enumerate(col_strength) if v >= col_thresh]

    table_top = int(h * 0.28)
    table_bottom = int(h * 0.90)
    if len(row_lines) >= 4:
        clusters = cluster_positions(row_lines, gap=8)
        mid = [c for c in clusters if h * 0.22 < int(np.mean(c)) < h * 0.92]
        big = [c for c in mid if len(c) >= 2 or (max(c) - min(c)) > h * 0.01]
        if len(big) >= 2:
            table_top = min(int(np.mean(c)) for c in big)
            table_bottom = max(int(np.mean(c)) for c in big)
        elif len(mid) >= 2:
            table_top = min(int(np.mean(c)) for c in mid)
            table_bottom = max(int(np.mean(c)) for c in mid)

    table_top = max(int(h * 0.24), table_top)
    table_bottom = min(int(h * 0.92), max(table_bottom, table_top + int(h * 0.35)))

    col_x: list[int] = []
    if len(col_lines) >= 5:
        clusters = cluster_positions(col_lines, gap=10)
        col_x = [int(np.mean(c)) for c in clusters if len(c) >= 1]
        col_x = sorted(set(col_x))

    if len(col_x) < 6:
        col_x = [
            int(w * 0.08),
            int(w * 0.14),
            int(w * 0.38),
            int(w * 0.52),
            int(w * 0.66),
            int(w * 0.80),
            int(w * 0.96),
        ]

    col_x = sorted(set(col_x))
    while len(col_x) < 7:
        col_x.append(int(w * (0.08 + 0.14 * len(col_x))))

    regions = dad_table_column_regions(col_x, w, table_top, table_bottom, h)
    post_bottom = max(int(h * 0.22), min(table_top - int(h * 0.015), int(h * 0.30)))
    if post_bottom <= int(h * 0.14):
        post_bottom = int(h * 0.26)

    return {
        "post": (0.08, 0.20, 0.92, post_bottom / h),
        **regions,
        "table_top": table_top,
        "table_bottom": table_bottom,
        "col_x": col_x,
    }


def dad_table_column_regions(
    col_x: list[int], w: int, table_top: int, table_bottom: int, h: int
) -> dict[str, tuple[float, float, float, float]]:
    """
    Map vertical grid lines to DTR columns, skipping the # (row number) column on the left.
    Columns: Guard Roaster | A.M. in | A.M. out | P.M. in | P.M. out
    """
    xs = sorted(col_x)
    start = 0
    if len(xs) >= 7:
        start = 1
    elif len(xs) >= 2 and (xs[1] - xs[0]) / max(w, 1) <= ROW_NUM_COL_MAX_FRAC:
        start = 1

    need = start + 6
    while len(xs) < need:
        xs.append(int(w * 0.96))

    y1, y2 = table_top / h, table_bottom / h

    def box(i: int) -> tuple[float, float, float, float]:
        return (xs[i] / w, y1, xs[i + 1] / w, y2)

    i = start
    return {
        "col_name": box(i),
        "col_am_in": box(i + 1),
        "col_am_out": box(i + 2),
        "col_pm_in": box(i + 3),
        "col_pm_out": box(i + 4),
    }


def cluster_positions(positions: list[int], gap: int = 8) -> list[list[int]]:
    if not positions:
        return []
    positions = sorted(positions)
    clusters: list[list[int]] = [[positions[0]]]
    for p in positions[1:]:
        if p - clusters[-1][-1] <= gap:
            clusters[-1].append(p)
        else:
            clusters.append([p])
    return clusters


def is_row_number_line(line: str) -> bool:
    s = line.strip()
    if s == "" or s == "#":
        return True
    if re.match(r"^(?:#?\s*)?\d{1,2}\s*\.?\s*$", s):
        return True
    return False


def skip_header_lines(lines: list[str]) -> list[str]:
    out: list[str] = []
    for line in lines:
        if is_row_number_line(line):
            continue
        u = line.upper()
        if any(
            k in u
            for k in (
                "GUARD",
                "ROASTER",
                "ROSTER",
                "TIME IN",
                "TIME OUT",
                "A.M",
                "P.M",
                "DAILY",
                "RECORD",
                "CONFIRMATION",
                "HEAD GUARD",
            )
        ):
            continue
        if line.strip() == "":
            continue
        out.append(line)
    return out


def crop_norm(gray: np.ndarray, box: tuple[float, float, float, float]) -> np.ndarray:
    h, w = gray.shape[:2]
    x1, y1, x2, y2 = box
    px1, py1 = int(x1 * w), int(y1 * h)
    px2, py2 = int(x2 * w), int(y2 * h)
    if px2 <= px1 + 8 or py2 <= py1 + 8:
        return gray[0:0, 0:0]
    roi = gray[py1:py2, px1:px2]
    up = cv2.resize(roi, None, fx=1.8, fy=1.8, interpolation=cv2.INTER_CUBIC)
    return add_border(up)


def main() -> int:
    if len(sys.argv) < 3:
        print(json.dumps({"ok": False, "error": "Usage: dad_form_ocr_prep.py <input> <output_dir>"}))
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

    if os.environ.get("DAD_OCR_SKIP_WARP", "").lower() not in ("1", "true", "yes"):
        quad = find_document_quad(bgr)
        if quad is not None:
            warped = four_point_transform(bgr, quad)
            if warped.shape[0] > 80 and warped.shape[1] > 80:
                bgr = warped

    gray = preprocess(bgr)
    layout = table_structure(gray)
    if "col_name" not in layout:
        layout = dict(FALLBACK)
        layout["layout"] = "fallback"
    else:
        layout["layout"] = "table_grid"

    full_path = output_dir / "full_preprocessed.jpg"
    cv2.imwrite(str(full_path), add_border(gray), [int(cv2.IMWRITE_JPEG_QUALITY), 94])

    region_keys = ("post", "col_name", "col_am_in", "col_am_out", "col_pm_in", "col_pm_out")
    paths: dict[str, str] = {}
    for key in region_keys:
        box = layout.get(key)
        if not isinstance(box, tuple) or len(box) != 4:
            continue
        crop = crop_norm(gray, box)
        if crop.size == 0:
            continue
        out = output_dir / f"{key}.jpg"
        cv2.imwrite(str(out), crop, [int(cv2.IMWRITE_JPEG_QUALITY), 96])
        paths[key] = str(out)

    if "col_name" not in paths:
        print(json.dumps({"ok": False, "error": "Could not crop attendance columns", "layout": layout.get("layout")}))
        return 1

    boxes_out = {k: list(layout[k]) for k in region_keys if isinstance(layout.get(k), tuple)}

    print(
        json.dumps(
            {
                "ok": True,
                "engine": "opencv+scipy" if ndimage is not None else "opencv",
                "layout_mode": layout.get("layout", "table_grid"),
                "full": str(full_path),
                "regions": paths,
                "boxes": boxes_out,
                "width": int(gray.shape[1]),
                "height": int(gray.shape[0]),
            }
        )
    )
    return 0


if __name__ == "__main__":
    sys.exit(main())
