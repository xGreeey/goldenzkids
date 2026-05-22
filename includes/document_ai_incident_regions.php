<?php
declare(strict_types=1);

/**
 * Region-based incident form OCR: preprocess → crop regions → Document AI per box.
 */

require_once __DIR__ . '/document_ai_rest.php';

use Google\Cloud\DocumentAI\V1\Document;

/**
 * Keys sent to Document AI (one crop each). Post is not on this form — filled from guard profile in PHP.
 *
 * @return list<string>
 */
function document_ai_incident_is_stacked_layout(string $layoutMode): bool
{
    return str_starts_with($layoutMode, 'stacked');
}

/**
 * @return list<string>
 */
function document_ai_incident_ocr_region_keys(string $layoutMode = ''): array
{
    if (document_ai_incident_is_stacked_layout($layoutMode)) {
        return ['incident_description', 'action_taken'];
    }

    return ['name_of_guard', 'incident_description', 'action_taken'];
}

/** @return array<string, array{x_min: float, y_min: float, x_max: float, y_max: float}> */
function document_ai_incident_region_boxes(): array
{
    return [
        'name_of_guard' => ['x_min' => 0.09, 'y_min' => 0.142, 'x_max' => 0.56, 'y_max' => 0.198],
        'incident_description' => ['x_min' => 0.04, 'y_min' => 0.365, 'x_max' => 0.465, 'y_max' => 0.855],
        'action_taken' => ['x_min' => 0.535, 'y_min' => 0.365, 'x_max' => 0.96, 'y_max' => 0.855],
    ];
}

/**
 * Reject OCR text that belongs in action-taken narrative, not Post (incident form has no Post field).
 */
function document_ai_incident_is_contaminated_post(string $text): bool
{
    $text = trim($text);
    if ($text === '' || strlen($text) < 8) {
        return false;
    }

    $hits = 0;
    foreach (['clean', 'warn', 'sign', 'first', 'aid', 'immed', 'provided', 'area', 'mark'] as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\w*/iu', $text) === 1) {
            $hits++;
        }
    }

    return $hits >= 2;
}

function document_ai_incident_python_script_path(): string
{
    return APP_ROOT . '/scripts/incident_form_ocr_prep.py';
}

/**
 * @return list<string>|null argv prefix for python invocation
 */
function document_ai_incident_resolve_python_argv(): ?array
{
    $env = trim((string) ($_ENV['INCIDENT_OCR_PYTHON'] ?? ''));
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
 * @return array{ok: bool, error?: string, regions?: array<string, string>, temp_dir?: string, engine?: string}
 */
function document_ai_incident_prepare_regions_opencv(string $absolutePath): array
{
    $pythonArgv = document_ai_incident_resolve_python_argv();
    $script = document_ai_incident_python_script_path();
    if ($pythonArgv === null || !is_file($script)) {
        return ['ok' => false, 'error' => 'OpenCV preprocessor unavailable'];
    }

    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gk_inc_' . bin2hex(random_bytes(8));
    if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
        return ['ok' => false, 'error' => 'Could not create temp directory'];
    }

    $cmdParts = array_merge(
        $pythonArgv,
        [$script, $absolutePath, $tempDir]
    );
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
        document_ai_incident_remove_dir($tempDir);

        return [
            'ok' => false,
            'error' => (string) ($decoded['error'] ?? 'OpenCV preprocessing failed'),
        ];
    }

    $regions = is_array($decoded['regions'] ?? null) ? $decoded['regions'] : [];
    $layoutMode = (string) ($decoded['layout_mode'] ?? '');
    $normalized = [];
    foreach (document_ai_incident_ocr_region_keys($layoutMode) as $key) {
        $path = trim((string) ($regions[$key] ?? ''));
        if ($path !== '' && is_file($path)) {
            $normalized[$key] = $path;
        }
    }

    if ($normalized === []) {
        document_ai_incident_remove_dir($tempDir);

        return ['ok' => false, 'error' => 'No region crops produced'];
    }

    $boxes = is_array($decoded['boxes'] ?? null) ? $decoded['boxes'] : [];

    return [
        'ok' => true,
        'regions' => $normalized,
        'temp_dir' => $tempDir,
        'engine' => (string) ($decoded['engine'] ?? 'opencv'),
        'layout_mode' => $layoutMode !== '' ? $layoutMode : 'unknown',
        'boxes' => $boxes,
        'full_preprocessed' => (string) ($decoded['full'] ?? ''),
    ];
}

/**
 * GD fallback: 2x scale, grayscale, contrast, crop fixed regions.
 *
 * @return array{ok: bool, error?: string, regions?: array<string, string>, temp_dir?: string, engine?: string}
 */
function document_ai_incident_prepare_regions_gd(string $absolutePath): array
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
    if (function_exists('imageconvolution')) {
        imageconvolution($full, [[0, -1, 0], [-1, 5, -1], [0, -1, 0]], 1, 0);
    }

    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gk_inc_' . bin2hex(random_bytes(8));
    if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
        imagedestroy($full);

        return ['ok' => false, 'error' => 'Could not create temp directory'];
    }

    $fw = imagesx($full);
    $fh = imagesy($full);
    $regions = [];

    foreach (document_ai_incident_ocr_region_keys() as $key) {
        $box = document_ai_incident_region_boxes()[$key] ?? null;
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

    if ($regions === []) {
        document_ai_incident_remove_dir($tempDir);

        return ['ok' => false, 'error' => 'No region crops produced (GD)'];
    }

    return [
        'ok' => true,
        'regions' => $regions,
        'temp_dir' => $tempDir,
        'engine' => 'gd',
    ];
}

/**
 * @return array{ok: bool, error?: string, regions?: array<string, string>, temp_dir?: string, engine?: string}
 */
function document_ai_incident_prepare_regions(string $absolutePath): array
{
    $opencv = document_ai_incident_prepare_regions_opencv($absolutePath);
    if ($opencv['ok']) {
        return $opencv;
    }

    return document_ai_incident_prepare_regions_gd($absolutePath);
}

function document_ai_incident_remove_dir(string $dir): void
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

/**
 * OCR a single cropped region (isolated stream — not full-page layout).
 */
function document_ai_ocr_region_text(string $regionImagePath): string
{
    if (!is_file($regionImagePath) || !is_readable($regionImagePath)) {
        return '';
    }

    $bytes = file_get_contents($regionImagePath);
    if ($bytes === false || $bytes === '') {
        return '';
    }

    $cfg = document_ai_config();
    $mimeType = document_ai_mime_for_path($regionImagePath);

    try {
        if (document_ai_should_use_direct_rest()) {
            $rest = document_ai_rest_process_document($bytes, $mimeType, $cfg);
            if ($rest['ok']) {
                $document = $rest['document'] ?? null;
                if ($document instanceof Document) {
                    return document_ai_region_text_from_document($document);
                }

                return trim((string) ($rest['raw_text'] ?? ''));
            }
        }

        $client = document_ai_client($cfg);
        $processorName = document_ai_resolve_processor_name($client, $cfg);
        if ($processorName === '') {
            return '';
        }

        $raw = (new \Google\Cloud\DocumentAI\V1\RawDocument())
            ->setContent($bytes)
            ->setMimeType($mimeType);

        $ocrConfig = (new \Google\Cloud\DocumentAI\V1\OcrConfig())
            ->setEnableNativePdfParsing(true);
        $processOptions = (new \Google\Cloud\DocumentAI\V1\ProcessOptions())->setOcrConfig($ocrConfig);

        $request = (new \Google\Cloud\DocumentAI\V1\ProcessRequest())
            ->setName($processorName)
            ->setRawDocument($raw)
            ->setProcessOptions($processOptions)
            ->setSkipHumanReview(true);

        $response = $client->processDocument($request);
        $document = $response->getDocument();
        if (!$document instanceof Document) {
            return '';
        }

        return document_ai_region_text_from_document($document);
    } catch (Throwable $e) {
        error_log('document_ai_ocr_region_text: ' . $e->getMessage());

        return '';
    }
}

/**
 * Reading order from lines/tokens — avoids relying on document.text alone for multi-line crops.
 */
function document_ai_region_text_from_document(Document $document): string
{
    $rawText = trim((string) $document->getText());
    $ordered = [];

    foreach ($document->getPages() as $page) {
        foreach ($page->getLines() as $line) {
            $layout = $line->getLayout();
            if ($layout === null) {
                continue;
            }
            $text = trim(document_ai_anchor_text($layout->getTextAnchor(), $rawText));
            if ($text === '') {
                continue;
            }
            $bounds = document_ai_bounds_from_poly($layout->getBoundingPoly());
            if ($bounds === null) {
                $ordered[] = ['text' => $text, 'y' => 0.0, 'x' => 0.0];
                continue;
            }
            $ordered[] = [
                'text' => $text,
                'y' => (float) $bounds['y_center'],
                'x' => (float) $bounds['x_center'],
            ];
        }
    }

    if ($ordered === []) {
        return $rawText;
    }

    usort($ordered, static fn (array $a, array $b): int => $a['y'] <=> $b['y'] ?: $a['x'] <=> $b['x']);

    return trim(implode("\n", array_map(static fn (array $row): string => (string) $row['text'], $ordered)));
}

/**
 * @param array<string, string> $regionTexts
 * @return array<string, mixed>
 */
function document_ai_incident_structured_from_regions(array $regionTexts): array
{
    $name = document_ai_sanitize_incident_name(trim($regionTexts['name_of_guard'] ?? ''));
    $post = '';
    $incident = document_ai_polish_incident_handwriting(trim($regionTexts['incident_description'] ?? ''));
    $action = document_ai_polish_incident_handwriting(trim($regionTexts['action_taken'] ?? ''));

    if (document_ai_incident_regions_look_swapped($incident, $action)) {
        [$incident, $action] = [$action, $incident];
    }

    $structured = [
        'template' => 'incident_report',
        'name' => $name,
        'post' => $post,
        'date' => '',
        'incident_description' => $incident,
        'action_taken' => $action,
        'ocr_mode' => 'region_isolated',
    ];

    return document_ai_enrich_incident_structured($structured);
}

/**
 * Heuristic: action narrative mis-assigned to left column when right crop is nearly empty.
 */
function document_ai_incident_regions_look_swapped(string $incident, string $action): bool
{
    if ($action !== '' || strlen($incident) < 24) {
        return false;
    }

    return preg_match(
        '/\b(?:clean|warn|sign|first\s*aid|immed|provided|area|mark)\b/iu',
        $incident
    ) === 1;
}

function document_ai_incident_action_looks_truncated(string $action, string $incident): bool
{
    $action = trim($action);
    if ($action === '') {
        return true;
    }

    if (strlen($incident) > 80 && strlen($action) < 45) {
        return true;
    }

    if (strlen($action) < 28 && !str_contains($action, ' ')) {
        return true;
    }

    return preg_match('/^\s*(?:ASSISTANCE|ESCORT|NOTIFIED|REPORTED)\s*\.?\s*$/iu', $action) === 1;
}

/**
 * Re-crop action region with expanded box when first OCR returned a fragment.
 *
 * @param array<string, list<float>|array{0: float, 1: float, 2: float, 3: float}> $boxes
 */
function document_ai_incident_rerocr_action_expanded(string $fullImagePath, array $boxes): string
{
    if ($fullImagePath === '' || !is_file($fullImagePath) || !extension_loaded('gd')) {
        return '';
    }

    $box = $boxes['action_taken'] ?? null;
    if (!is_array($box) || count($box) < 4) {
        return '';
    }

    $image = @imagecreatefromjpeg($fullImagePath);
    if ($image === false) {
        return '';
    }

    $fw = imagesx($image);
    $fh = imagesy($image);
    $x1 = max(0, (int) floor((float) $box[0] * $fw) - (int) ($fw * 0.02));
    $y1 = max(0, (int) floor((float) $box[1] * $fh) - (int) ($fh * 0.02));
    $x2 = min($fw, (int) ceil((float) $box[2] * $fw) + (int) ($fw * 0.02));
    $y2 = min($fh, (int) ceil((float) $box[3] * $fh) + (int) ($fh * 0.03));

    $crop = imagecrop($image, ['x' => $x1, 'y' => $y1, 'width' => $x2 - $x1, 'height' => $y2 - $y1]);
    imagedestroy($image);
    if ($crop === false) {
        return '';
    }

    $temp = tempnam(sys_get_temp_dir(), 'gk_act_') . '.jpg';
    imagejpeg($crop, $temp, 96);
    imagedestroy($crop);

    $text = document_ai_ocr_region_text($temp);
    @unlink($temp);

    return $text;
}

/**
 * @return array{ok: bool, error?: string, payload?: array<string, mixed>}
 */
function document_ai_process_incident_region_pipeline(string $absolutePath, string $reportType): array
{
    if (!document_ai_is_configured()) {
        return ['ok' => false, 'error' => 'Document AI credentials are not configured.'];
    }

    $prep = document_ai_incident_prepare_regions($absolutePath);
    if (!$prep['ok']) {
        return ['ok' => false, 'error' => (string) ($prep['error'] ?? 'Region preparation failed')];
    }

    $regionPaths = is_array($prep['regions'] ?? null) ? $prep['regions'] : [];
    $tempDir = (string) ($prep['temp_dir'] ?? '');
    $regionTexts = [];
    $ocrMeta = [];

    $layoutMode = (string) ($prep['layout_mode'] ?? '');
    $boxes = is_array($prep['boxes'] ?? null) ? $prep['boxes'] : [];
    $fullPreprocessed = (string) ($prep['full_preprocessed'] ?? '');

    try {
        foreach (document_ai_incident_ocr_region_keys($layoutMode) as $key) {
            $path = trim((string) ($regionPaths[$key] ?? ''));
            if ($path === '' || !is_file($path)) {
                $regionTexts[$key] = '';
                continue;
            }
            $raw = document_ai_ocr_region_text($path);
            $regionTexts[$key] = $raw;
            $ocrMeta[$key] = ['chars' => strlen($raw), 'path' => basename($path)];
        }

        $incidentRaw = trim((string) ($regionTexts['incident_description'] ?? ''));
        $actionRaw = trim((string) ($regionTexts['action_taken'] ?? ''));
        if (
            document_ai_incident_action_looks_truncated($actionRaw, $incidentRaw)
            && $fullPreprocessed !== ''
            && $boxes !== []
        ) {
            $retry = trim(document_ai_incident_rerocr_action_expanded($fullPreprocessed, $boxes));
            if ($retry !== '' && strlen($retry) > strlen($actionRaw)) {
                $regionTexts['action_taken'] = $retry;
                $ocrMeta['action_taken_retry'] = ['chars' => strlen($retry)];
            }
        }
    } finally {
        if ($tempDir !== '') {
            document_ai_incident_remove_dir($tempDir);
        }
    }

    $structured = document_ai_incident_structured_from_regions($regionTexts);
    $structured['region_ocr'] = $ocrMeta;
    $structured['preprocess_engine'] = (string) ($prep['engine'] ?? 'unknown');
    $structured['layout_mode'] = (string) ($prep['layout_mode'] ?? 'unknown');
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
            'extraction' => $structured['extraction'] ?? document_ai_incident_extraction_json($structured),
        ],
    ];
}
