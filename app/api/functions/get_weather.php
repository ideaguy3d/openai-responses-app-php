<?php
// public/api/functions/get_weather.php
header('Content-Type: application/json');

function respond_with_error(string $message, int $status = 502): void {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

$location = $_GET['location'] ?? '';
$unit = $_GET['unit'] ?? 'celsius';

if (!$location) {
    respond_with_error('Missing location', 400);
}

// 1. Geocode the location
$geoUrl = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query([
    'name' => $location,
    'count' => 1,
    'language' => 'en',
    'format' => 'json',
]);
$geoResponse = @file_get_contents($geoUrl);
if ($geoResponse === false) {
    respond_with_error('Geocoding failed');
}

$geoData = json_decode($geoResponse, true);
if (empty($geoData['results'][0]) || !is_array($geoData['results'][0])) {
    respond_with_error('Invalid location', 404);
}

$lat = $geoData['results'][0]['latitude'];
$lon = $geoData['results'][0]['longitude'];

// 2. Fetch weather
$weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&hourly=temperature_2m&temperature_unit={$unit}";
$weatherResponse = @file_get_contents($weatherUrl);
if ($weatherResponse === false) {
    respond_with_error('Weather lookup failed');
}

$weather = json_decode($weatherResponse, true);
$hourly = $weather['hourly'] ?? null;
if (!is_array($hourly)) {
    respond_with_error('Weather payload malformed');
}

// 3. Find current hour's temperature
$currentHour = gmdate('Y-m-d\TH:00');
$times = $hourly['time'] ?? [];
$temperatures = $hourly['temperature_2m'] ?? [];
$index = array_search($currentHour, $times, true);
$temp = ($index !== false && isset($temperatures[$index])) ? $temperatures[$index] : null;

if ($temp === null) {
    respond_with_error('Temperature data unavailable');
}

echo json_encode(['temperature' => $temp]);
