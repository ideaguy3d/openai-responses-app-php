<?php

// public/api/vector_stores/upload_file.php

require_once __DIR__ . '/../../../config_response_starter.php';
require_once __DIR__ . '/../../includes/openai.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$fileObject = $input['fileObject'];

// Decode base64 file content and save to a temp file
$tmpFile = tempnam(sys_get_temp_dir(), 'oai_');
file_put_contents($tmpFile, base64_decode($fileObject['content']));

$result = openai_upload_file($tmpFile, $fileObject['name']);
unlink($tmpFile); // Clean up temp file

echo json_encode($result);