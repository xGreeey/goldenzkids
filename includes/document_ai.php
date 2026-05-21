<?php
declare(strict_types=1);

use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\Document;
use Google\Cloud\DocumentAI\V1\Document\Page\FormField;
use Google\Cloud\DocumentAI\V1\Document\Page\Table;
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
        'Post incident' => 'report-template-incident.png',
        'Daily Attendance Document' => 'report-template-dad.png',
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

    $bytes = file_get_contents($absolutePath);
    if ($bytes === false || $bytes === '') {
        return ['ok' => false, 'error' => 'Could not read the report scan image.'];
    }

    try {
        $cfg = document_ai_config();
        $client = document_ai_client($cfg);
        $processorName = document_ai_resolve_processor_name($client, $cfg);
        if ($processorName === '') {
            return ['ok' => false, 'error' => 'No Document AI OCR processor found. Set GOOGLE_DOCUMENT_AI_PROCESSOR_ID in .env.'];
        }

        $raw = (new RawDocument())
            ->setContent($bytes)
            ->setMimeType(document_ai_mime_for_path($absolutePath));

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
    } catch (Throwable $e) {
        error_log('Document AI OCR failed: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'OCR processing failed. Check credentials, processor, and API access.'];
    }
}

/** @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg */
function document_ai_client(array $cfg): DocumentProcessorServiceClient
{
    $location = $cfg['location'];
    $endpoint = $location === 'us'
        ? 'us-documentai.googleapis.com'
        : $location . '-documentai.googleapis.com';

    return new DocumentProcessorServiceClient([
        'credentials' => $cfg['credentials'],
        'apiEndpoint' => $endpoint,
    ]);
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
 * @param list<array{name: string, time_in: string, time_out: string}> $rows
 * @return list<array{name: string, time_in: string, time_out: string}>
 */
function document_ai_dad_sanitize_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = document_ai_sanitize_dad_name((string) ($row['name'] ?? ''));
        $timeIn = document_ai_sanitize_dad_time((string) ($row['time_in'] ?? ''));
        $timeOut = document_ai_sanitize_dad_time((string) ($row['time_out'] ?? ''));
        if ($name === '' && $timeIn === '' && $timeOut === '') {
            continue;
        }
        $out[] = ['name' => $name, 'time_in' => $timeIn, 'time_out' => $timeOut];
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

    if ($reportType === 'Post incident' || str_contains($upper, 'INCIDENT REPORT')) {
        return document_ai_parse_incident_report($normalized);
    }

    if ($reportType === 'Daily Attendance Document' || str_contains($upper, 'DAILY ATTENDANCE')) {
        return document_ai_parse_dad_report($normalized);
    }

    return ['template' => 'generic', 'notes' => trim($normalized)];
}

/**
 * @return array<string, mixed>
 */
function document_ai_parse_incident_report(string $text): array
{
    $name = document_ai_match_after_label($text, ['Name:', 'NAME:']);
    $date = document_ai_match_after_label($text, ['Date:', 'DATE:']);
    $incident = document_ai_section_between(
        $text,
        ['INCIDENT DESCRIPTION', 'Incident Description'],
        ['ACTION TAKEN', 'Action Taken']
    );
    $action = document_ai_section_after($text, ['ACTION TAKEN', 'Action Taken']);

    return [
        'template' => 'incident_report',
        'name' => $name,
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
 * Ordered fields matching the printed DAD sheet (POST, DATE, NAME, TIME IN, TIME OUT).
 *
 * @param list<string> $dates
 * @param list<array{name: string, time_in: string, time_out: string}> $attendanceRows
 * @return list<array{key: string, label: string, value: string}>
 */
function document_ai_dad_build_display_fields(string $post, array $dates, array $attendanceRows): array
{
    $fields = [];

    if ($post !== '') {
        $fields[] = ['key' => 'post', 'label' => 'POST', 'value' => $post];
    }

    if ($dates !== []) {
        $fields[] = ['key' => 'date', 'label' => 'DATE', 'value' => implode(' · ', $dates)];
    }

    $rowIndex = 0;
    foreach ($attendanceRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rowIndex++;
        $name = trim((string) ($row['name'] ?? ''));
        $timeIn = trim((string) ($row['time_in'] ?? ''));
        $timeOut = trim((string) ($row['time_out'] ?? ''));
        if ($name === '' && $timeIn === '' && $timeOut === '') {
            continue;
        }
        $suffix = count($attendanceRows) > 1 ? ' (' . $rowIndex . ')' : '';
        if ($name !== '') {
            $fields[] = ['key' => 'name_' . $rowIndex, 'label' => 'NAME' . $suffix, 'value' => $name];
        }
        if ($timeIn !== '') {
            $fields[] = ['key' => 'time_in_' . $rowIndex, 'label' => 'TIME IN' . $suffix, 'value' => $timeIn];
        }
        if ($timeOut !== '') {
            $fields[] = ['key' => 'time_out_' . $rowIndex, 'label' => 'TIME OUT' . $suffix, 'value' => $timeOut];
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

    return is_array($enriched['display_fields'] ?? null) ? $enriched['display_fields'] : [];
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
        $lines[] = '';
        $lines[] = 'Name: ' . ($structured['name'] ?? '—');
        $lines[] = 'Date: ' . ($structured['date'] ?? '—');
        $lines[] = '';
        $lines[] = 'Incident description';
        $lines[] = (string) ($structured['incident_description'] ?? '—');
        $lines[] = '';
        $lines[] = 'Action taken';
        $lines[] = (string) ($structured['action_taken'] ?? '—');
    } elseif (($structured['template'] ?? '') === 'daily_attendance') {
        $display = document_ai_dad_display_fields($structured);
        $lines[] = '';
        if ($display !== []) {
            foreach ($display as $field) {
                $label = (string) ($field['label'] ?? '');
                $value = (string) ($field['value'] ?? '');
                if ($label === '' || $value === '') {
                    continue;
                }
                $lines[] = $label . ': ' . $value;
            }
        } else {
            $lines[] = 'POST: ' . ($structured['post'] ?? '—');
            $dates = $structured['dates'] ?? [];
            if (is_array($dates) && $dates !== []) {
                $lines[] = 'DATE: ' . implode(' · ', array_map('strval', $dates));
            }
        }
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

    if (trim($rawText) !== '' && count($lines) <= 2) {
        $lines[] = '';
        $lines[] = 'Raw OCR text';
        $lines[] = $rawText;
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
