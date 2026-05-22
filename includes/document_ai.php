<?php
declare(strict_types=1);

require_once __DIR__ . '/document_ai_rest.php';

use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\Document;
use Google\Cloud\DocumentAI\V1\Document\Page\FormField;
use Google\Cloud\DocumentAI\V1\Document\Page\Table;
use Google\Cloud\DocumentAI\V1\Document\Page\Token;
use Google\Cloud\DocumentAI\V1\Document\TextAnchor;
use Google\Cloud\DocumentAI\V1\OcrConfig;
use Google\Cloud\DocumentAI\V1\ListProcessorsRequest;
use Google\Cloud\DocumentAI\V1\ProcessOptions;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\RawDocument;

/** @return array{project_id: string, location: string, credentials: string, processor_id: string} */
function document_ai_config(): array
{
    $credentials = trim((string) ($_ENV['GOOGLE_DOCUMENT_AI_CREDENTIALS'] ?? ''));
    if ($credentials === '') {
        $credentials = 'config/google-document-ai.json';
    }
    if (!str_starts_with($credentials, '/') && !preg_match('#^[A-Za-z]:#', $credentials)) {
        $credentials = APP_ROOT . '/' . ltrim($credentials, '/');
    }

    return [
        'project_id' => trim((string) ($_ENV['GOOGLE_DOCUMENT_AI_PROJECT_ID'] ?? 'dgd-document-ai-prototype')),
        'location' => trim((string) ($_ENV['GOOGLE_DOCUMENT_AI_LOCATION'] ?? 'us')) ?: 'us',
        'credentials' => $credentials,
        'processor_id' => trim((string) ($_ENV['GOOGLE_DOCUMENT_AI_PROCESSOR_ID'] ?? '')),
    ];
}

function document_ai_is_configured(): bool
{
    $cfg = document_ai_config();

    return is_file($cfg['credentials']) && is_readable($cfg['credentials']);
}

function document_ai_reference_image_url(string $reportType): string
{
    $file = match ($reportType) {
        'Incident Report' => 'report-template-incident.png',
        'Incident' => 'report-template-incident.png',
        'Post incident' => 'report-template-incident.png',
        'Daily Time Record', 'Daily Attendance Document' => 'report-template-dad.png',
        default => '',
    };
    if ($file === '') {
        return '';
    }

    return app_url('admin/assets/img/' . $file);
}

function document_ai_mime_for_path(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return match ($ext) {
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        default => 'image/jpeg',
    };
}

function document_ai_is_incident_report_type(string $reportType): bool
{
    return in_array(trim($reportType), ['Incident Report', 'Incident', 'Post incident'], true);
}

/**
 * Improve scan contrast for handwriting OCR (grayscale, contrast, sharpen). Returns temp JPEG path or null.
 */
function document_ai_preprocess_incident_scan(string $absolutePath): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }

    $mime = document_ai_mime_for_path($absolutePath);
    $image = match ($mime) {
        'image/png' => @imagecreatefrompng($absolutePath),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
        'image/gif' => @imagecreatefromgif($absolutePath),
        'image/jpeg' => @imagecreatefromjpeg($absolutePath),
        default => false,
    };
    if ($image === false) {
        return null;
    }

    if (function_exists('imagepalettetotruecolor')) {
        imagepalettetotruecolor($image);
    }
    imagealphablending($image, true);
    imagesavealpha($image, false);

    imagefilter($image, IMG_FILTER_GRAYSCALE);
    imagefilter($image, IMG_FILTER_CONTRAST, -22);
    imagefilter($image, IMG_FILTER_BRIGHTNESS, 8);

    $sharpen = [
        [0, -1, 0],
        [-1, 5, -1],
        [0, -1, 0],
    ];
    if (function_exists('imageconvolution')) {
        imageconvolution($image, $sharpen, 1, 0);
    }

    $temp = tempnam(sys_get_temp_dir(), 'gk_inc_');
    if ($temp === false) {
        imagedestroy($image);

        return null;
    }

    $out = $temp . '.jpg';
    @unlink($temp);
    if (!imagejpeg($image, $out, 92)) {
        imagedestroy($image);
        @unlink($out);

        return null;
    }

    imagedestroy($image);

    return is_file($out) ? $out : null;
}

/**
 * @return array{ok: bool, error?: string, payload?: array<string, mixed>}
 */
function document_ai_process_report_scan(string $absolutePath, string $reportType): array
{
    if (!document_ai_is_configured()) {
        return ['ok' => false, 'error' => 'Document AI credentials are not configured. Add config/google-document-ai.json.'];
    }
    if (!is_file($absolutePath) || !is_readable($absolutePath)) {
        return ['ok' => false, 'error' => 'Report scan file was not found on the server.'];
    }

    if (document_ai_is_dad_report_type($reportType)) {
        $dadRegionResult = document_ai_process_dad_region_pipeline($absolutePath, $reportType);
        if ($dadRegionResult['ok']) {
            return $dadRegionResult;
        }

        error_log(
            'DTR region OCR failed, falling back to full-page: '
            . ($dadRegionResult['error'] ?? 'unknown')
        );
    }

    if (document_ai_is_incident_report_type($reportType)) {
        $regionResult = document_ai_process_incident_region_pipeline($absolutePath, $reportType);
        if ($regionResult['ok']) {
            return $regionResult;
        }

        error_log(
            'Incident region OCR failed, falling back to full-page: '
            . ($regionResult['error'] ?? 'unknown')
        );
    }

    $scanPath = $absolutePath;
    $preprocessed = null;
    if (document_ai_is_incident_report_type($reportType)) {
        $preprocessed = document_ai_preprocess_incident_scan($absolutePath);
        if ($preprocessed !== null) {
            $scanPath = $preprocessed;
        }
    }

    $bytes = file_get_contents($scanPath);
    if ($bytes === false || $bytes === '') {
        if ($preprocessed !== null) {
            @unlink($preprocessed);
        }

        return ['ok' => false, 'error' => 'Could not read the report scan image.'];
    }

    $cfg = document_ai_config();
    $mimeType = document_ai_mime_for_path($scanPath);
    $restFailure = null;

    try {
        if (document_ai_should_use_direct_rest()) {
            $restResult = document_ai_process_with_rest($bytes, $mimeType, $cfg, $reportType);
            if ($restResult['ok']) {
                return $restResult;
            }

            $restFailure = $restResult;
            error_log('Document AI REST failed: ' . ($restResult['error'] ?? 'unknown'));
        }

        return document_ai_process_with_client($bytes, $mimeType, $cfg, $reportType);
    } catch (Throwable $e) {
        error_log('Document AI OCR failed: ' . $e->getMessage());

        if ($restFailure !== null && ($restFailure['error'] ?? '') !== '') {
            return $restFailure;
        }

        return ['ok' => false, 'error' => document_ai_public_error($e)];
    } finally {
        if ($preprocessed !== null) {
            @unlink($preprocessed);
        }
    }
}

/**
 * @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg
 * @return array{ok: bool, error?: string, payload?: array<string, mixed>}
 */
function document_ai_process_with_rest(string $bytes, string $mimeType, array $cfg, string $reportType): array
{
    try {
        $rest = document_ai_rest_process_document($bytes, $mimeType, $cfg);
        if (!$rest['ok']) {
            return ['ok' => false, 'error' => (string) ($rest['error'] ?? 'Document AI request failed.')];
        }

        $processorName = (string) ($rest['processor'] ?? '');
        $document = $rest['document'] ?? null;
        if ($document instanceof Document) {
            return document_ai_success_payload($document, $reportType, $processorName);
        }

        $rawText = trim((string) ($rest['raw_text'] ?? ''));
        if ($rawText === '') {
            return ['ok' => false, 'error' => 'Document AI returned an empty result.'];
        }

        return document_ai_success_payload_from_text($rawText, $reportType, $processorName);
    } catch (Throwable $e) {
        error_log('Document AI REST exception: ' . $e->getMessage());

        return ['ok' => false, 'error' => document_ai_public_error($e)];
    }
}

/**
 * @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg
 * @return array{ok: bool, error?: string, payload?: array<string, mixed>}
 */
function document_ai_process_with_client(
    string $bytes,
    string $mimeType,
    array $cfg,
    string $reportType
): array {
    $client = document_ai_client($cfg);
    $processorName = document_ai_resolve_processor_name($client, $cfg);
    if ($processorName === '') {
        return ['ok' => false, 'error' => 'No Document AI OCR processor found. Set GOOGLE_DOCUMENT_AI_PROCESSOR_ID in .env.'];
    }

    $raw = (new RawDocument())
        ->setContent($bytes)
        ->setMimeType($mimeType);

    $ocrConfig = (new OcrConfig())
        ->setEnableNativePdfParsing(true);

    $processOptions = (new ProcessOptions())->setOcrConfig($ocrConfig);

    $request = (new ProcessRequest())
        ->setName($processorName)
        ->setRawDocument($raw)
        ->setProcessOptions($processOptions)
        ->setSkipHumanReview(true);

    $response = $client->processDocument($request);
    $document = $response->getDocument();
    if (!$document instanceof Document) {
        return ['ok' => false, 'error' => 'Document AI returned an empty result.'];
    }

    return document_ai_success_payload($document, $reportType, $processorName);
}

/**
 * @return array{ok: true, payload: array<string, mixed>}
 */
function document_ai_success_payload(Document $document, string $reportType, string $processorName): array
{
    $rawText = trim((string) $document->getText());
    $structured = document_ai_build_structured($document, $reportType, $rawText);
    $formatted = document_ai_format_structured($structured, $reportType, $rawText);

    return [
        'ok' => true,
        'payload' => [
            'raw' => $rawText,
            'structured' => $structured,
            'formatted' => $formatted,
            'processed_at' => date('c'),
            'processor' => $processorName,
        ],
    ];
}

/**
 * Text-only fallback when the REST JSON document cannot be hydrated (still useful on shared hosting).
 *
 * @return array{ok: true, payload: array<string, mixed>}
 */
function document_ai_success_payload_from_text(string $rawText, string $reportType, string $processorName): array
{
    $structured = document_ai_parse_by_template($rawText, $reportType);
    if (($structured['template'] ?? '') === 'daily_attendance') {
        $structured = document_ai_enrich_dad_structured($structured);
    } elseif (($structured['template'] ?? '') === 'incident_report') {
        $structured['raw'] = $rawText;
        $structured = document_ai_enrich_incident_structured($structured);
    }
    $formatted = document_ai_format_structured($structured, $reportType, $rawText);

    return [
        'ok' => true,
        'payload' => [
            'raw' => $rawText,
            'structured' => $structured,
            'formatted' => $formatted,
            'processed_at' => date('c'),
            'processor' => $processorName,
        ],
    ];
}

/** @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg */
function document_ai_client(array $cfg): DocumentProcessorServiceClient
{
    $location = $cfg['location'];
    $endpoint = $location === 'us'
        ? 'us-documentai.googleapis.com'
        : $location . '-documentai.googleapis.com';

    $options = [
        'credentials' => $cfg['credentials'],
        'apiEndpoint' => $endpoint,
    ];

    if (document_ai_should_use_direct_rest() || !extension_loaded('grpc')) {
        $options['transport'] = 'rest';
    }

    return new DocumentProcessorServiceClient($options);
}

/**
 * @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg
 */
function document_ai_resolve_processor_name(DocumentProcessorServiceClient $client, array $cfg): string
{
    if ($cfg['processor_id'] !== '') {
        return DocumentProcessorServiceClient::processorName(
            $cfg['project_id'],
            $cfg['location'],
            $cfg['processor_id']
        );
    }

    $listRequest = (new ListProcessorsRequest())
        ->setParent($client->locationName($cfg['project_id'], $cfg['location']));
    $response = $client->listProcessors($listRequest);
    foreach ($response->iterateAllElements() as $processor) {
        $type = (string) $processor->getType();
        $state = (string) $processor->getState();
        if ($state !== 'ENABLED') {
            continue;
        }
        if (stripos($type, 'OCR') !== false || stripos($type, 'FORM') !== false || $type === '') {
            return (string) $processor->getName();
        }
    }
    foreach ($response->iterateAllElements() as $processor) {
        if ((string) $processor->getState() === 'ENABLED') {
            return (string) $processor->getName();
        }
    }

    return '';
}

function document_ai_anchor_text(?TextAnchor $anchor, string $fullText): string
{
    if ($anchor === null) {
        return '';
    }
    $content = trim((string) $anchor->getContent());
    if ($content !== '') {
        return $content;
    }
    $parts = [];
    foreach ($anchor->getTextSegments() as $segment) {
        $start = (int) $segment->getStartIndex();
        $end = (int) $segment->getEndIndex();
        if ($end > $start && $fullText !== '') {
            $parts[] = substr($fullText, $start, $end - $start);
        }
    }

    return trim(implode('', $parts));
}

/**
 * @return array<string, mixed>
 */
function document_ai_build_structured(Document $document, string $reportType, string $rawText): array
{
    $formFields = [];
    foreach ($document->getPages() as $page) {
        foreach ($page->getFormFields() as $field) {
            if (!$field instanceof FormField) {
                continue;
            }
            $name = document_ai_anchor_text($field->getFieldName(), $rawText);
            $value = document_ai_anchor_text($field->getFieldValue(), $rawText);
            if ($name === '' && $value === '') {
                continue;
            }
            $formFields[] = ['label' => $name, 'value' => $value];
        }
    }

    $tables = [];
    foreach ($document->getPages() as $page) {
        foreach ($page->getTables() as $table) {
            if (!$table instanceof Table) {
                continue;
            }
            $tables[] = document_ai_table_matrix($table, $rawText);
        }
    }

    $parsed = document_ai_parse_by_template($rawText, $reportType);

    $merged = array_merge($parsed, [
        'report_type' => $reportType,
        'form_fields' => $formFields,
        'tables' => $tables,
    ]);

    if (($merged['template'] ?? '') === 'daily_attendance') {
        $spatial = document_ai_parse_dad_spatial($document, $rawText, $formFields);
        $merged = document_ai_dad_merge_spatial($spatial, $merged);

        return document_ai_enrich_dad_structured($merged);
    }

    if (($merged['template'] ?? '') === 'incident_report') {
        $spatial = document_ai_parse_incident_spatial($document, $rawText);
        $merged = document_ai_incident_merge_spatial($spatial, $merged);
        $merged['raw'] = $rawText;

        return document_ai_enrich_incident_structured($merged);
    }

    return $merged;
}

/**
 * @return list<list<string>>
 */
function document_ai_table_matrix(Table $table, string $fullText): array
{
    $rows = [];
    foreach ($table->getHeaderRows() as $row) {
        $cells = [];
        foreach ($row->getCells() as $cell) {
            $cells[] = document_ai_anchor_text($cell->getLayout()?->getTextAnchor(), $fullText);
        }
        if ($cells !== []) {
            $rows[] = $cells;
        }
    }
    foreach ($table->getBodyRows() as $row) {
        $cells = [];
        foreach ($row->getCells() as $cell) {
            $cells[] = document_ai_anchor_text($cell->getLayout()?->getTextAnchor(), $fullText);
        }
        if ($cells !== []) {
            $rows[] = $cells;
        }
    }

    return $rows;
}

/**
 * @param \Google\Cloud\DocumentAI\V1\BoundingPoly|null $poly
 * @return array{x_min: float, x_max: float, y_min: float, y_max: float, x_center: float, y_center: float}|null
 */
function document_ai_bounds_from_poly($poly): ?array
{
    if ($poly === null) {
        return null;
    }

    $xs = [];
    $ys = [];
    foreach ($poly->getNormalizedVertices() as $vertex) {
        $xs[] = (float) $vertex->getX();
        $ys[] = (float) $vertex->getY();
    }
    if ($xs === [] || $ys === []) {
        foreach ($poly->getVertices() as $vertex) {
            $xs[] = (float) $vertex->getX();
            $ys[] = (float) $vertex->getY();
        }
    }
    if ($xs === [] || $ys === []) {
        return null;
    }

    $xMin = min($xs);
    $xMax = max($xs);
    $yMin = min($ys);
    $yMax = max($ys);

    return [
        'x_min' => $xMin,
        'x_max' => $xMax,
        'y_min' => $yMin,
        'y_max' => $yMax,
        'x_center' => ($xMin + $xMax) / 2,
        'y_center' => ($yMin + $yMax) / 2,
    ];
}

/**
 * @return list<array{text: string, x_min: float, x_max: float, y_min: float, y_max: float, x_center: float, y_center: float}>
 */
function document_ai_collect_page_lines(Document $document, string $rawText): array
{
    $lines = [];
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
                continue;
            }
            $lines[] = array_merge(['text' => $text], $bounds);
        }
    }

    usort($lines, static fn (array $a, array $b): int => $a['y_center'] <=> $b['y_center'] ?: $a['x_center'] <=> $b['x_center']);

    return $lines;
}

/**
 * Word-level OCR anchors (used for incident columns — avoids horizontal line bleed).
 *
 * @return list<array{text: string, x_min: float, x_max: float, y_min: float, y_max: float, x_center: float, y_center: float}>
 */
function document_ai_collect_page_tokens(Document $document, string $rawText): array
{
    $tokens = [];

    foreach ($document->getPages() as $page) {
        foreach ($page->getTokens() as $token) {
            if (!$token instanceof Token) {
                continue;
            }
            $layout = $token->getLayout();
            if ($layout === null) {
                continue;
            }
            $text = trim(document_ai_anchor_text($layout->getTextAnchor(), $rawText));
            if ($text === '') {
                continue;
            }
            $bounds = document_ai_bounds_from_poly($layout->getBoundingPoly());
            if ($bounds === null) {
                continue;
            }
            $tokens[] = array_merge(['text' => $text], $bounds);
        }
    }

    usort(
        $tokens,
        static fn (array $a, array $b): int => $a['y_center'] <=> $b['y_center'] ?: $a['x_center'] <=> $b['x_center']
    );

    return $tokens;
}

/**
 * When Document AI returns no tokens, approximate words from lines (split wide lines at column boundary).
 *
 * @param list<array{text: string, x_min: float, x_max: float, y_min: float, y_max: float, x_center: float, y_center: float}> $lines
 * @return list<array{text: string, x_min: float, x_max: float, y_min: float, y_max: float, x_center: float, y_center: float}>
 */
function document_ai_incident_tokens_from_lines(array $lines, float $splitX = 0.5): array
{
    $tokens = [];

    foreach ($lines as $line) {
        $text = trim((string) ($line['text'] ?? ''));
        if ($text === '') {
            continue;
        }

        $xMin = (float) $line['x_min'];
        $xMax = (float) $line['x_max'];
        $width = $xMax - $xMin;

        if ($width < 0.1 || $xMax <= $splitX + 0.02 || $xMin >= $splitX - 0.02) {
            $tokens[] = array_merge(['text' => $text], $line);
            continue;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            continue;
        }

        $cursor = 0;
        foreach ($words as $word) {
            $start = $cursor / max(1, mb_strlen($text));
            $end = ($cursor + mb_strlen($word)) / max(1, mb_strlen($text));
            $cursor += mb_strlen($word) + 1;
            $wxMin = $xMin + $width * $start;
            $wxMax = $xMin + $width * $end;
            $tokens[] = [
                'text' => $word,
                'x_min' => $wxMin,
                'x_max' => $wxMax,
                'y_min' => (float) $line['y_min'],
                'y_max' => (float) $line['y_max'],
                'x_center' => ($wxMin + $wxMax) / 2,
                'y_center' => (float) $line['y_center'],
            ];
        }
    }

    usort(
        $tokens,
        static fn (array $a, array $b): int => $a['y_center'] <=> $b['y_center'] ?: $a['x_center'] <=> $b['x_center']
    );

    return $tokens;
}

/**
 * Build column text top-to-bottom (vertical reading order within one form box).
 *
 * @param list<array{text: string, x_center: float, y_center: float}> $tokens
 */
function document_ai_incident_compose_vertical_column_text(
    array $tokens,
    float $xMin,
    float $xMax,
    float $yMin = 0.0,
    float $yMax = 1.0
): string {
    $column = [];
    foreach ($tokens as $token) {
        $xc = (float) $token['x_center'];
        $yc = (float) $token['y_center'];
        if ($xc < $xMin || $xc > $xMax) {
            continue;
        }
        if ($yMin > 0 && $yc < $yMin) {
            continue;
        }
        if ($yMax < 1.0 && $yc > $yMax) {
            continue;
        }
        $text = trim((string) ($token['text'] ?? ''));
        if (
            $text === ''
            || document_ai_incident_is_boilerplate($text)
            || document_ai_incident_is_printed_label_text($text)
        ) {
            continue;
        }
        if (preg_match('/\b(?:INCIDENT\s+DESCRIPTION|ACTION\s+TAKEN|INCIDENT\s+REPORT)\b/iu', $text)) {
            continue;
        }
        if (preg_match('/^\s*(?:NAME|DATE|POST)(?:\s+OF\s+GUARD)?\s*:+/iu', $text) === 1) {
            continue;
        }
        $column[] = $token;
    }

    if ($column === []) {
        return '';
    }

    usort(
        $column,
        static fn (array $a, array $b): int => $a['y_center'] <=> $b['y_center'] ?: $a['x_center'] <=> $b['x_center']
    );

    $rows = [];
    // Tight row grouping: read top-to-bottom (vertical), not left-to-right across columns.
    $rowThreshold = 0.008;
    $currentY = null;
    $currentParts = [];

    foreach ($column as $token) {
        $yc = (float) $token['y_center'];
        $part = trim((string) ($token['text']));
        if ($part === '') {
            continue;
        }

        if ($currentY === null || abs($yc - $currentY) > $rowThreshold) {
            if ($currentParts !== []) {
                $rows[] = implode(' ', $currentParts);
            }
            $currentParts = [$part];
            $currentY = $yc;
            continue;
        }

        $currentParts[] = $part;
    }

    if ($currentParts !== []) {
        $rows[] = implode(' ', $currentParts);
    }

    return document_ai_sanitize_incident_handwriting(implode("\n", $rows));
}

/**
 * @param array{incident_description?: string, action_taken?: string, name?: string, post?: string, date?: string} $spatial
 * @param array<string, mixed> $textParsed
 * @return array<string, mixed>
 */
function document_ai_incident_merge_spatial(array $spatial, array $textParsed): array
{
    $incident = document_ai_sanitize_incident_handwriting((string) ($spatial['incident_description'] ?? ''));
    $textIncident = document_ai_sanitize_incident_handwriting((string) ($textParsed['incident_description'] ?? ''));
    if ($incident === '') {
        $incident = $textIncident;
    }

    $action = document_ai_sanitize_incident_handwriting((string) ($spatial['action_taken'] ?? ''));
    $textAction = document_ai_sanitize_incident_handwriting((string) ($textParsed['action_taken'] ?? ''));
    if ($action === '') {
        $action = $textAction;
    }

    if ($incident !== '' && $action !== '' && $incident === $action) {
        $action = $textAction !== '' && $textAction !== $incident ? $textAction : '';
    }

    $name = document_ai_sanitize_incident_name((string) ($spatial['name'] ?? ''));
    $textName = document_ai_sanitize_incident_name((string) ($textParsed['name'] ?? ''));
    if ($name === '' || ($textName !== '' && strlen($textName) > strlen($name))) {
        $name = $textName;
    }

    $post = document_ai_sanitize_dad_post((string) ($spatial['post'] ?? ''));
    $textPost = document_ai_sanitize_dad_post((string) ($textParsed['post'] ?? ''));
    if ($post === '' || ($textPost !== '' && strlen($textPost) > strlen($post))) {
        $post = $textPost;
    }

    $date = document_ai_sanitize_incident_date((string) ($spatial['date'] ?? ''));
    if ($date === '') {
        $date = document_ai_sanitize_incident_date((string) ($textParsed['date'] ?? ''));
    }

    $textParsed['incident_description'] = $incident;
    $textParsed['action_taken'] = $action;
    $textParsed['name'] = $name;
    $textParsed['post'] = $post;
    $textParsed['date'] = $date;

    return $textParsed;
}

function document_ai_dad_is_boilerplate(string $text): bool
{
    $upper = strtoupper(trim($text));
    if ($upper === '') {
        return true;
    }

    $needles = [
        'DAILY ATTENDANCE',
        'SINGLE/DOUBLE POST',
        'GOLDEN Z',
        'SECURITY AND INVESTIGATION',
        'AGENCY, INC',
        'AGENCY INC',
        'PLEASE WRITE',
        'BIG LETTERS',
        'CAPITALIZED',
        'PROCESSED DIGITALLY',
        'THIS DOCUMENT IS TO BE',
        'TIME IN TIME OUT',
        'NAME TIME IN',
    ];

    foreach ($needles as $needle) {
        if (str_contains($upper, $needle)) {
            return true;
        }
    }

    if (preg_match('/^(DATE|POST|NAME|TIME\s*IN|TIME\s*OUT)\s*:?\s*$/iu', $upper)) {
        return true;
    }

    return preg_match('/^\d+\.\s*$/', $upper) === 1;
}

function document_ai_dad_is_date_text(string $text): bool
{
    $t = trim($text);
    if ($t === '') {
        return false;
    }

    return preg_match(
        '/\b(?:JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:TEMBER)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?)\b[\s,.\-]*\d{1,2}|\d{1,2}[\s,.\-\/]+\d{1,2}[\s,.\-\/]+\d{2,4}/iu',
        $t
    ) === 1;
}

function document_ai_dad_is_time_text(string $text): bool
{
    $t = trim($text);
    if ($t === '') {
        return false;
    }

    return preg_match('/^\d{1,2}(?::\d{2})?\s*(?:[AP]\.?M\.?)?$/iu', $t) === 1
        || preg_match('/\b\d{1,2}(?::\d{2})?\s*(?:[AP]\.?M\.?)\b/iu', $t) === 1;
}

function document_ai_sanitize_dad_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }

    $stripPatterns = [
        '/\bBIG\s+LETTERS\.?\s*/iu',
        '/\bPLEASE\s+WRITE\b.*$/iu',
        '/\bIN\s+CAPITALIZED\b.*$/iu',
        '/\bCAPITALIZED\/BIG\s+LETTERS\.?\s*/iu',
        '/\b(?:NAME|TIME\s*IN|TIME\s*OUT)\b/iu',
        '/\b\d{1,2}\.\s*/u',
        '/\b(?:JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:TEMBER)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?)\b[\s,.\-]*\d{1,2}(?:,?\s*\d{4})?/iu',
        '/\bDAILY\s+ATTENDANCE\b.*$/iu',
        '/\bGOLDEN\s+Z[\w\s\-]*\b(?:SECURITY|AGENCY|INVESTIGATION).*$/iu',
        '/\bSINGLE\/DOUBLE\s+POST\b/iu',
        '/\bPROCESSED\s+DIGITALLY\b/iu',
    ];

    foreach ($stripPatterns as $pattern) {
        $name = trim(preg_replace($pattern, ' ', $name) ?? $name);
    }

    $name = trim(preg_replace('/\s{2,}/u', ' ', $name) ?? $name);
    if ($name === '' || document_ai_dad_is_boilerplate($name)) {
        return '';
    }

    if (preg_match('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,5})\b/u', $name, $m)) {
        return trim((string) $m[1]);
    }

    if (preg_match('/\b([A-Z]{2,}(?:\s+[A-Z]{2,}){1,5})\b/u', $name, $m)) {
        $candidate = trim((string) $m[1]);
        if (!document_ai_dad_is_boilerplate($candidate) && !document_ai_dad_is_date_text($candidate)) {
            return $candidate;
        }
    }

    return $name;
}

function document_ai_sanitize_dad_post(string $post): string
{
    $post = trim($post);
    if ($post === '' || document_ai_dad_is_boilerplate($post)) {
        return '';
    }

    $post = trim(preg_replace('/\bGOLDEN\s+Z[\w\s\-]*\b(?:SECURITY|AGENCY|INVESTIGATION).*$/iu', '', $post) ?? $post);
    $post = trim(preg_replace('/^POST\s*:+\s*/iu', '', $post) ?? $post);
    $post = trim(preg_replace('/^DATE\s*:+\s*/iu', '', $post) ?? $post);
    $post = trim(preg_replace('/^[_\-\s\.]+/u', '', $post) ?? $post);
    $post = trim(preg_replace('/[_\-\s\.]+$/u', '', $post) ?? $post);

    if ($post === '' || document_ai_dad_is_boilerplate($post) || document_ai_dad_is_date_text($post)) {
        return '';
    }

    return trim(preg_replace('/\s{2,}/u', ' ', $post) ?? $post);
}

/**
 * Extract handwritten post from the printed "POST:" / "POST:_______" field (not title "SINGLE/DOUBLE POST").
 */
function document_ai_extract_dad_post_from_text(string $text): string
{
    $patterns = [
        '/(?<!(?:SINGLE\/DOUBLE|ATTENDANCE)\s*)POST\s*:+\s*(?:[_\-\s\.]+)?\s*([A-Za-z0-9][^\n_]{1,80})/iu',
        '/(?<!(?:SINGLE\/DOUBLE|ATTENDANCE)\s*)POST\s*:+\s*(?:[_\-\s\.]*)?\s*[\r\n]+\s*([A-Za-z0-9][^\n_]{1,80})/iu',
        '/^POST\s*:+\s*(?:[_\-\s\.]+)?\s*([A-Za-z0-9][^\n_]{1,80})$/imu',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $m)) {
            $value = document_ai_sanitize_dad_post(trim((string) ($m[1])));
            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
}

/**
 * @param list<array{text: string, x_min: float, x_max: float, y_min: float, y_max: float, x_center: float, y_center: float}> $lines
 */
function document_ai_extract_dad_post_from_lines(array $lines): string
{
    $postLabelYMax = null;
    $postXMin = null;
    $postXMax = null;

    foreach ($lines as $line) {
        $text = (string) $line['text'];
        if (preg_match('/(?:SINGLE\/DOUBLE|ATTENDANCE\s+DOCUMENT).*POST/iu', $text)) {
            continue;
        }
        if (!preg_match('/\bPOST\s*:+/iu', $text)) {
            continue;
        }

        if (preg_match('/\bPOST\s*:+\s*(?:[_\-\s\.]+)?\s*(.+)$/iu', $text, $m)) {
            $value = document_ai_sanitize_dad_post(trim((string) $m[1]));
            if ($value !== '') {
                return $value;
            }
        }

        $postLabelYMax = (float) $line['y_max'];
        $postXMin = (float) $line['x_min'];
        $postXMax = (float) $line['x_max'];
    }

    if ($postLabelYMax === null) {
        return '';
    }

    foreach ($lines as $line) {
        $yc = (float) $line['y_center'];
        if ($yc <= $postLabelYMax + 0.005) {
            continue;
        }
        if ($yc > $postLabelYMax + 0.12) {
            continue;
        }
        $xc = (float) $line['x_center'];
        if ($postXMin !== null && $xc < $postXMin - 0.12) {
            continue;
        }
        $text = (string) $line['text'];
        if (preg_match('/\b(?:POST|DATE|NAME|TIME)\b\s*:?/iu', $text)) {
            continue;
        }
        $value = document_ai_sanitize_dad_post($text);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function document_ai_sanitize_dad_date(string $date): string
{
    $date = trim($date);
    if ($date === '' || document_ai_dad_is_boilerplate($date)) {
        return '';
    }

    $date = trim(preg_replace('/^DATE\s*:?\s*/iu', '', $date) ?? $date);

    return document_ai_dad_is_date_text($date) ? $date : '';
}

function document_ai_sanitize_dad_time(string $time): string
{
    $time = trim($time);
    if ($time === '') {
        return '';
    }

    if (preg_match('/(\d{1,2}(?::\d{2})?\s*(?:[AP]\.?M\.?)?)/iu', $time, $m)) {
        return trim((string) $m[1]);
    }

    return '';
}

/**
 * @param list<array{label: string, value: string}> $formFields
 * @return array{post: string, dates: list<string>, attendance_rows: list<array{name: string, time_in: string, time_out: string}>}
 */
function document_ai_parse_dad_spatial(Document $document, string $rawText, array $formFields): array
{
    $lines = document_ai_collect_page_lines($document, $rawText);
    $post = document_ai_extract_dad_post_from_text($rawText);
    if ($post === '') {
        $post = document_ai_extract_dad_post_from_lines($lines);
    }
    $dates = [];
    $attendanceRows = [];

    $agencyYMax = 0.0;
    $headerYMin = 1.0;
    $headerYMax = 0.0;
    $nameColX = 0.25;
    $timeInColX = 0.5;
    $timeOutColX = 0.72;
    $dateRightMinX = 0.52;

    foreach ($lines as $line) {
        $text = (string) $line['text'];
        $upper = strtoupper($text);
        if (
            str_contains($upper, 'GOLDEN')
            && (str_contains($upper, 'SECURITY') || str_contains($upper, 'AGENCY') || str_contains($upper, 'INVESTIGATION'))
        ) {
            $agencyYMax = max($agencyYMax, (float) $line['y_max']);
        }
    }

    foreach ($lines as $line) {
        $text = (string) $line['text'];
        $upper = strtoupper($text);
        if (preg_match('/\bNAME\b/u', $upper) && preg_match('/\bTIME\b/u', $upper)) {
            $headerYMin = min($headerYMin, (float) $line['y_min']);
            $headerYMax = max($headerYMax, (float) $line['y_max']);
        }
    }

    if ($agencyYMax > 0 && $headerYMin >= 1.0) {
        $headerYMin = min(0.95, $agencyYMax + 0.28);
    }
    if ($headerYMax <= 0 && $agencyYMax > 0) {
        $headerYMax = min(0.95, $agencyYMax + 0.22);
    }

    foreach ($lines as $line) {
        $text = (string) $line['text'];
        $upper = strtoupper(trim($text));
        $xc = (float) $line['x_center'];

        if ($upper === 'NAME' || str_starts_with($upper, 'NAME ')) {
            $nameColX = $xc;
        } elseif (preg_match('/^TIME\s*IN\b/u', $upper)) {
            $timeInColX = $xc;
        } elseif (preg_match('/^TIME\s*OUT\b/u', $upper)) {
            $timeOutColX = $xc;
        } elseif ($upper === 'DATE' || str_starts_with($upper, 'DATE ')) {
            if ($xc >= $dateRightMinX) {
                $dateRightMinX = min($dateRightMinX, (float) $line['x_min']);
            }
        }
    }

    foreach ($lines as $line) {
        $text = (string) $line['text'];
        $yc = (float) $line['y_center'];
        $xc = (float) $line['x_center'];

        if (document_ai_dad_is_boilerplate($text)) {
            continue;
        }

        if ($agencyYMax > 0 && $yc <= $agencyYMax + 0.02) {
            continue;
        }

        if (document_ai_dad_is_date_text($text) && $xc >= $dateRightMinX) {
            $cleanDate = document_ai_sanitize_dad_date($text);
            if ($cleanDate !== '') {
                $dates[] = $cleanDate;
            }
            continue;
        }

        if (
            $post === ''
            && $headerYMax > 0
            && $yc > $agencyYMax
            && $yc < $headerYMin
            && !preg_match('/\bPOST\s*:+/iu', $text)
        ) {
            if ($xc < $dateRightMinX && !document_ai_dad_is_date_text($text) && !document_ai_dad_is_time_text($text)) {
                $candidate = document_ai_sanitize_dad_post($text);
                if ($candidate !== '') {
                    $post = $candidate;
                }
            }
        }
    }

    if ($post === '') {
        $post = document_ai_extract_dad_post_from_lines($lines);
    }

    $valueLines = [];
    foreach ($lines as $line) {
        $yc = (float) $line['y_center'];
        if ($headerYMax > 0 && $yc <= $headerYMax + 0.01) {
            continue;
        }
        if ($agencyYMax > 0 && $yc <= $agencyYMax + 0.015) {
            continue;
        }
        $text = (string) $line['text'];
        if (document_ai_dad_is_boilerplate($text)) {
            continue;
        }
        $valueLines[] = $line;
    }

    $currentRow = ['name' => '', 'time_in' => '', 'time_out' => ''];
    foreach ($valueLines as $line) {
        $text = (string) $line['text'];
        $xc = (float) $line['x_center'];

        if (document_ai_dad_is_date_text($text) && $xc >= $dateRightMinX) {
            $cleanDate = document_ai_sanitize_dad_date($text);
            if ($cleanDate !== '') {
                $dates[] = $cleanDate;
            }
            continue;
        }

        if (document_ai_dad_is_time_text($text)) {
            $time = document_ai_sanitize_dad_time($text);
            if ($time === '') {
                continue;
            }
            $distIn = abs($xc - $timeInColX);
            $distOut = abs($xc - $timeOutColX);
            if ($distOut < $distIn) {
                $currentRow['time_out'] = $time;
            } else {
                $currentRow['time_in'] = $time;
            }
            continue;
        }

        if ($xc < $dateRightMinX && !document_ai_dad_is_date_text($text)) {
            $name = document_ai_sanitize_dad_name($text);
            if ($name !== '') {
                if ($currentRow['name'] !== '' || $currentRow['time_in'] !== '' || $currentRow['time_out'] !== '') {
                    $attendanceRows[] = $currentRow;
                    $currentRow = ['name' => '', 'time_in' => '', 'time_out' => ''];
                }
                $currentRow['name'] = $name;
            }
        }
    }

    if ($currentRow['name'] !== '' || $currentRow['time_in'] !== '' || $currentRow['time_out'] !== '') {
        $attendanceRows[] = $currentRow;
    }

    foreach ($formFields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = document_ai_dad_field_key((string) ($field['label'] ?? ''));
        $value = trim((string) ($field['value'] ?? ''));
        if ($key === null || $value === '' || document_ai_dad_is_boilerplate($value)) {
            continue;
        }
        if ($key === 'post') {
            $cleanPost = document_ai_sanitize_dad_post($value);
            if ($cleanPost !== '' && ($post === '' || strlen($cleanPost) > strlen($post))) {
                $post = $cleanPost;
            }
        } elseif ($key === 'date') {
            $dates[] = document_ai_sanitize_dad_date($value);
        } elseif ($key === 'name') {
            $attendanceRows[] = [
                'name' => document_ai_sanitize_dad_name($value),
                'time_in' => '',
                'time_out' => '',
            ];
        } elseif ($key === 'time_in' && $attendanceRows !== []) {
            $attendanceRows[count($attendanceRows) - 1]['time_in'] = document_ai_sanitize_dad_time($value);
        } elseif ($key === 'time_out' && $attendanceRows !== []) {
            $attendanceRows[count($attendanceRows) - 1]['time_out'] = document_ai_sanitize_dad_time($value);
        }
    }

    $dates = array_values(array_unique(array_filter($dates)));

    return [
        'post' => $post,
        'dates' => $dates,
        'attendance_rows' => $attendanceRows,
    ];
}

/**
 * @param array{post?: string, dates?: list<string>, attendance_rows?: list<array<string, string>>} $spatial
 * @param array<string, mixed> $textParsed
 * @return array<string, mixed>
 */
function document_ai_dad_merge_spatial(array $spatial, array $textParsed): array
{
    $post = document_ai_sanitize_dad_post(trim((string) ($spatial['post'] ?? '')));
    $textPost = document_ai_sanitize_dad_post((string) ($textParsed['post'] ?? ''));
    if ($post === '' || ($textPost !== '' && strlen($textPost) > strlen($post))) {
        $post = $textPost;
    }

    $dates = is_array($spatial['dates'] ?? null) ? $spatial['dates'] : [];
    if ($dates === []) {
        $dates = is_array($textParsed['dates'] ?? null) ? $textParsed['dates'] : [];
    }

    $rows = is_array($spatial['attendance_rows'] ?? null) ? $spatial['attendance_rows'] : [];
    if ($rows === []) {
        $rows = is_array($textParsed['attendance_rows'] ?? null) ? $textParsed['attendance_rows'] : [];
    }

    $textParsed['post'] = $post;
    $textParsed['dates'] = $dates;
    $textParsed['attendance_rows'] = $rows;

    return $textParsed;
}

/**
 * @param array<string, mixed> $row
 * @return array{name: string, am_time_in: string, am_time_out: string, pm_time_in: string, pm_time_out: string, time_in: string, time_out: string}
 */
function document_ai_dad_normalize_attendance_row(array $row): array
{
    $name = document_ai_sanitize_dad_name((string) ($row['name'] ?? ''));
    $amIn = document_ai_sanitize_dad_time((string) ($row['am_time_in'] ?? ''));
    $amOut = document_ai_sanitize_dad_time((string) ($row['am_time_out'] ?? ''));
    $pmIn = document_ai_sanitize_dad_time((string) ($row['pm_time_in'] ?? ''));
    $pmOut = document_ai_sanitize_dad_time((string) ($row['pm_time_out'] ?? ''));
    $timeIn = document_ai_sanitize_dad_time((string) ($row['time_in'] ?? ''));
    $timeOut = document_ai_sanitize_dad_time((string) ($row['time_out'] ?? ''));

    if ($amIn === '' && $timeIn !== '' && !document_ai_dad_is_time_text($name)) {
        $amIn = $timeIn;
    }
    if ($pmOut === '' && $timeOut !== '' && $amOut === '' && $pmIn === '') {
        $pmOut = $timeOut;
    } elseif ($amOut === '' && $timeOut !== '' && $pmIn === '' && $pmOut === '') {
        $amOut = $timeOut;
    }

    if ($timeIn === '') {
        $timeIn = $amIn !== '' ? $amIn : $pmIn;
    }
    if ($timeOut === '') {
        $timeOut = $pmOut !== '' ? $pmOut : $amOut;
    }

    return [
        'name' => $name,
        'am_time_in' => $amIn,
        'am_time_out' => $amOut,
        'pm_time_in' => $pmIn,
        'pm_time_out' => $pmOut,
        'time_in' => $timeIn,
        'time_out' => $timeOut,
    ];
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array{name: string, am_time_in: string, am_time_out: string, pm_time_in: string, pm_time_out: string, time_in: string, time_out: string}>
 */
function document_ai_dad_sanitize_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $normalized = document_ai_dad_normalize_attendance_row($row);
        if (
            $normalized['name'] === ''
            && $normalized['am_time_in'] === ''
            && $normalized['am_time_out'] === ''
            && $normalized['pm_time_in'] === ''
            && $normalized['pm_time_out'] === ''
        ) {
            continue;
        }
        $out[] = $normalized;
    }

    return $out;
}

/**
 * @return array<string, mixed>
 */
function document_ai_parse_by_template(string $text, string $reportType): array
{
    $normalized = preg_replace("/\r\n?/", "\n", $text) ?? $text;
    $upper = strtoupper($normalized);

    if (
        $reportType === 'Incident Report'
        || $reportType === 'Incident'
        || $reportType === 'Post incident'
        || str_contains($upper, 'INCIDENT REPORT')
    ) {
        return document_ai_parse_incident_report($normalized);
    }

    if (
        in_array($reportType, ['Daily Time Record', 'Daily Attendance Document'], true)
        || str_contains($upper, 'DAILY TIME RECORD')
        || str_contains($upper, 'DAILY ATTENDANCE')
        || str_contains($upper, 'TIME RECORD')
    ) {
        return document_ai_parse_dad_report($normalized);
    }

    return ['template' => 'generic', 'notes' => trim($normalized)];
}

/**
 * Printed notice on incident/DAD forms — never part of handwritten content.
 */
function document_ai_incident_boilerplate_phrase_pattern(): string
{
    return '/\bthis\s+document\s+is\s+to\s+be\s+processed\s+di[gt]it(?:ally|rally)\b[^.\n]*(?:\.\s*)?(?:please\s+write\s+in\s+capitalized\s*\/?\s*big\s+letters)?\.?/iu';
}

function document_ai_incident_strip_boilerplate_phrases(string $text): string
{
    $text = trim(preg_replace(document_ai_incident_boilerplate_phrase_pattern(), ' ', $text) ?? $text);
    $text = trim(preg_replace('/\bplease\s+write\s+in\s+capitalized\s*\/?\s*big\s+letters\.?\b/iu', ' ', $text) ?? $text);

    return trim(preg_replace('/\s{2,}/u', ' ', $text) ?? $text);
}

function document_ai_incident_is_boilerplate(string $text): bool
{
    if (document_ai_dad_is_boilerplate($text)) {
        return true;
    }

    if (document_ai_incident_is_printed_label_text($text)) {
        return true;
    }

    $upper = strtoupper(trim($text));
    if ($upper === '') {
        return true;
    }

    $footerLabels = [
        'CONFIRMATION BY',
        'HEAD GUARD',
        'HEADGUARD',
        'SIGNATURE',
        'ACKNOWLEDGED BY',
    ];
    foreach ($footerLabels as $label) {
        if (str_contains($upper, $label)) {
            return true;
        }
    }

    if (preg_match(document_ai_incident_boilerplate_phrase_pattern(), $text) === 1) {
        return true;
    }

    return preg_match('/\b(?:SECURITY\s+AND\s+INVESTIGATION|GOLDEN\s+Z)\b/iu', $text) === 1
        && !preg_match('/[a-z]{3,}/u', $text);
}

/**
 * Printed Oswald / template labels — not handwritten field values.
 */
function document_ai_incident_is_printed_label_text(string $text): bool
{
    $trimmed = trim($text);
    if ($trimmed === '') {
        return true;
    }

    $upper = strtoupper(trim(preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed));
    $labelOnly = [
        'INCIDENT REPORT',
        'INCIDENT DESCRIPTION',
        'ACTION TAKEN',
        'NAME',
        'NAME OF GUARD',
        'DATE',
        'POST',
    ];
    foreach ($labelOnly as $label) {
        if ($upper === $label || preg_match('/^' . preg_quote($label, '/') . '\s*:?\s*$/u', $upper) === 1) {
            return true;
        }
    }

    return preg_match('/^(?:INCIDENT\s+DESCRIPTION|ACTION\s+TAKEN|NAME(?:\s+OF\s+GUARD)?|POST|DATE)\s*:?\s*$/iu', $trimmed) === 1;
}

function document_ai_incident_strip_label_prefix(string $line, array $labels): string
{
    $line = trim($line);
    foreach ($labels as $label) {
        $stripped = trim((string) (preg_replace(
            '/.*\b' . preg_quote($label, '/') . '\s*:?\s*/iu',
            '',
            $line,
            1
        ) ?? $line));
        if ($stripped !== $line) {
            return $stripped;
        }
    }

    return $line;
}

/**
 * Join OCR line fragments into one readable sentence block (preserves paragraph breaks when intended).
 *
 * @param list<string> $lines
 */
function document_ai_incident_join_handwriting_lines(array $lines): string
{
    $parts = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts[] = $line;
    }

    if ($parts === []) {
        return '';
    }

    $joined = '';
    foreach ($parts as $i => $part) {
        if ($joined === '') {
            $joined = $part;
            continue;
        }
        $prevEndsSentence = preg_match('/[.!?…]["\']?\s*$/u', $joined) === 1;
        $startsLower = preg_match('/^[a-z(]/u', $part) === 1;
        if ($prevEndsSentence || (!$startsLower && strlen($part) > 2 && preg_match('/^[A-Z]/u', $part) === 1)) {
            $joined .= "\n" . $part;
        } else {
            $joined .= ' ' . $part;
        }
    }

    return trim(preg_replace('/\s{2,}/u', ' ', $joined) ?? $joined);
}

function document_ai_incident_fix_common_ocr(string $text): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if ($text === '') {
        return '';
    }

    $text = (string) (preg_replace('/\b(\w+)\s+\1\b/iu', '$1', $text) ?? $text);
    $text = (string) (preg_replace('/\s+([,.;:!?])/u', '$1', $text) ?? $text);
    $text = (string) (preg_replace('/(\d)\s*:\s*(\d{2})\s*(am|pm)\b/iu', '$1:$2 $3', $text) ?? $text);
    $text = (string) (preg_replace('/\bapproximatly\b/iu', 'approximately', $text) ?? $text);
    $text = (string) (preg_replace('/\bimmedately\b/iu', 'immediately', $text) ?? $text);
    $text = (string) (preg_replace('/\boccured\b/iu', 'occurred', $text) ?? $text);
    $text = (string) (preg_replace('/\bvisiter\b/iu', 'visitor', $text) ?? $text);
    $text = (string) (preg_replace('/\bentrence\b/iu', 'entrance', $text) ?? $text);
    $text = (string) (preg_replace('/\bfirs\s*1\b/iu', 'first', $text) ?? $text);
    $text = (string) (preg_replace('/\bfirs\s*t\b/iu', 'first', $text) ?? $text);
    $text = (string) (preg_replace('/\bCleanca\b/iu', 'cleaned', $text) ?? $text);
    $text = (string) (preg_replace('/\bmarke\s+h\b/iu', 'marked', $text) ?? $text);
    $text = (string) (preg_replace('/\bprovidec\b/iu', 'provided', $text) ?? $text);
    $text = (string) (preg_replace('/\bmately\b/iu', 'approximately', $text) ?? $text);
    $text = (string) (preg_replace('/\bimme\b/iu', 'immediately', $text) ?? $text);
    $text = (string) (preg_replace('/\s*\+\s*/u', ' ', $text) ?? $text);
    $text = (string) (preg_replace('/\bHEAD\s*GUAR\b/iu', '', $text) ?? $text);
    $text = (string) (preg_replace('/\bUNA\s+THORIZED\b/iu', 'UNAUTHORIZED', $text) ?? $text);
    $text = (string) (preg_replace('/\bIAIDENTIFICATION\b/iu', 'IDENTIFICATION', $text) ?? $text);
    $text = (string) (preg_replace('/\bIA\s+IDENTIFICATION\b/iu', 'IDENTIFICATION', $text) ?? $text);
    $text = (string) (preg_replace('/\bPROPER\s+I\s+IDENTIFICATION\b/iu', 'PROPER IDENTIFICATION', $text) ?? $text);
    $text = (string) (preg_replace('/\bPREMISES\b/iu', 'PREMISES', $text) ?? $text);

    if (preg_match('/^\p{Ll}/u', $text)) {
        $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    if ($text !== '' && !preg_match('/[.!?…]$/u', $text) && mb_strlen($text) > 40) {
        $text .= '.';
    }

    return trim($text);
}

/**
 * Polish narrative handwriting: sanitize → join lines → light OCR correction.
 */
function document_ai_polish_incident_handwriting(string $text): string
{
    $text = document_ai_sanitize_incident_handwriting($text);
    if ($text === '') {
        return '';
    }

    $lines = preg_split('/\n+/u', $text) ?: [];
    $block = document_ai_incident_join_handwriting_lines($lines);

    return document_ai_incident_fix_common_ocr($block);
}

/**
 * @return array{name_of_guard: string, post: string, incident_description: string, action_taken: string}
 */
function document_ai_incident_extraction_json(array $structured): array
{
    $post = document_ai_sanitize_dad_post((string) ($structured['post'] ?? ''));
    if ($post !== '' && document_ai_incident_is_contaminated_post($post)) {
        $post = '';
    }

    return [
        'name_of_guard' => document_ai_sanitize_incident_name((string) ($structured['name'] ?? '')),
        'post' => $post,
        'incident_description' => document_ai_polish_incident_handwriting(
            (string) ($structured['incident_description'] ?? '')
        ),
        'action_taken' => document_ai_polish_incident_handwriting(
            (string) ($structured['action_taken'] ?? '')
        ),
    ];
}

function document_ai_incident_is_date_text(string $text): bool
{
    $t = trim($text);
    if ($t === '') {
        return false;
    }

    if (document_ai_dad_is_date_text($t)) {
        return true;
    }

    return preg_match(
        '/\b(?:\d{1,2}[\s.\-\/]+\d{1,2}[\s.\-\/]+\d{2,4}|\d{1,2}[\s.\-\/]+(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)[A-Z]*[\s.\-\/]+\d{2,4}|(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)[A-Z]*[\s.\-\/]+\d{1,2}(?:[\s.\-\/]+\d{2,4})?)\b/iu',
        $t
    ) === 1;
}

function document_ai_sanitize_incident_date(string $date): string
{
    $date = document_ai_incident_strip_boilerplate_phrases(trim($date));
    if ($date === '' || document_ai_incident_is_boilerplate($date)) {
        return '';
    }

    $date = trim(preg_replace('/^DATE\s*:?\s*/iu', '', $date) ?? $date);
    if ($date === '') {
        return '';
    }

    if (preg_match(
        '/\b((?:JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:TEMBER)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?)\s*[\s,.\-\/]*\d{1,2}(?:\s*[\s,.\-\/]*\d{2,4})?|\d{1,2}\s*[\s,.\-\/]+\d{1,2}\s*[\s,.\-\/]+\d{2,4}|\d{1,2}\s*[\s,.\-\/]+\d{1,2}\s*[\s,.\-\/]+\d{2})\b/iu',
        $date,
        $m
    )) {
        return trim((string) $m[1]);
    }

    return document_ai_incident_is_date_text($date) ? $date : '';
}

function document_ai_incident_field_key(string $label): ?string
{
    $normalized = strtoupper(trim(preg_replace('/[\s:._\-]+/u', ' ', $label) ?? $label));
    $normalized = trim(preg_replace('/\s+/u', ' ', $normalized));

    return match (true) {
        $normalized === 'NAME'
            || str_starts_with($normalized, 'NAME ')
            || str_contains($normalized, 'NAME OF GUARD') => 'name',
        $normalized === 'POST' || str_starts_with($normalized, 'POST ') => 'post',
        $normalized === 'DATE' || str_starts_with($normalized, 'DATE ') => 'date',
        str_contains($normalized, 'INCIDENT DESCRIPTION') => 'incident_description',
        str_contains($normalized, 'ACTION TAKEN') => 'action_taken',
        default => null,
    };
}

function document_ai_incident_looks_like_name(string $name): bool
{
    $name = trim($name);
    if ($name === '' || strlen($name) < 2) {
        return false;
    }
    if (document_ai_incident_is_boilerplate($name) || document_ai_incident_is_date_text($name)) {
        return false;
    }
    if (!preg_match('/\p{L}/u', $name)) {
        return false;
    }

    if (preg_match('/\b(?:INCIDENT\s+DESCRIPTION|ACTION\s+TAKEN|ATTENDANCE|DOCUMENT|CONFIRMATION|HEAD\s*GUARD)\b/iu', $name) === 1) {
        return false;
    }
    if (preg_match('/\b(?:ENT\s+DESCRIPTION|OF\s+GUARD|DESCRIPTION|WARNING\s+SIGN)\b/iu', $name) === 1) {
        return false;
    }
    if (preg_match('/\b\d{1,2}\s*:\s*\d{2}\s*(?:am|pm)?\b/iu', $name) === 1) {
        return false;
    }
    if (preg_match('/\b(?:approximately|entrance|cleaned|warning|first\s*aid|provided|floor)\b/iu', $name) === 1) {
        return false;
    }

    return true;
}

function document_ai_sanitize_incident_name(string $name): string
{
    $name = document_ai_incident_strip_boilerplate_phrases(trim($name));
    if ($name === '' || document_ai_incident_is_boilerplate($name)) {
        return '';
    }

    $name = trim(preg_replace('/^NAME(?:\s+OF\s+GUARD)?\s*:?\s*/iu', '', $name) ?? $name);
    $name = trim(preg_replace('/\b(?:DATE|INCIDENT|ACTION|CONFIRMATION|HEAD\s*GUARD)\b.*$/iu', '', $name) ?? $name);
    $name = trim(preg_replace('/\b(?:PLEASE\s+WRITE|BIG\s+LETTERS|CAPITALIZED|PROCESSED\s+DIGITALLY)\b.*$/iu', '', $name) ?? $name);
    $name = trim(preg_replace('/\s{2,}/u', ' ', $name) ?? $name);

    if (strlen($name) > 80) {
        return '';
    }

    return document_ai_incident_looks_like_name($name) ? $name : '';
}

/**
 * Read handwritten name from the incident form NAME: field (same line or following lines).
 */
function document_ai_extract_incident_name_from_text(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $best = '';

    if (preg_match_all('/\b(?:NAME(?:\s+OF\s+GUARD)?)\s*:+\s*([^\n]+)/iu', $text, $inlineMatches)) {
        foreach ($inlineMatches[1] as $chunk) {
            $candidate = document_ai_sanitize_incident_name(trim((string) $chunk));
            if ($candidate !== '' && strlen($candidate) > strlen($best)) {
                $best = $candidate;
            }
        }
    }

    if (preg_match(
        '/\bNAME\s*:?\s*(.*?)(?=\bDATE\s*:|\bINCIDENT\s+DESCRIPTION|\bACTION\s+TAKEN)/isu',
        $text,
        $blockMatch
    )) {
        $block = trim(preg_replace('/\s*\n\s*/u', ' ', (string) ($blockMatch[1] ?? '')) ?? '');
        $candidate = document_ai_sanitize_incident_name($block);
        if ($candidate !== '' && strlen($candidate) > strlen($best)) {
            $best = $candidate;
        }
    }

    if (preg_match(
        '/\bNAME\s*:?\s*(?:[_\-\s\.]+)?\s*[\n]+\s*([^\n]+)/iu',
        $text,
        $nextLineMatch
    )) {
        $candidate = document_ai_sanitize_incident_name(trim((string) ($nextLineMatch[1] ?? '')));
        if ($candidate !== '' && strlen($candidate) > strlen($best)) {
            $best = $candidate;
        }
    }

    if (preg_match(
        '/\bNAME\s*:?\s*(?:[_\-\s\.]+)?\s*[\n]+\s*([^\n]+(?:\n[^\n]+)?)(?=\s*[\n]+\s*DATE\s*:)/iu',
        $text,
        $multiLineMatch
    )) {
        $block = trim(preg_replace('/\s*\n\s*/u', ' ', (string) ($multiLineMatch[1] ?? '')) ?? '');
        $candidate = document_ai_sanitize_incident_name($block);
        if ($candidate !== '' && strlen($candidate) > strlen($best)) {
            $best = $candidate;
        }
    }

    return $best;
}

/**
 * @param list<array{label: string, value: string}> $formFields
 * @param array{name?: string, post?: string, date?: string, incident_description?: string, action_taken?: string} $seed
 * @return array{name: string, post: string, date: string, incident_description: string, action_taken: string}
 */
function document_ai_incident_merge_form_fields(array $formFields, array $seed): array
{
    $name = trim((string) ($seed['name'] ?? ''));
    $post = trim((string) ($seed['post'] ?? ''));
    $date = trim((string) ($seed['date'] ?? ''));
    $incident = trim((string) ($seed['incident_description'] ?? ''));
    $action = trim((string) ($seed['action_taken'] ?? ''));

    foreach ($formFields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = document_ai_incident_field_key((string) ($field['label'] ?? ''));
        $value = trim((string) ($field['value'] ?? ''));
        if ($key === null || $value === '') {
            continue;
        }
        if ($key === 'name') {
            $clean = document_ai_sanitize_incident_name($value);
            if ($clean !== '' && ($name === '' || strlen($clean) > strlen($name))) {
                $name = $clean;
            }
        } elseif ($key === 'post') {
            $clean = document_ai_sanitize_dad_post($value);
            if ($clean !== '' && ($post === '' || strlen($clean) > strlen($post))) {
                $post = $clean;
            }
        } elseif ($key === 'date') {
            $clean = document_ai_sanitize_incident_date($value);
            if ($clean !== '') {
                $date = $clean;
            }
        } elseif ($key === 'incident_description') {
            $clean = document_ai_sanitize_incident_handwriting($value);
            if ($clean !== '' && ($incident === '' || strlen($clean) > strlen($incident))) {
                $incident = $clean;
            }
        } elseif ($key === 'action_taken') {
            $clean = document_ai_sanitize_incident_handwriting($value);
            if ($clean !== '' && ($action === '' || strlen($clean) > strlen($action))) {
                $action = $clean;
            }
        }
    }

    return [
        'name' => $name,
        'post' => $post,
        'date' => $date,
        'incident_description' => $incident,
        'action_taken' => $action,
    ];
}

/**
 * Keep handwritten blocks as scanned (line breaks); drop printed template lines only.
 */
function document_ai_sanitize_incident_handwriting(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = preg_split('/\n+/u', $text) ?: [];
    $kept = [];

    foreach ($lines as $line) {
        $line = document_ai_incident_strip_boilerplate_phrases(trim($line));
        if ($line === '' || document_ai_incident_is_boilerplate($line) || document_ai_incident_is_printed_label_text($line)) {
            continue;
        }
        if (preg_match('/^\s*(?:NAME|DATE|POST)(?:\s+OF\s+GUARD)?\s*:+/iu', $line) === 1) {
            continue;
        }
        $line = trim(preg_replace('/^(?:INCIDENT\s+DESCRIPTION|ACTION\s+TAKEN)\s*:?\s*/iu', '', $line) ?? $line);
        if ($line !== '' && !document_ai_incident_is_boilerplate($line) && !document_ai_incident_is_printed_label_text($line)) {
            $kept[] = $line;
        }
    }

    return trim(implode("\n", $kept));
}

/**
 * @param list<string> $startLabels
 * @param list<string> $stopLabels
 */
function document_ai_incident_extract_lines_for_section(string $text, array $startLabels, array $stopLabels): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = preg_split('/\n+/u', $text) ?: [];
    $started = false;
    $kept = [];

    foreach ($lines as $line) {
        $raw = trim($line);
        if ($raw === '') {
            continue;
        }

        $upper = strtoupper($raw);
        if (!$started) {
            foreach ($startLabels as $label) {
                if (!str_contains($upper, strtoupper($label))) {
                    continue;
                }
                $started = true;
                $remainder = document_ai_incident_strip_label_prefix($raw, $startLabels);
                $remainder = document_ai_sanitize_incident_handwriting($remainder);
                if ($remainder !== '') {
                    $kept[] = $remainder;
                }
                continue 2;
            }
            continue;
        }

        foreach ($stopLabels as $stop) {
            if (str_contains($upper, strtoupper($stop))) {
                return trim(implode("\n", $kept));
            }
        }

        if (document_ai_incident_is_boilerplate($raw) || document_ai_incident_is_printed_label_text($raw)) {
            continue;
        }
        if (preg_match('/^\s*(?:NAME|DATE|POST|INCIDENT\s+REPORT)(?:\s+OF\s+GUARD)?\s*:?/iu', $raw) === 1) {
            continue;
        }

        $clean = document_ai_sanitize_incident_handwriting($raw);
        if ($clean !== '') {
            $kept[] = $clean;
        }
    }

    return trim(implode("\n", $kept));
}

/** @param list<string> $startLabels @param list<string> $endLabels */
function document_ai_incident_section_between(string $text, array $startLabels, array $endLabels): string
{
    $lineBased = document_ai_incident_extract_lines_for_section($text, $startLabels, $endLabels);
    if ($lineBased !== '') {
        return $lineBased;
    }

    $start = document_ai_label_pos($text, $startLabels);
    $end = document_ai_label_pos($text, $endLabels);
    if ($start === null) {
        return '';
    }
    $from = $start + strlen(document_ai_first_label($text, $startLabels));
    $slice = $end !== null && $end > $from ? substr($text, $from, $end - $from) : substr($text, $from);

    return document_ai_sanitize_incident_handwriting(trim((string) ($slice ?? '')));
}

/** @param list<string> $labels */
function document_ai_incident_section_after(string $text, array $labels): string
{
    $lineBased = document_ai_incident_extract_lines_for_section(
        $text,
        $labels,
        ['NAME', 'DATE', 'POST', 'INCIDENT REPORT', 'CONFIRMATION BY', 'HEAD GUARD']
    );
    if ($lineBased !== '') {
        return $lineBased;
    }

    $pos = document_ai_label_pos($text, $labels);
    if ($pos === null) {
        return '';
    }
    $from = $pos + strlen(document_ai_first_label($text, $labels));

    return document_ai_sanitize_incident_handwriting(trim(substr($text, $from) ?: ''));
}

/**
 * Handwritten fields per column: tokens sorted top-to-bottom within each box (not full-width lines).
 *
 * @return array{incident_description: string, action_taken: string, name: string, post: string, date: string}
 */
function document_ai_parse_incident_spatial(Document $document, string $rawText): array
{
    $lines = document_ai_collect_page_lines($document, $rawText);
    $agencyYMax = 0.0;
    $sectionYMin = 1.0;
    $splitX = 0.5;
    $descLabelX = null;
    $actionLabelX = null;
    $descLabelYMax = null;
    $actionLabelYMax = null;
    $name = document_ai_extract_incident_name_from_text($rawText);
    $post = document_ai_extract_dad_post_from_text($rawText);
    if ($post === '') {
        $post = document_ai_extract_dad_post_from_lines($lines);
    }
    $date = '';
    $nameLabelYMax = null;
    $footerYMin = null;

    foreach ($lines as $line) {
        $text = (string) $line['text'];
        $upper = strtoupper(trim($text));
        if (
            str_contains($upper, 'GOLDEN')
            && (str_contains($upper, 'SECURITY') || str_contains($upper, 'AGENCY') || str_contains($upper, 'INVESTIGATION'))
        ) {
            $agencyYMax = max($agencyYMax, (float) $line['y_max']);
        }
        if (preg_match(document_ai_incident_boilerplate_phrase_pattern(), $text) === 1) {
            $footerYMin = min($footerYMin ?? 1.0, (float) $line['y_min']);
        }
    }

    foreach ($lines as $line) {
        $text = (string) $line['text'];
        $upper = strtoupper(trim($text));
        if (str_contains($upper, 'INCIDENT DESCRIPTION')) {
            $descLabelX = (float) $line['x_center'];
            $sectionYMin = min($sectionYMin, (float) $line['y_min']);
            $descLabelYMax = max($descLabelYMax ?? 0.0, (float) $line['y_max']);
        }
        if (str_contains($upper, 'ACTION TAKEN')) {
            $actionLabelX = (float) $line['x_center'];
            $sectionYMin = min($sectionYMin, (float) $line['y_min']);
            $actionLabelYMax = max($actionLabelYMax ?? 0.0, (float) $line['y_max']);
        }
        if (preg_match('/\b(?:NAME|NAME\s+OF\s+GUARD)\s*:+/iu', $text)) {
            $nameLabelYMax = max($nameLabelYMax ?? 0.0, (float) $line['y_max']);
        }
        if (preg_match('/\bDATE\s*:+/iu', $text)) {
            $nameLabelYMax = max($nameLabelYMax ?? 0.0, (float) $line['y_max']);
        }
    }

    if ($descLabelX !== null && $actionLabelX !== null) {
        $splitX = ($descLabelX + $actionLabelX) / 2;
    } elseif ($descLabelX !== null) {
        $splitX = $descLabelX + 0.18;
    } elseif ($actionLabelX !== null) {
        $splitX = $actionLabelX - 0.18;
    }

    $headerYMax = $sectionYMin < 1.0 ? max($agencyYMax + 0.04, $sectionYMin - 0.02) : 0.58;
    $descBodyYMin = $descLabelYMax !== null ? $descLabelYMax + 0.012 : 0.0;
    $actionBodyYMin = $actionLabelYMax !== null ? $actionLabelYMax + 0.012 : $descBodyYMin;
    $bodyYMax = $footerYMin !== null && $footerYMin < 1.0 ? $footerYMin - 0.01 : 1.0;

    foreach ($lines as $line) {
        $text = (string) $line['text'];
        $yc = (float) $line['y_center'];
        if ($agencyYMax > 0 && $yc <= $agencyYMax + 0.02) {
            continue;
        }
        if ($sectionYMin < 1.0 && $yc >= $headerYMax) {
            continue;
        }

        if (preg_match('/\b(?:NAME|NAME\s+OF\s+GUARD)\s*:+\s*(.+)$/iu', $text, $m)) {
            $candidate = document_ai_sanitize_incident_name(trim((string) $m[1]));
            if ($candidate !== '' && strlen($candidate) > strlen($name)) {
                $name = $candidate;
            }
            continue;
        }

        if (preg_match('/\bPOST\s*:+\s*(.+)$/iu', $text, $m)) {
            $candidate = document_ai_sanitize_dad_post(trim((string) $m[1]));
            if ($candidate !== '' && strlen($candidate) > strlen($post)) {
                $post = $candidate;
            }
            continue;
        }

        if (preg_match('/\bDATE\s*:+\s*(.+)$/iu', $text, $m)) {
            $candidate = document_ai_sanitize_incident_date(trim((string) $m[1]));
            if ($candidate !== '') {
                $date = $candidate;
            }
            continue;
        }

        if (document_ai_incident_is_date_text($text) && $date === '' && $yc < $headerYMax) {
            $date = document_ai_sanitize_incident_date($text);
            continue;
        }

        if ($nameLabelYMax !== null && $yc > $nameLabelYMax && $yc < $nameLabelYMax + 0.1) {
            if (preg_match('/\b(?:NAME|DATE|POST|INCIDENT|ACTION)\b\s*:?/iu', $text)) {
                continue;
            }
            $candidate = document_ai_sanitize_incident_name($text);
            if ($candidate !== '' && strlen($candidate) > strlen($name)) {
                $name = $candidate;
            }
        }
    }

    $descXMin = 0.0;
    $descXMax = $splitX - 0.015;
    $actionXMin = $splitX + 0.015;
    $actionXMax = 1.0;

    $tokens = document_ai_collect_page_tokens($document, $rawText);
    if ($tokens === []) {
        $tokens = document_ai_incident_tokens_from_lines($lines, $splitX);
    }

    $descTokens = [];
    $actionTokens = [];
    foreach ($tokens as $token) {
        $yc = (float) $token['y_center'];
        if ($agencyYMax > 0 && $yc <= $agencyYMax + 0.015) {
            continue;
        }
        if ($nameLabelYMax !== null && $yc <= $nameLabelYMax + 0.008) {
            continue;
        }
        if ($yc > $bodyYMax) {
            continue;
        }

        $xc = (float) $token['x_center'];
        if ($xc <= $splitX - 0.015 && $yc >= $descBodyYMin) {
            $descTokens[] = $token;
        }
        if ($xc >= $splitX + 0.015 && $yc >= $actionBodyYMin) {
            $actionTokens[] = $token;
        }
    }

    $incidentDescription = document_ai_incident_compose_vertical_column_text(
        $descTokens,
        $descXMin,
        $descXMax,
        $descBodyYMin,
        $bodyYMax
    );
    $actionTaken = document_ai_incident_compose_vertical_column_text(
        $actionTokens,
        $actionXMin,
        $actionXMax,
        $actionBodyYMin,
        $bodyYMax
    );

    return [
        'incident_description' => $incidentDescription,
        'action_taken' => $actionTaken,
        'name' => $name,
        'post' => $post,
        'date' => $date,
    ];
}

/**
 * @param array<string, mixed> $structured
 * @return array<string, mixed>
 */
function document_ai_enrich_incident_structured(array $structured): array
{
    if (($structured['template'] ?? '') !== 'incident_report') {
        return $structured;
    }

    $formFields = is_array($structured['form_fields'] ?? null) ? $structured['form_fields'] : [];
    $merged = document_ai_incident_merge_form_fields($formFields, [
        'name' => (string) ($structured['name'] ?? ''),
        'post' => (string) ($structured['post'] ?? ''),
        'date' => (string) ($structured['date'] ?? ''),
        'incident_description' => (string) ($structured['incident_description'] ?? ''),
        'action_taken' => (string) ($structured['action_taken'] ?? ''),
    ]);

    $structured['name'] = document_ai_sanitize_incident_name((string) $merged['name']);
    if ($structured['name'] === '') {
        $structured['name'] = document_ai_extract_incident_name_from_text(
            (string) ($structured['raw'] ?? '')
        );
    }
    $structured['post'] = document_ai_sanitize_dad_post((string) $merged['post']);
    if ($structured['post'] === '') {
        $structured['post'] = document_ai_sanitize_dad_post(
            document_ai_extract_dad_post_from_text((string) ($structured['raw'] ?? ''))
        );
    }
    $structured['date'] = document_ai_sanitize_incident_date((string) $merged['date']);
    $structured['incident_description'] = document_ai_polish_incident_handwriting(
        (string) ($merged['incident_description'] ?? $structured['incident_description'] ?? '')
    );
    $structured['action_taken'] = document_ai_polish_incident_handwriting(
        (string) ($merged['action_taken'] ?? $structured['action_taken'] ?? '')
    );
    $structured['extraction'] = document_ai_incident_extraction_json($structured);
    $structured['display_template'] = 'as_is_two_column';

    return $structured;
}

/**
 * @return array<string, mixed>
 */
function document_ai_parse_incident_report(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $name = document_ai_extract_incident_name_from_text($text);
    $post = document_ai_extract_dad_post_from_text($text);
    $date = document_ai_sanitize_incident_date(document_ai_match_after_label($text, ['Date:', 'DATE:']));
    if ($date === '' && preg_match_all('/\bDATE\s*:?\s*([^\n]+)/iu', $text, $dateMatches)) {
        foreach ($dateMatches[1] as $rawDate) {
            $candidate = document_ai_sanitize_incident_date(trim((string) $rawDate));
            if ($candidate !== '') {
                $date = $candidate;
                break;
            }
        }
    }

    $incident = document_ai_incident_section_between(
        $text,
        ['INCIDENT DESCRIPTION', 'Incident Description'],
        ['ACTION TAKEN', 'Action Taken']
    );
    $action = document_ai_incident_section_after($text, ['ACTION TAKEN', 'Action Taken']);

    return [
        'template' => 'incident_report',
        'name' => $name,
        'post' => $post,
        'date' => $date,
        'incident_description' => $incident,
        'action_taken' => $action,
    ];
}

/**
 * Map OCR / form labels to DAD sheet field keys (matches printed template).
 */
function document_ai_dad_field_key(string $label): ?string
{
    $normalized = strtoupper(trim(preg_replace('/[\s:._\-]+/u', ' ', $label) ?? $label));
    $normalized = trim(preg_replace('/\s+/u', ' ', $normalized));

    return match (true) {
        $normalized === 'POST'
            || str_starts_with($normalized, 'POST ')
            || preg_match('/^POST\s*:?$/u', $normalized) === 1 => 'post',
        $normalized === 'DATE' || str_starts_with($normalized, 'DATE ') => 'date',
        $normalized === 'NAME' || str_starts_with($normalized, 'NAME ') => 'name',
        str_contains($normalized, 'TIME IN') || $normalized === 'TIMEIN' => 'time_in',
        str_contains($normalized, 'TIME OUT') || $normalized === 'TIMEOUT' => 'time_out',
        default => null,
    };
}

/**
 * @param list<array{label: string, value: string}> $formFields
 * @param array{post?: string, dates?: list<string>, attendance_rows?: list<array<string, string>>} $seed
 * @return array{post: string, dates: list<string>, attendance_rows: list<array{name: string, time_in: string, time_out: string}>}
 */
function document_ai_dad_merge_form_fields(array $formFields, array $seed): array
{
    $post = trim((string) ($seed['post'] ?? ''));
    $dates = is_array($seed['dates'] ?? null) ? $seed['dates'] : [];
    $rows = is_array($seed['attendance_rows'] ?? null) ? $seed['attendance_rows'] : [];
    $currentRow = ['name' => '', 'time_in' => '', 'time_out' => ''];

    foreach ($formFields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = document_ai_dad_field_key((string) ($field['label'] ?? ''));
        $value = trim((string) ($field['value'] ?? ''));
        if ($key === null || $value === '') {
            continue;
        }
        if ($key === 'post') {
            $post = document_ai_sanitize_dad_post($value);
            continue;
        }
        if ($key === 'date') {
            $cleanDate = document_ai_sanitize_dad_date($value);
            if ($cleanDate !== '') {
                $dates[] = $cleanDate;
            }
            continue;
        }
        if ($key === 'name') {
            if ($currentRow['name'] !== '' || $currentRow['time_in'] !== '' || $currentRow['time_out'] !== '') {
                $rows[] = $currentRow;
                $currentRow = ['name' => '', 'time_in' => '', 'time_out' => ''];
            }
            $currentRow['name'] = document_ai_sanitize_dad_name($value);
            continue;
        }
        if ($key === 'time_in') {
            $currentRow['time_in'] = document_ai_sanitize_dad_time($value);
            continue;
        }
        if ($key === 'time_out') {
            $currentRow['time_out'] = document_ai_sanitize_dad_time($value);
        }
    }

    if ($currentRow['name'] !== '' || $currentRow['time_in'] !== '' || $currentRow['time_out'] !== '') {
        $rows[] = $currentRow;
    }

    $dates = array_values(array_unique(array_filter(array_map(
        static fn ($d): string => trim((string) $d),
        $dates
    ))));

    return ['post' => $post, 'dates' => $dates, 'attendance_rows' => $rows];
}

/**
 * @param list<list<list<string>>> $tables
 * @param list<array{name: string, time_in: string, time_out: string}> $existing
 * @return list<array{name: string, time_in: string, time_out: string}>
 */
function document_ai_dad_rows_from_tables(array $tables, array $existing): array
{
    $rows = $existing;

    foreach ($tables as $matrix) {
        if (!is_array($matrix) || $matrix === []) {
            continue;
        }
        $header = $matrix[0] ?? [];
        if (!is_array($header)) {
            continue;
        }
        $colMap = [];
        foreach ($header as $colIdx => $cell) {
            $key = document_ai_dad_field_key((string) $cell);
            if ($key !== null && in_array($key, ['name', 'time_in', 'time_out', 'date'], true)) {
                $colMap[$key] = (int) $colIdx;
            }
        }
        if ($colMap === []) {
            continue;
        }
        $bodyStart = isset($colMap['name']) || isset($colMap['time_in']) ? 1 : 0;
        for ($r = $bodyStart, $rMax = count($matrix); $r < $rMax; $r++) {
            $line = $matrix[$r] ?? [];
            if (!is_array($line)) {
                continue;
            }
            $row = ['name' => '', 'time_in' => '', 'time_out' => ''];
            foreach ($colMap as $key => $idx) {
                if ($key === 'date') {
                    continue;
                }
                $row[$key] = trim((string) ($line[$idx] ?? ''));
            }
            if ($row['name'] !== '' || $row['time_in'] !== '' || $row['time_out'] !== '') {
                $rows[] = $row;
            }
        }
    }

    return $rows;
}

/**
 * Display-only OCR fields shown in admin/guard UI (attendance row columns only).
 *
 * @param list<string> $dates
 * @param list<array{name: string, time_in: string, time_out: string}> $attendanceRows
 * @return list<array{key: string, label: string, value: string}>
 */
function document_ai_dad_build_display_fields(string $post, array $dates, array $attendanceRows): array
{
    unset($post, $dates);

    $fields = [];
    $rowIndex = 0;
    foreach ($attendanceRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rowIndex++;
        $normalized = document_ai_dad_normalize_attendance_row($row);
        $name = $normalized['name'];
        if (
            $name === ''
            && $normalized['am_time_in'] === ''
            && $normalized['am_time_out'] === ''
            && $normalized['pm_time_in'] === ''
            && $normalized['pm_time_out'] === ''
        ) {
            continue;
        }
        $suffix = count($attendanceRows) > 1 ? ' (' . $rowIndex . ')' : '';
        if ($name !== '') {
            $fields[] = ['key' => 'name_' . $rowIndex, 'label' => 'NAME' . $suffix, 'value' => $name];
        }
        foreach (
            [
                'am_time_in' => 'AM TIME IN',
                'am_time_out' => 'AM TIME OUT',
                'pm_time_in' => 'PM TIME IN',
                'pm_time_out' => 'PM TIME OUT',
            ] as $key => $label
        ) {
            $value = $normalized[$key];
            if ($value !== '') {
                $fields[] = [
                    'key' => $key . '_' . $rowIndex,
                    'label' => $label . $suffix,
                    'value' => $value,
                ];
            }
        }
        if (
            $normalized['am_time_in'] === ''
            && $normalized['am_time_out'] === ''
            && $normalized['pm_time_in'] === ''
            && $normalized['pm_time_out'] === ''
        ) {
            if ($normalized['time_in'] !== '') {
                $fields[] = ['key' => 'time_in_' . $rowIndex, 'label' => 'TIME IN' . $suffix, 'value' => $normalized['time_in']];
            }
            if ($normalized['time_out'] !== '') {
                $fields[] = ['key' => 'time_out_' . $rowIndex, 'label' => 'TIME OUT' . $suffix, 'value' => $normalized['time_out']];
            }
        }
    }

    return $fields;
}

/**
 * Normalize DAD structured OCR to match the attendance sheet layout.
 *
 * @param array<string, mixed> $structured
 * @return array<string, mixed>
 */
function document_ai_enrich_dad_structured(array $structured): array
{
    if (($structured['template'] ?? '') !== 'daily_attendance') {
        return $structured;
    }

    $formFields = is_array($structured['form_fields'] ?? null) ? $structured['form_fields'] : [];
    $merged = document_ai_dad_merge_form_fields($formFields, [
        'post' => (string) ($structured['post'] ?? ''),
        'dates' => is_array($structured['dates'] ?? null) ? $structured['dates'] : [],
        'attendance_rows' => is_array($structured['attendance_rows'] ?? null) ? $structured['attendance_rows'] : [],
    ]);

    $rows = document_ai_dad_rows_from_tables(
        is_array($structured['tables'] ?? null) ? $structured['tables'] : [],
        $merged['attendance_rows']
    );

    $dates = array_values(array_unique(array_filter(array_map(
        static fn ($d): string => document_ai_sanitize_dad_date((string) $d),
        $merged['dates']
    ))));

    $structured['post'] = document_ai_sanitize_dad_post($merged['post']);
    $structured['dates'] = $dates;
    $structured['attendance_rows'] = document_ai_dad_sanitize_rows($rows);
    $structured['display_fields'] = document_ai_dad_build_display_fields(
        $structured['post'],
        $structured['dates'],
        $structured['attendance_rows']
    );

    return $structured;
}

/**
 * @param array<string, mixed> $structured
 * @return list<array{key: string, label: string, value: string}>
 */
function document_ai_dad_display_fields(array $structured): array
{
    if (($structured['template'] ?? '') !== 'daily_attendance') {
        return [];
    }

    $enriched = document_ai_enrich_dad_structured($structured);
    $fields = is_array($enriched['display_fields'] ?? null) ? $enriched['display_fields'] : [];

    return document_ai_dad_filter_name_time_display_fields($fields);
}

/**
 * @param list<array{key?: string, label: string, value: string}> $fields
 * @return list<array{key: string, label: string, value: string}>
 */
function document_ai_dad_filter_name_time_display_fields(array $fields): array
{
    $out = [];
    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $label = trim((string) ($field['label'] ?? ''));
        $base = preg_replace('/\s+\(\d+\)$/', '', $label);
        if (!in_array($base, ['NAME', 'TIME IN', 'TIME OUT'], true)) {
            continue;
        }
        $value = trim((string) ($field['value'] ?? ''));
        if ($value === '') {
            continue;
        }
        $out[] = [
            'key' => (string) ($field['key'] ?? strtolower(str_replace(' ', '_', $base))),
            'label' => $label,
            'value' => $value,
        ];
    }

    return $out;
}

/**
 * @return array<string, mixed>
 */
function document_ai_parse_dad_report(string $text): array
{
    $post = document_ai_extract_dad_post_from_text($text);
    if ($post === '') {
        $post = document_ai_sanitize_dad_post(document_ai_match_after_label($text, ['POST:', 'Post:', 'POST :', 'POST\t']));
    }

    $dates = [];
    if (preg_match_all('/\bDATE\s*:?\s*([^\n]+)/iu', $text, $m)) {
        $dates = array_values(array_filter(array_map('trim', $m[1])));
    }

    $name = document_ai_sanitize_dad_name(document_ai_match_after_label($text, ['NAME:', 'Name:', 'NAME :']));
    $timeIn = document_ai_sanitize_dad_time(document_ai_match_after_label($text, ['TIME IN:', 'TIME IN :', 'Time In:', 'TIME-IN:', 'TIME IN\t']));
    $timeOut = document_ai_sanitize_dad_time(document_ai_match_after_label($text, ['TIME OUT:', 'TIME OUT :', 'Time Out:', 'TIME-OUT:', 'TIME OUT\t']));

    $attendance = [];
    if ($name !== '' || $timeIn !== '' || $timeOut !== '') {
        $attendance[] = [
            'name' => $name,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
        ];
    }

    if ($attendance === [] && preg_match_all(
        '/\bNAME\s*:?\s*([^\n]+?)\s+(\d{1,2}(?::\d{2})?\s*(?:[AP]\.?M\.?)?)\s+(\d{1,2}(?::\d{2})?\s*(?:[AP]\.?M\.?)?)/iu',
        $text,
        $inlineRows,
        PREG_SET_ORDER
    )) {
        foreach ($inlineRows as $row) {
            $attendance[] = [
                'name' => document_ai_sanitize_dad_name(trim((string) $row[1])),
                'time_in' => document_ai_sanitize_dad_time(trim((string) $row[2])),
                'time_out' => document_ai_sanitize_dad_time(trim((string) $row[3])),
            ];
        }
    }

    if ($attendance === [] && preg_match_all(
        '/([A-Za-z][A-Za-z0-9\s\.\-\'",]+)\s+(\d{1,2}(?::\d{2})?\s*(?:[AP]\.?M\.?)?)\s+(\d{1,2}(?::\d{2})?\s*(?:[AP]\.?M\.?)?)/iu',
        $text,
        $rows,
        PREG_SET_ORDER
    )) {
        foreach ($rows as $row) {
            $candidate = trim((string) $row[1]);
            if (strlen($candidate) < 4 || preg_match('/^(DATE|POST|NAME|TIME|DAILY|ATTENDANCE|DOCUMENT)/i', $candidate)) {
                continue;
            }
            $attendance[] = [
                'name' => document_ai_sanitize_dad_name($candidate),
                'time_in' => document_ai_sanitize_dad_time(trim((string) $row[2])),
                'time_out' => document_ai_sanitize_dad_time(trim((string) $row[3])),
            ];
        }
    }

    $post = document_ai_sanitize_dad_post($post);
    $dates = array_values(array_filter(array_map(
        static fn ($d): string => document_ai_sanitize_dad_date((string) $d),
        $dates
    )));

    return [
        'template' => 'daily_attendance',
        'post' => $post,
        'dates' => $dates,
        'attendance_rows' => document_ai_dad_sanitize_rows($attendance),
    ];
}

/** @param list<string> $labels */
function document_ai_match_after_label(string $text, array $labels): string
{
    foreach ($labels as $label) {
        $pattern = '/' . preg_quote($label, '/') . '\s*([^\n]+)/iu';
        if (preg_match($pattern, $text, $m)) {
            return trim((string) $m[1]);
        }
    }

    return '';
}

/** @param list<string> $startLabels @param list<string> $endLabels */
function document_ai_section_between(string $text, array $startLabels, array $endLabels): string
{
    $start = document_ai_label_pos($text, $startLabels);
    $end = document_ai_label_pos($text, $endLabels);
    if ($start === null) {
        return '';
    }
    $from = $start + strlen(document_ai_first_label($text, $startLabels));
    $slice = $end !== null && $end > $from ? substr($text, $from, $end - $from) : substr($text, $from);

    return trim(preg_replace('/\s+/', ' ', $slice ?? '') ?? '');
}

/** @param list<string> $labels */
function document_ai_section_after(string $text, array $labels): string
{
    $pos = document_ai_label_pos($text, $labels);
    if ($pos === null) {
        return '';
    }
    $from = $pos + strlen(document_ai_first_label($text, $labels));

    return trim(preg_replace('/\s+/', ' ', substr($text, $from) ?? '') ?? '');
}

/** @param list<string> $labels */
function document_ai_label_pos(string $text, array $labels): ?int
{
    foreach ($labels as $label) {
        $pos = stripos($text, $label);
        if ($pos !== false) {
            return $pos;
        }
    }

    return null;
}

/** @param list<string> $labels */
function document_ai_first_label(string $text, array $labels): string
{
    $best = null;
    $bestLabel = $labels[0];
    foreach ($labels as $label) {
        $pos = stripos($text, $label);
        if ($pos !== false && ($best === null || $pos < $best)) {
            $best = $pos;
            $bestLabel = $label;
        }
    }

    return $bestLabel;
}

/**
 * @param array<string, mixed> $structured
 */
function document_ai_format_structured(array $structured, string $reportType, string $rawText): string
{
    $lines = ['Report type: ' . ($reportType !== '' ? $reportType : 'Unknown')];

    if (($structured['template'] ?? '') === 'incident_report') {
        $structured = document_ai_enrich_incident_structured($structured);
        $extract = is_array($structured['extraction'] ?? null)
            ? $structured['extraction']
            : document_ai_incident_extraction_json($structured);
        $lines[] = '';
        if (($extract['name_of_guard'] ?? '') !== '') {
            $lines[] = 'Name of guard: ' . $extract['name_of_guard'];
        }
        if (($extract['post'] ?? '') !== '') {
            $lines[] = 'Post: ' . $extract['post'];
        }
        if (($structured['date'] ?? '') !== '') {
            $lines[] = 'Date: ' . $structured['date'];
        }
        $lines[] = '';
        $lines[] = '── Incident description (as written) ──';
        $lines[] = (string) ($extract['incident_description'] ?? $structured['incident_description'] ?? '—');
        $lines[] = '';
        $lines[] = '── Action taken (as written) ──';
        $lines[] = (string) ($extract['action_taken'] ?? $structured['action_taken'] ?? '—');
    } elseif (($structured['template'] ?? '') === 'daily_attendance') {
        return document_ai_format_dad_extract($structured);
    }

    $formFields = $structured['form_fields'] ?? [];
    if (
        is_array($formFields) && $formFields !== []
        && ($structured['template'] ?? '') !== 'daily_attendance'
    ) {
        $lines[] = '';
        $lines[] = 'Detected form fields';
        foreach ($formFields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $label = trim((string) ($field['label'] ?? ''));
            $value = trim((string) ($field['value'] ?? ''));
            if ($label === '' && $value === '') {
                continue;
            }
            $lines[] = ($label !== '' ? $label . ': ' : '') . ($value !== '' ? $value : '—');
        }
    }

    if (
        trim($rawText) !== ''
        && count($lines) <= 2
        && ($structured['template'] ?? '') !== 'daily_attendance'
    ) {
        $lines[] = '';
        $lines[] = 'Raw OCR text';
        $lines[] = $rawText;
    }

    return implode("\n", $lines);
}

/**
 * Formatted DAD extract: NAME, TIME IN, and TIME OUT only (no full-page OCR dump).
 *
 * @param array<string, mixed> $structured
 */
function document_ai_format_dad_extract(array $structured): string
{
    $lines = [];
    foreach (document_ai_dad_display_fields($structured) as $field) {
        $label = (string) ($field['label'] ?? '');
        $value = (string) ($field['value'] ?? '');
        if ($label === '' || $value === '') {
            continue;
        }
        $lines[] = $label . ': ' . $value;
    }

    return implode("\n", $lines);
}

function document_ai_decode_stored(string $stored): array
{
    $stored = trim($stored);
    if ($stored === '') {
        return ['formatted' => '', 'raw' => '', 'structured' => []];
    }
    $json = json_decode($stored, true);
    if (is_array($json) && isset($json['formatted'])) {
        return [
            'formatted' => (string) ($json['formatted'] ?? ''),
            'raw' => (string) ($json['raw'] ?? ''),
            'structured' => is_array($json['structured'] ?? null) ? $json['structured'] : [],
        ];
    }

    return ['formatted' => $stored, 'raw' => $stored, 'structured' => []];
}

function document_ai_encode_stored(array $payload): string
{
    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

require_once __DIR__ . '/document_ai_incident_regions.php';
require_once __DIR__ . '/document_ai_dad_regions.php';
