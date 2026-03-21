<?php

// public/api/vector_stores/create_store.php

require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/openai.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$result = openai_api('POST', '/vector_stores', ['name' => $input['name']]);
echo json_encode($result);