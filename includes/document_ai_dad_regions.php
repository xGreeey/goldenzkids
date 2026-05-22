<?php
declare(strict_types=1);

/**
 * Region-based DTR (Daily Time Record) OCR: POST + per-column table crops → isolated Document AI.
 */

require_once __DIR__ . '/document_ai_rest.php';

use Google\Cloud\DocumentAI\V1\Document;

function document_ai_is_dad_report_type(string $reportType): bool
{
    return in_array(trim($reportType), ['Daily Time Record', 'Daily Attendance Document'], true);
}

/**
 * @return list<string>
 */
function document_ai_dad_ocr_region_keys(): array
{
    return ['post', 'col_name', 'col_am_in', 'col_am_out', 'col_pm_in', 'col_pm_out'];
}

/** @return array<string, array{x_min: float, y_min: float, x_max: float, y_max: float}> */
function document_ai_dad_region_boxes_gd(): array
{
    return [
        'post' => ['x_min' => 0.08, 'y_min' => 0.20, 'x_max' => 0.92, 'y_max' => 0.28],
        'col_name' => ['x_min' => 0.14, 'y_min' => 0.30, 'x_max' => 0.38, 'y_max' => 0.88],
        'col_am_in' => ['x_min' => 0.38, 'y_min' => 0.30, 'x_max' => 0.52, 'y_max' => 0.88],
        'col_am_out' => ['x_min' => 0.52, 'y_min' => 0.30, 'x_max' => 0.66, 'y_max' => 0.88],
        'col_pm_in' => ['x_min' => 0.66, 'y_min' => 0.30, 'x_max' => 0.80, 'y_max' => 0.88],
        'col_pm_out' => ['x_min' => 0.80, 'y_min' => 0.30, 'x_max' => 0.96, 'y_max' => 0.88],
    ];
}

function document_ai_dad_python_script_path(): string
{
    return APP_ROOT . '/scripts/dad_form_ocr_prep.py';
}

/**
 * @return list<string>|null
 */
function document_ai_dad_resolve_python_argv(): ?array
{
    $env = trim((string) ($_ENV['DAD_OCR_PYTHON'] ?? $_ENV['INCIDENT_OCR_PYTHON'] ?? ''));
    if ($env !== '') {
        return preg_split('/\s+/u', $env, -1, PREG_SPLIT_NO_EMPTY) ?: null;
    }

    $candidates = [
        ['python'],
        ['python3'],
        ['py', '-3'],
    ];

    foreach ($candidates as $argv) {
        $bin = $argv[0];
        $check = PHP_OS_FAMILY === 'Windows'
            ? 'where ' . escapeshellarg($bin) . ' 2>nul'
            : 'command -v ' . escapeshellarg($bin) . ' 2>/dev/null';
        @exec($check, $out, $code);
        if ($code === 0 && $out !== []) {
            return $argv;
        }
    }

    return null;
}

/**
 * @return array{ok: bool, error?: string, regions?: array<string, string>, temp_dir?: string, engine?: string, layout_mode?: string, full_preprocessed?: string, boxes?: array<string, mixed>}
 */
function document_ai_dad_prepare_regions_opencv(string $absolutePath): array
{
    $pythonArgv = document_ai_dad_resolve_python_argv();
    $script = document_ai_dad_python_script_path();
    if ($pythonArgv === null || !is_file($script)) {
        return ['ok' => false, 'error' => 'OpenCV DTR preprocessor unavailable'];
    }

    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gk_dad_' . bin2hex(random_bytes(8));
    if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
        return ['ok' => false, 'error' => 'Could not create temp directory'];
    }

    $cmdParts = array_merge($pythonArgv, [$script, $absolutePath, $tempDir]);
    $cmd = '';
    foreach ($cmdParts as $part) {
        $cmd .= ($cmd === '' ? '' : ' ') . escapeshellarg($part);
    }
    $cmd .= ' 2>&1';

    $output = [];
    $exitCode = 1;
    @exec($cmd, $output, $exitCode);
    $jsonLine = '';
    foreach (array_reverse($output) as $line) {
        $line = trim($line);
        if ($line !== '' && str_starts_with($line, '{')) {
            $jsonLine = $line;
            break;
        }
    }
    if ($jsonLine === '' && $output !== []) {
        $jsonLine = trim((string) end($output));
    }

    $decoded = json_decode($jsonLine, true);
    if (!is_array($decoded) || !($decoded['ok'] ?? false)) {
        document_ai_dad_remove_dir($tempDir);

        return [
            'ok' => false,
            'error' => (string) ($decoded['error'] ?? 'DTR OpenCV preprocessing failed'),
        ];
    }

    $regions = is_array($decoded['regions'] ?? null) ? $decoded['regions'] : [];
    $normalized = [];
    foreach (document_ai_dad_ocr_region_keys() as $key) {
        $path = trim((string) ($regions[$key] ?? ''));
        if ($path !== '' && is_file($path)) {
            $normalized[$key] = $path;
        }
    }

    if (!isset($normalized['col_name'])) {
        document_ai_dad_remove_dir($tempDir);

        return ['ok' => false, 'error' => 'No DTR column crops produced'];
    }

    return [
        'ok' => true,
        'regions' => $normalized,
        'temp_dir' => $tempDir,
        'engine' => (string) ($decoded['engine'] ?? 'opencv'),
        'layout_mode' => (string) ($decoded['layout_mode'] ?? 'table_grid'),
        'boxes' => is_array($decoded['boxes'] ?? null) ? $decoded['boxes'] : [],
        'full_preprocessed' => (string) ($decoded['full'] ?? ''),
    ];
}

/**
 * @return array{ok: bool, error?: string, regions?: array<string, string>, temp_dir?: string, engine?: string}
 */
function document_ai_dad_prepare_regions_gd(string $absolutePath): array
{
    if (!extension_loaded('gd')) {
        return ['ok' => false, 'error' => 'GD extension not available'];
    }

    $mime = document_ai_mime_for_path($absolutePath);
    $image = match ($mime) {
        'image/png' => @imagecreatefrompng($absolutePath),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
        'image/gif' => @imagecreatefromgif($absolutePath),
        default => @imagecreatefromjpeg($absolutePath),
    };
    if ($image === false) {
        return ['ok' => false, 'error' => 'Could not load image'];
    }

    if (function_exists('imagepalettetotruecolor')) {
        imagepalettetotruecolor($image);
    }

    $srcW = imagesx($image);
    $srcH = imagesy($image);
    $scale = 2;
    $full = imagecreatetruecolor($srcW * $scale, $srcH * $scale);
    if ($full === false) {
        imagedestroy($image);

        return ['ok' => false, 'error' => 'Could not allocate image buffer'];
    }

    imagecopyresampled($full, $image, 0, 0, 0, 0, $srcW * $scale, $srcH * $scale, $srcW, $srcH);
    imagedestroy($image);
    imagefilter($full, IMG_FILTER_GRAYSCALE);
    imagefilter($full, IMG_FILTER_CONTRAST, -24);
    imagefilter($full, IMG_FILTER_BRIGHTNESS, 6);

    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gk_dad_' . bin2hex(random_bytes(8));
    if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
        imagedestroy($full);

        return ['ok' => false, 'error' => 'Could not create temp directory'];
    }

    $fw = imagesx($full);
    $fh = imagesy($full);
    $regions = [];

    foreach (document_ai_dad_ocr_region_keys() as $key) {
        $box = document_ai_dad_region_boxes_gd()[$key] ?? null;
        if ($box === null) {
            continue;
        }
        $x1 = max(0, (int) floor($box['x_min'] * $fw));
        $y1 = max(0, (int) floor($box['y_min'] * $fh));
        $x2 = min($fw, (int) ceil($box['x_max'] * $fw));
        $y2 = min($fh, (int) ceil($box['y_max'] * $fh));
        $cw = $x2 - $x1;
        $ch = $y2 - $y1;
        if ($cw < 12 || $ch < 12) {
            continue;
        }

        $crop = imagecrop($full, ['x' => $x1, 'y' => $y1, 'width' => $cw, 'height' => $ch]);
        if ($crop === false) {
            continue;
        }

        $out = $tempDir . DIRECTORY_SEPARATOR . $key . '.jpg';
        if (imagejpeg($crop, $out, 92)) {
            $regions[$key] = $out;
        }
        imagedestroy($crop);
    }

    imagedestroy($full);

    if (!isset($regions['col_name'])) {
        document_ai_dad_remove_dir($tempDir);

        return ['ok' => false, 'error' => 'No DTR region crops produced (GD)'];
    }

    return [
        'ok' => true,
        'regions' => $regions,
        'temp_dir' => $tempDir,
        'engine' => 'gd',
        'layout_mode' => 'gd_fallback',
    ];
}

/**
 * @return array{ok: bool, error?: string, regions?: array<string, string>, temp_dir?: string, engine?: string, layout_mode?: string, full_preprocessed?: string, boxes?: array<string, mixed>}
 */
function document_ai_dad_prepare_regions(string $absolutePath): array
{
    $opencv = document_ai_dad_prepare_regions_opencv($absolutePath);
    if ($opencv['ok']) {
        return $opencv;
    }

    return document_ai_dad_prepare_regions_gd($absolutePath);
}

function document_ai_dad_remove_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_file($path)) {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function document_ai_dad_is_row_number_line(string $line): bool
{
    $line = trim($line);
    if ($line === '' || $line === '#') {
        return true;
    }

    return preg_match('/^(?:#?\s*)?\d{1,2}\s*\.?\s*$/u', $line) === 1;
}

/**
 * @return list<string>
 */
function document_ai_dad_column_lines(string $text, string $columnKind = 'name'): array
{
    $text = preg_replace("/\r\n?/", "\n", trim($text)) ?? '';
    if ($text === '') {
        return [];
    }

    $lines = [];
    foreach (preg_split('/\n+/u', $text) ?: [] as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        if (document_ai_dad_is_row_number_line($line)) {
            continue;
        }
        $upper = strtoupper($line);
        if (preg_match('/\b(?:GUARD|ROASTER|ROSTER|TIME\s*IN|TIME\s*OUT|A\.?\s*M|P\.?\s*M|DAILY|RECORD|CONFIRMATION|HEAD\s*GUARD|GOLDEN)\b/u', $upper) === 1) {
            continue;
        }
        if ($columnKind === 'name') {
            if (document_ai_dad_is_time_text($line)) {
                continue;
            }
        } elseif ($columnKind === 'time') {
            if (!document_ai_dad_is_time_text($line) && preg_match('/\p{L}{3,}/u', $line) === 1) {
                continue;
            }
        }
        $lines[] = $line;
    }

    return $lines;
}

function document_ai_dad_row_is_redacted(string $name, string $amIn, string $amOut, string $pmIn, string $pmOut): bool
{
    if ($name === '') {
        return $amIn === '' && $amOut === '' && $pmIn === '' && $pmOut === '';
    }

    $letters = preg_match_all('/\p{L}/u', $name) ?: 0;
    $len = max(1, strlen($name));
    if ($letters < 2 || ($letters / $len) < 0.35) {
        return true;
    }

    if (preg_match('/^[^a-zA-Z0-9\s]{4,}$/u', $name) === 1) {
        return true;
    }

    return false;
}

/**
 * @param array<string, string> $regionTexts
 * @return list<array{name: string, am_time_in: string, am_time_out: string, pm_time_in: string, pm_time_out: string, time_in: string, time_out: string}>
 */
function document_ai_dad_rows_from_region_columns(array $regionTexts): array
{
    $cols = [
        'name' => document_ai_dad_column_lines($regionTexts['col_name'] ?? '', 'name'),
        'am_in' => document_ai_dad_column_lines($regionTexts['col_am_in'] ?? '', 'time'),
        'am_out' => document_ai_dad_column_lines($regionTexts['col_am_out'] ?? '', 'time'),
        'pm_in' => document_ai_dad_column_lines($regionTexts['col_pm_in'] ?? '', 'time'),
        'pm_out' => document_ai_dad_column_lines($regionTexts['col_pm_out'] ?? '', 'time'),
    ];

    $max = max(
        count($cols['name']),
        count($cols['am_in']),
        count($cols['am_out']),
        count($cols['pm_in']),
        count($cols['pm_out'])
    );

    $rows = [];
    for ($i = 0; $i < $max; $i++) {
        $name = document_ai_sanitize_dad_name((string) ($cols['name'][$i] ?? ''));
        $amIn = document_ai_sanitize_dad_time((string) ($cols['am_in'][$i] ?? ''));
        $amOut = document_ai_sanitize_dad_time((string) ($cols['am_out'][$i] ?? ''));
        $pmIn = document_ai_sanitize_dad_time((string) ($cols['pm_in'][$i] ?? ''));
        $pmOut = document_ai_sanitize_dad_time((string) ($cols['pm_out'][$i] ?? ''));

        if (document_ai_dad_row_is_redacted($name, $amIn, $amOut, $pmIn, $pmOut)) {
            continue;
        }

        $rows[] = document_ai_dad_normalize_attendance_row([
            'name' => $name,
            'am_time_in' => $amIn,
            'am_time_out' => $amOut,
            'pm_time_in' => $pmIn,
            'pm_time_out' => $pmOut,
        ]);
    }

    return document_ai_dad_sanitize_rows($rows);
}

/**
 * @param array<string, string> $regionTexts
 * @return array<string, mixed>
 */
function document_ai_dad_structured_from_regions(array $regionTexts): array
{
    $post = document_ai_sanitize_dad_post(trim($regionTexts['post'] ?? ''));
    $rows = document_ai_dad_rows_from_region_columns($regionTexts);

    $structured = [
        'template' => 'daily_attendance',
        'post' => $post,
        'dates' => [],
        'attendance_rows' => $rows,
        'ocr_mode' => 'region_isolated',
    ];

    return document_ai_enrich_dad_structured($structured);
}

/**
 * @return array{ok: bool, error?: string, payload?: array<string, mixed>}
 */
function document_ai_process_dad_region_pipeline(string $absolutePath, string $reportType): array
{
    if (!document_ai_is_configured()) {
        return ['ok' => false, 'error' => 'Document AI credentials are not configured.'];
    }

    $prep = document_ai_dad_prepare_regions($absolutePath);
    if (!$prep['ok']) {
        return ['ok' => false, 'error' => (string) ($prep['error'] ?? 'DTR region preparation failed')];
    }

    $regionPaths = is_array($prep['regions'] ?? null) ? $prep['regions'] : [];
    $tempDir = (string) ($prep['temp_dir'] ?? '');
    $regionTexts = [];
    $ocrMeta = [];

    try {
        foreach (document_ai_dad_ocr_region_keys() as $key) {
            $path = trim((string) ($regionPaths[$key] ?? ''));
            if ($path === '' || !is_file($path)) {
                $regionTexts[$key] = '';
                continue;
            }
            $raw = document_ai_ocr_region_text($path);
            $regionTexts[$key] = $raw;
            $ocrMeta[$key] = ['chars' => strlen($raw), 'path' => basename($path)];
        }
    } finally {
        if ($tempDir !== '') {
            document_ai_dad_remove_dir($tempDir);
        }
    }

    $structured = document_ai_dad_structured_from_regions($regionTexts);
    $structured['region_ocr'] = $ocrMeta;
    $structured['preprocess_engine'] = (string) ($prep['engine'] ?? 'unknown');
    $structured['layout_mode'] = (string) ($prep['layout_mode'] ?? 'table_grid');
    $structured['raw'] = json_encode($regionTexts, JSON_UNESCAPED_UNICODE) ?: '';

    $formatted = document_ai_format_structured($structured, $reportType, '');

    $processorName = '';
    $cfg = document_ai_config();
    if ($cfg['processor_id'] !== '') {
        $processorName = \Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient::processorName(
            $cfg['project_id'],
            $cfg['location'],
            $cfg['processor_id']
        );
    }

    return [
        'ok' => true,
        'payload' => [
            'raw' => $structured['raw'],
            'structured' => $structured,
            'formatted' => $formatted,
            'processed_at' => date('c'),
            'processor' => $processorName,
        ],
    ];
}
