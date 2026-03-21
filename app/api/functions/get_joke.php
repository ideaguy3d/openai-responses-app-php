<?php
// public/api/functions/get_joke.php
header('Content-Type: application/json');

$jokeData = json_decode(file_get_contents('https://v2.jokeapi.dev/joke/Programming'), true);
$joke = ($jokeData['type'] === 'twopart')
    ? $jokeData['setup'] . ' - ' . $jokeData['delivery']
    : $jokeData['joke'];

echo json_encode(['joke' => $joke]);