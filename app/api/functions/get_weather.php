<?php
// public/api/functions/get_weather.php
header('Content-Type: application/json');

$location = $_GET['location'] ?? '';
$unit = $_GET['unit'] ?? 'celsius';

if (!$location) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing location']);
    exit;
}

// 1. Geocode the location
$geoUrl = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
    'q' => $location, 
    'format' => 'json'
]);
$geoData = json_decode(file_get_contents($geoUrl), true);

if (empty($geoData)) {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid location']);
    exit;
}

$lat = $geoData[0]['lat'];
$lon = $geoData[0]['lon'];

// 2. Fetch weather
$weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&hourly=temperature_2m&temperature_unit={$unit}";
$weather = json_decode(file_get_contents($weatherUrl), true);

// 3. Find current hour's temperature
$currentHour = gmdate('Y-m-d\TH:00');
$index = array_search($currentHour, $weather['hourly']['time']);
$temp = ($index !== false) ? $weather['hourly']['temperature_2m'][$index] : null;

if ($temp === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Temperature data unavailable']);
    exit;
}

echo json_encode(['temperature' => $temp]);
