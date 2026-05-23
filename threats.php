<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

const DEFAULT_RADIUS = 200;
const MAX_RADIUS = 1000;
const CACHE_TIME = 300; // seconds
const CACHE_FILE = __DIR__ . '/gdacs_cache.xml';
const GDACS_FEED = 'https://www.gdacs.org/xml/rss.xml';

function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);

    header('Content-Type: application/json');
    header('Cache-Control: no-store');

    echo json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    exit;
}


function cleanText(string $text): string {
    return trim(strip_tags($text));
}

function validateCoordinates(float $lat, float $lon): bool {
    return (
        $lat >= -90 &&
        $lat <= 90 &&
        $lon >= -180 &&
        $lon <= 180
    );
}

function calculateDistance(
    float $lat1,
    float $lon1,
    float $lat2,
    float $lon2
): float {

    $earthRadius = 6371;

    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);

    $a =
        sin($latDelta / 2) * sin($latDelta / 2) +
        cos(deg2rad($lat1)) *
        cos(deg2rad($lat2)) *
        sin($lonDelta / 2) *
        sin($lonDelta / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

function getAlertColor(string $level): string {
    return match (strtolower($level)) {
        'red' => '#ff0000',
        'orange' => '#ffa500',
        'green' => '#008000',
        default => '#808080'
    };
}

function fetchGDACSFeed(): string {
    if (
        file_exists(CACHE_FILE) &&
        (time() - filemtime(CACHE_FILE)) < CACHE_TIME
    ) {
        return file_get_contents(CACHE_FILE);
    }

    /*
        Ensure CURL exists
    */

    if (!function_exists('curl_init')) {
        throw new Exception('CURL extension not installed.');
    }

    $ch = curl_init(GDACS_FEED);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => '',
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'DisasterMonitor/2.0'
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception(
            'CURL Error: ' . curl_error($ch)
        );
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception(
            'GDACS returned HTTP ' . $httpCode
        );
    }

    file_put_contents(CACHE_FILE, $response);

    return $response;
}

function getDisastersNearby(
    float $userLat,
    float $userLon,
    float $radius
): array {

    $xmlString = fetchGDACSFeed();

    libxml_use_internal_errors(true);

    $xml = simplexml_load_string(
        $xmlString,
        'SimpleXMLElement',
        LIBXML_NOCDATA
    );

    if (!$xml) {
        throw new Exception('Invalid XML received.');
    }

    $namespaces = $xml->getNamespaces(true);

    $eventTypes = [
        'EQ' => 'Earthquake',
        'FL' => 'Flood',
        'TC' => 'Tropical Cyclone',
        'TS' => 'Tsunami',
        'VO' => 'Volcano',
        'WF' => 'Wild Fire',
        'DR' => 'Drought'
    ];

    $events = [];

    foreach ($xml->channel->item as $item) {
        $coords = null;

        if (isset($namespaces['georss'])) {

            $geo = $item->children(
                $namespaces['georss']
            );

            if ($geo && $geo->point) {

                $parts = explode(
                    ' ',
                    trim((string)$geo->point)
                );

                if (count($parts) === 2) {

                    $coords = [
                        'lat' => (float)$parts[0],
                        'lon' => (float)$parts[1]
                    ];
                }
            }
        }

        if (!$coords) {
            continue;
        }

        $distance = calculateDistance(
            $userLat,
            $userLon,
            $coords['lat'],
            $coords['lon']
        );

        if ($distance > $radius) {
            continue;
        }

        $gdacs = null;

        if (isset($namespaces['gdacs'])) {
            $gdacs = $item->children(
                $namespaces['gdacs']
            );
        }

        $alert = strtolower(
            (string)($gdacs->alertlevel ?? 'unknown')
        );

        $priority = match ($alert) {
            'red' => 1,
            'orange' => 2,
            'green' => 3,
            default => 4
        };

        $eventCode = (string)($gdacs->eventtype ?? 'Unknown');

        $events[] = [
            'title' => cleanText((string)$item->title),

            'event_id' =>
                cleanText((string)($gdacs->eventid ?? 'N/A')),

            'event_type' => $eventCode,

            'event_type_name' =>
                $eventTypes[$eventCode] ?? $eventCode,

            'alert_level' => $alert,

            'alert_color' =>
                getAlertColor($alert),

            'priority' => $priority,

            'distance_km' =>
                round($distance, 2),

            'coordinates' => $coords,

            'from_date' =>
                cleanText((string)($gdacs->fromdate ?? '')),

            'to_date' =>
                cleanText((string)($gdacs->todate ?? '')),

            'severity' =>
                cleanText((string)($gdacs->severity ?? '')),

            'population_exposed' =>
                cleanText((string)($gdacs->population ?? '')),

            'description' =>
                cleanText((string)$item->description),

            'link' =>
                filter_var(
                    (string)$item->link,
                    FILTER_VALIDATE_URL
                ) ?: '',

            'published' =>
                cleanText((string)$item->pubDate)
        ];
    }

    usort($events, function ($a, $b) {

        if ($a['priority'] === $b['priority']) {
            return $a['distance_km'] <=> $b['distance_km'];
        }

        return $a['priority'] <=> $b['priority'];
    });

    return [
        'success' => true,

        'user_location' => [
            'latitude' => $userLat,
            'longitude' => $userLon,
            'radius_km' => $radius
        ],

        'total_events' => count($events),

        'last_updated' => date('c'),

        'events' => $events
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        if (!isset($_POST['lat']) ||!isset($_POST['lon'])) {
            jsonResponse([
                'success' => false,
                'error' => 'Missing coordinates.'
            ], 400);
        }

        if (!is_numeric($_POST['lat']) || !is_numeric($_POST['lon'])) {
            jsonResponse([
                'success' => false,
                'error' => 'Coordinates must be numeric.'
            ], 400);
        }

        $lat = (float)$_POST['lat'];
        $lon = (float)$_POST['lon'];

        if (!validateCoordinates($lat, $lon)) {
            jsonResponse([
                'success' => false,
                'error' => 'Invalid coordinate range.'
            ], 400);
        }

        $radius = isset($_POST['radius'])
            ? (float)$_POST['radius']
            : DEFAULT_RADIUS;

        $radius = max(
            1,
            min($radius, MAX_RADIUS)
        );
        
        $result = getDisastersNearby(
            $lat,
            $lon,
            $radius
        );

        jsonResponse($result);

    } catch (Throwable $e) {

        jsonResponse([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Perimeter Scan</title>
   <link rel = "stylesheet" href = "./Stylesheets/threatsStyle.css">
</head>
<body>

<h1>🌍 Disaster Monitoring System</h1>

<div id="status" class="loading">
    Getting your location...
</div>

<hr>

<div id="results"></div>

<script>

function escapeHTML(str) {
    return String(str).replace(
        /[&<>"']/g,
        function(match) {

            const map = {
                '&':'&amp;',
                '<':'&lt;',
                '>':'&gt;',
                '"':'&quot;',
                "'":'&#039;'
            };

            return map[match];
        }
    );
}

async function fetchDisasters(lat, lon, radius = 200) {
    const status = document.getElementById('status');
    const results = document.getElementById('results');

    status.innerHTML ='📡 Fetching disaster data...';
    results.innerHTML = '';

    try {
        const formData = new FormData();

        formData.append('lat', lat);
        formData.append('lon', lon);
        formData.append('radius', radius);

        const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
        });

        const text = await response.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch(err) {
            console.error(text);
            throw new Error('Invalid JSON response from server.');
        }

        if (!data.success) {
            throw new Error(data.error);
        }
        renderDisasters(data);
    } catch(error) {
        console.error(error);
        status.innerHTML =`<span class="error">❌ ${escapeHTML(error.message)}</span>`;
    }
}

function renderDisasters(data) {

    const status = document.getElementById('status');
    const results = document.getElementById('results');

    status.innerHTML = `
        <div class="success">
            ✅ Location Loaded
        </div>

        <p>
            Latitude:
            ${data.user_location.latitude}
        </p>

        <p>
            Longitude:
            ${data.user_location.longitude}
        </p>

        <p>
            Radius:
            ${data.user_location.radius_km} km
        </p>

        <p>
            Events Found:
            ${data.total_events}
        </p>
    `;

    if (data.total_events === 0) {
        results.innerHTML = `<p>✅ No nearby disasters detected.</p>`;
        return;
    }

    let html = '';
    for (const event of data.events) {
        html += `<div class="event">
                 <h3
                    style=" color:${event.alert_color};">
                    ${escapeHTML(event.event_type_name)}
                    (${escapeHTML(event.alert_level)})
                 </h3>

                <p><strong>Title:</strong>${escapeHTML(event.title)}</p>

                <p><strong>Distance:</strong>${event.distance_km} km</p>

                <p><strong>Severity:</strong>${escapeHTML(event.severity)}</p>

                <p><strong>Published:</strong>${escapeHTML(event.published)}</p>

                <p><strong>Description:</strong>${escapeHTML(event.description)}</p>

                <p>
                  <a href="${escapeHTML(event.link)}"target="_blank">View Event</a>
                </p>
            </div>
        `;
    }
    results.innerHTML = html;
}

function locationSuccess(position) {
    const lat = position.coords.latitude;
    const lon = position.coords.longitude;
    fetchDisasters(lat, lon);
}

function locationError(error) {
    // debugging pupose only mwehehehe
    console.error(error);
    const status = document.getElementById('status');
    status.innerHTML = `<span class="error">❌ Unable to get location.Using fallback location.</span>`;
    fetchDisasters(
        14.5995,
        120.9842
    );
}

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(locationSuccess, locationError, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    });
} else {
 document.getElementById('status').innerHTML ='❌ Geolocation not supported.';
}
</script>
</body>
</html>