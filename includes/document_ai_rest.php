<?php
declare(strict_types=1);

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\Document;
use Google\Protobuf\Internal\RawInputStream;

/**
 * Shared-hosting friendly Document AI (curl + REST JSON, no gRPC).
 */

function document_ai_should_use_direct_rest(): bool
{
    $mode = strtolower(trim((string) ($_ENV['DOCUMENT_AI_TRANSPORT'] ?? '')));
    if ($mode === 'grpc' || $mode === 'client') {
        return false;
    }
    if (in_array($mode, ['rest', 'direct', 'curl', 'http'], true)) {
        return true;
    }

    if (filter_var($_ENV['DOCUMENT_AI_DIRECT_REST'] ?? false, FILTER_VALIDATE_BOOL)) {
        return true;
    }

    if (!extension_loaded('grpc')) {
        return true;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    foreach (['infinityfree', 'epizy', 'byet.org', 'rf.gd', '42web.io'] as $needle) {
        if ($host !== '' && str_contains($host, $needle)) {
            return true;
        }
    }

    $docRoot = strtolower(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')));
    foreach (['infinityfree', '/epiz_', 'byethost'] as $needle) {
        if ($docRoot !== '' && str_contains($docRoot, $needle)) {
            return true;
        }
    }

    return false;
}

function document_ai_debug_errors_enabled(): bool
{
    return filter_var($_ENV['DOCUMENT_AI_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
}

function document_ai_public_error(Throwable $e): string
{
    if (document_ai_debug_errors_enabled()) {
        return 'OCR failed: ' . $e->getMessage();
    }

    return 'OCR processing failed. Check credentials, processor, and API access.';
}

/** @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg */
function document_ai_rest_processor_name(array $cfg): string
{
    if ($cfg['processor_id'] === '') {
        return '';
    }

    return DocumentProcessorServiceClient::processorName(
        $cfg['project_id'],
        $cfg['location'],
        $cfg['processor_id']
    );
}

/** @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg */
function document_ai_rest_api_host(array $cfg): string
{
    $location = $cfg['location'] === '' ? 'us' : $cfg['location'];

    return $location === 'us'
        ? 'us-documentai.googleapis.com'
        : $location . '-documentai.googleapis.com';
}

/** @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg */
function document_ai_rest_access_token(array $cfg): string
{
    $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
    $credentials = new ServiceAccountCredentials($scopes, $cfg['credentials']);
    $token = $credentials->fetchAuthToken();
    $access = trim((string) ($token['access_token'] ?? ''));
    if ($access === '') {
        throw new RuntimeException('Could not obtain a Google access token from the service account.');
    }

    return $access;
}

/**
 * @return array{ok: bool, status?: int, body?: string, error?: string}
 */
function document_ai_http_post_json(string $url, string $jsonBody, string $bearerToken, int $timeoutSec = 120): array
{
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $bearerToken,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'Could not initialize HTTP client.'];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'error' => $curlError !== '' ? $curlError : 'HTTP request failed.'];
        }

        return ['ok' => true, 'status' => $status, 'body' => (string) $body];
    }

    $headerLines = implode("\r\n", $headers);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headerLines,
            'content' => $jsonBody,
            'timeout' => $timeoutSec,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $ctx);
    if ($body === false || $body === '') {
        return ['ok' => false, 'error' => 'HTTP request failed (allow_url_fopen / outbound HTTPS may be blocked).'];
    }

    $status = 0;
    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', (string) $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }

    return ['ok' => true, 'status' => $status, 'body' => $body];
}

/**
 * @param array<string, mixed> $documentJson
 */
function document_ai_document_from_api_json(array $documentJson): Document
{
    $document = new Document();
    $json = json_encode($documentJson, JSON_THROW_ON_ERROR);
    $stream = new RawInputStream($json);
    $document->parseFromJsonStream($stream, true);

    return $document;
}

/**
 * Direct REST :process call (works on shared hosting without gRPC).
 *
 * @param array{project_id: string, location: string, credentials: string, processor_id: string} $cfg
 * @return array{ok: bool, error?: string, document?: Document, processor?: string}
 */
function document_ai_rest_process_document(string $bytes, string $mimeType, array $cfg): array
{
    $processorName = document_ai_rest_processor_name($cfg);
    if ($processorName === '') {
        return ['ok' => false, 'error' => 'No Document AI OCR processor found. Set GOOGLE_DOCUMENT_AI_PROCESSOR_ID in .env.'];
    }

    $host = document_ai_rest_api_host($cfg);
    $url = 'https://' . $host . '/v1/' . $processorName . ':process';

    $payload = [
        'rawDocument' => [
            'content' => base64_encode($bytes),
            'mimeType' => $mimeType,
        ],
        'skipHumanReview' => true,
        'processOptions' => [
            'ocrConfig' => [
                'enableNativePdfParsing' => true,
            ],
        ],
    ];

    $token = document_ai_rest_access_token($cfg);
    $http = document_ai_http_post_json($url, json_encode($payload, JSON_THROW_ON_ERROR), $token);
    if (!$http['ok']) {
        return ['ok' => false, 'error' => (string) ($http['error'] ?? 'Document AI HTTP request failed.')];
    }

    $status = (int) ($http['status'] ?? 0);
    $body = (string) ($http['body'] ?? '');
    if ($status < 200 || $status >= 300) {
        $detail = '';
        try {
            $errJson = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $detail = trim((string) ($errJson['error']['message'] ?? ''));
        } catch (Throwable) {
            $detail = $body !== '' ? substr($body, 0, 240) : '';
        }

        $message = $detail !== '' ? $detail : ('Document AI API returned HTTP ' . $status);

        return ['ok' => false, 'error' => $message];
    }

    try {
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Invalid Document AI response.'];
    }

    $documentJson = $decoded['document'] ?? null;
    if (!is_array($documentJson)) {
        return ['ok' => false, 'error' => 'Document AI returned an empty result.'];
    }

    try {
        $document = document_ai_document_from_api_json($documentJson);
    } catch (Throwable $e) {
        $rawText = trim((string) ($documentJson['text'] ?? ''));
        if ($rawText === '') {
            return ['ok' => false, 'error' => 'Could not parse Document AI response.'];
        }

        return [
            'ok' => true,
            'document' => null,
            'raw_text' => $rawText,
            'processor' => $processorName,
        ];
    }

    return [
        'ok' => true,
        'document' => $document,
        'processor' => $processorName,
    ];
}
