<?php
declare(strict_types=1);

/**
 * Reverse geocoding via Google Maps Geocoding API (uses MAPS_API_KEY from .env).
 */
function google_maps_api_key(): string
{
    return trim((string) ($_ENV['MAPS_API_KEY'] ?? ''));
}

function google_maps_geocoding_enabled(): bool
{
    $key = google_maps_api_key();

    return $key !== '' && $key !== 'YOUR_MAPS_API_KEY_HERE';
}

/**
 * @return array{ok:bool,label?:string,error?:string}
 */
function google_maps_reverse_geocode(float $lat, float $lng): array
{
    if (!google_maps_geocoding_enabled()) {
        return ['ok' => false, 'error' => 'Maps API key not configured'];
    }

    $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
        'latlng' => sprintf('%.7f,%.7f', $lat, $lng),
        'key' => google_maps_api_key(),
        'language' => 'en',
    ]);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "Accept: application/json\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') {
        return ['ok' => false, 'error' => 'Geocoding request failed'];
    }

    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        return ['ok' => false, 'error' => 'Invalid geocoding response'];
    }

    $status = (string) ($data['status'] ?? '');
    if ($status !== 'OK') {
        return ['ok' => false, 'error' => 'Geocoding: ' . ($status !== '' ? $status : 'unknown')];
    }

    $results = $data['results'] ?? [];
    if (!is_array($results) || $results === []) {
        return ['ok' => false, 'error' => 'No address found'];
    }

    $label = trim((string) ($results[0]['formatted_address'] ?? ''));

    return $label !== '' ? ['ok' => true, 'label' => $label] : ['ok' => false, 'error' => 'Empty address'];
}

/**
 * @return array{ok:bool,label?:string,source?:string,error?:string}
 */
function geocode_coordinates(float $lat, float $lng): array
{
    $google = google_maps_reverse_geocode($lat, $lng);
    if ($google['ok']) {
        return ['ok' => true, 'label' => (string) $google['label'], 'source' => 'google'];
    }

    $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
        . rawurlencode((string) $lat) . '&lon=' . rawurlencode((string) $lng) . '&zoom=18&addressdetails=1';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "Accept: application/json\r\nUser-Agent: GoldenZKidsGuardPortal/1.0\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return ['ok' => false, 'error' => $google['error'] ?? 'Geocoding unavailable'];
    }

    $data = json_decode($raw, true);
    if (is_array($data) && !empty($data['display_name'])) {
        return ['ok' => true, 'label' => (string) $data['display_name'], 'source' => 'nominatim'];
    }

    return [
        'ok' => true,
        'label' => sprintf('%.6f, %.6f', $lat, $lng),
        'source' => 'coordinates',
    ];
}
