<?php
// public/api/functions/get_joke.php
header('Content-Type: application/json');

function respond_with_error(string $message, int $status = 502): void {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

$jokeResponse = @file_get_contents('https://v2.jokeapi.dev/joke/Programming');
if ($jokeResponse === false) {
    respond_with_error('Failed to fetch joke');
}

$jokeData = json_decode($jokeResponse, true);
if (!is_array($jokeData)) {
    respond_with_error('Invalid joke payload');
}

if (($jokeData['type'] ?? '') === 'twopart') {
    $joke = trim(($jokeData['setup'] ?? '') . ' - ' . ($jokeData['delivery'] ?? ''));
} elseif (!empty($jokeData['joke'])) {
    $joke = $jokeData['joke'];
} else {
    respond_with_error('Joke data missing');
}

echo json_encode(['joke' => $joke]);
