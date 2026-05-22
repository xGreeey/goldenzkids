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
 * @param array<int, mixed> $results
 * @return array<string, mixed>|null
 */
function google_maps_pick_geocode_result(array $results): ?array
{
    $typePriority = [
        'street_address' => 0,
        'premise' => 1,
        'subpremise' => 2,
        'establishment' => 3,
        'point_of_interest' => 4,
        'route' => 5,
        'neighborhood' => 6,
        'sublocality_level_1' => 7,
        'sublocality' => 8,
        'locality' => 9,
    ];
    $locRank = [
        'ROOFTOP' => 0,
        'RANGE_INTERPOLATED' => 1,
        'GEOMETRIC_CENTER' => 2,
        'APPROXIMATE' => 3,
    ];

    $best = null;
    $bestScore = PHP_INT_MAX;

    foreach ($results as $row) {
        if (!is_array($row)) {
            continue;
        }
        $typeScore = 99;
        foreach ((array) ($row['types'] ?? []) as $type) {
            $type = (string) $type;
            if (isset($typePriority[$type])) {
                $typeScore = min($typeScore, $typePriority[$type]);
            }
        }
        $locType = (string) ($row['geometry']['location_type'] ?? 'APPROXIMATE');
        $locScore = $locRank[$locType] ?? 3;
        $score = ($typeScore * 10) + $locScore;
        if ($score < $bestScore) {
            $bestScore = $score;
            $best = $row;
        }
    }

    if ($best !== null) {
        return $best;
    }

    return is_array($results[0] ?? null) ? $results[0] : null;
}

/**
 * @param array<string, mixed> $result
 * @return array{label:string,locality:string,admin_area:string}
 */
function google_maps_format_geocode_result(array $result): array
{
    $byType = [];
    foreach ((array) ($result['address_components'] ?? []) as $component) {
        if (!is_array($component)) {
            continue;
        }
        $name = trim((string) ($component['long_name'] ?? ''));
        if ($name === '') {
            continue;
        }
        foreach ((array) ($component['types'] ?? []) as $type) {
            $byType[(string) $type] = $name;
        }
    }

    $route = trim((string) ($byType['route'] ?? ''));
    if (strcasecmp($route, 'street') === 0 || strcasecmp($route, 'road') === 0) {
        $route = '';
    }
    $street = trim(implode(' ', array_filter([
        $byType['street_number'] ?? '',
        $route,
    ])));
    $sublocality = $byType['sublocality_level_1']
        ?? $byType['sublocality']
        ?? $byType['neighborhood']
        ?? '';
    $city = $byType['locality']
        ?? $byType['administrative_area_level_2']
        ?? '';
    $region = $byType['administrative_area_level_1'] ?? '';
    $adminArea = $region;

    $parts = array_values(array_filter([$street, $sublocality, $city, $region]));
    $label = $parts !== [] ? implode(', ', $parts) : trim((string) ($result['formatted_address'] ?? ''));

    return [
        'label' => $label,
        'locality' => $city,
        'admin_area' => $adminArea,
    ];
}

/**
 * @return array{ok:bool,label?:string,locality?:string,admin_area?:string,error?:string}
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
        'region' => 'ph',
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

    $picked = google_maps_pick_geocode_result($results);
    if ($picked === null) {
        return ['ok' => false, 'error' => 'No address found'];
    }

    $formatted = google_maps_format_geocode_result($picked);
    if ($formatted['label'] === '') {
        return ['ok' => false, 'error' => 'Empty address'];
    }

    return [
        'ok' => true,
        'label' => $formatted['label'],
        'locality' => $formatted['locality'],
        'admin_area' => $formatted['admin_area'],
    ];
}

/**
 * @return array{ok:bool,label?:string,locality?:string,admin_area?:string,source?:string,accuracy_warning?:string,error?:string}
 */
function geocode_coordinates(float $lat, float $lng, ?float $gpsAccuracyM = null): array
{
    $accuracyWarning = null;
    if ($gpsAccuracyM !== null && $gpsAccuracyM > 0) {
        if ($gpsAccuracyM > 150) {
            $accuracyWarning = sprintf(
                'GPS precision is low (±%d m). The city or street shown may be wrong — move outdoors, wait a few seconds, and stamp again.',
                (int) round($gpsAccuracyM)
            );
        } elseif ($gpsAccuracyM > 60) {
            $accuracyWarning = sprintf(
                'GPS precision is moderate (±%d m). For a more accurate city/street, move outdoors and stamp again.',
                (int) round($gpsAccuracyM)
            );
        }
    }

    $google = google_maps_reverse_geocode($lat, $lng);
    if ($google['ok']) {
        $out = [
            'ok' => true,
            'label' => (string) $google['label'],
            'source' => 'google',
        ];
        if (($google['locality'] ?? '') !== '') {
            $out['locality'] = (string) $google['locality'];
        }
        if (($google['admin_area'] ?? '') !== '') {
            $out['admin_area'] = (string) $google['admin_area'];
        }
        if ($accuracyWarning !== null) {
            $out['accuracy_warning'] = $accuracyWarning;
        }

        return $out;
    }

    $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
        . rawurlencode((string) $lat) . '&lon=' . rawurlencode((string) $lng)
        . '&zoom=18&addressdetails=1&accept-language=en';
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
        $out = [
            'ok' => true,
            'label' => (string) $data['display_name'],
            'source' => 'nominatim',
        ];
        $addr = $data['address'] ?? [];
        if (is_array($addr)) {
            $city = (string) ($addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? $addr['county'] ?? '');
            if ($city !== '') {
                $out['locality'] = $city;
            }
        }
        if ($accuracyWarning !== null) {
            $out['accuracy_warning'] = $accuracyWarning;
        }

        return $out;
    }

    $out = [
        'ok' => true,
        'label' => sprintf('%.6f, %.6f', $lat, $lng),
        'source' => 'coordinates',
    ];
    if ($accuracyWarning !== null) {
        $out['accuracy_warning'] = $accuracyWarning;
    }

    return $out;
}
