<?php

// public/api/vector_stores/create_store.php

require_once __DIR__ . '/../../../config_response_starter.php';
require_once __DIR__ . '/../../includes/openai.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$result = openai_api('POST', '/vector_stores', ['name' => $input['name']]);
echo json_encode($result);