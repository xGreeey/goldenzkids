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

    return array_merge($parsed, [
        'report_type' => $reportType,
        'form_fields' => $formFields,
        'tables' => $tables,
    ]);
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
 * @return array<string, mixed>
 */
function document_ai_parse_dad_report(string $text): array
{
    $post = document_ai_match_after_label($text, ['POST:', 'Post:']);
    $dates = [];
    if (preg_match_all('/\bDATE\s*:\s*([^\n]+)/iu', $text, $m)) {
        $dates = array_values(array_filter(array_map('trim', $m[1])));
    }

    $attendance = [];
    if (preg_match_all(
        '/([A-Z][A-Z0-9\s\.\-\'",]+)\s+(\d{1,2}:\d{2}(?:\s*[AP]M)?)\s+(\d{1,2}:\d{2}(?:\s*[AP]M)?)/iu',
        $text,
        $rows,
        PREG_SET_ORDER
    )) {
        foreach ($rows as $row) {
            $attendance[] = [
                'name' => trim((string) $row[1]),
                'time_in' => trim((string) $row[2]),
                'time_out' => trim((string) $row[3]),
            ];
        }
    }

    return [
        'template' => 'daily_attendance',
        'post' => $post,
        'dates' => $dates,
        'attendance_rows' => $attendance,
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
        $lines[] = '';
        $lines[] = 'Post: ' . ($structured['post'] ?? '—');
        $dates = $structured['dates'] ?? [];
        if (is_array($dates) && $dates !== []) {
            $lines[] = 'Date(s): ' . implode(' · ', array_map('strval', $dates));
        }
        $rows = $structured['attendance_rows'] ?? [];
        if (is_array($rows) && $rows !== []) {
            $lines[] = '';
            $lines[] = 'Attendance';
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $lines[] = sprintf(
                    '- %s | In: %s | Out: %s',
                    (string) ($row['name'] ?? '—'),
                    (string) ($row['time_in'] ?? '—'),
                    (string) ($row['time_out'] ?? '—')
                );
            }
        }
    }

    $formFields = $structured['form_fields'] ?? [];
    if (is_array($formFields) && $formFields !== []) {
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
