<?php

require_once __DIR__ . '/../../../config_response_starter.php';

$fileId = $_GET['file_id'] ?? '';
$containerId = $_GET['container_id'] ?? '';
$filename = $_GET['filename'] ?? $fileId;

if (!$fileId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing file_id']);
    exit;
}

$url = $containerId
    ? "https://api.openai.com/v1/containers/{$containerId}/files/{$fileId}/content"
    : "https://api.openai.com/v1/container-files/{$fileId}/content";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . OPENAI_API_KEY],
    CURLOPT_RETURNTRANSFER => true,
]);
$content = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream';
curl_close($ch);

header("Content-Type: {$contentType}");
header("Content-Disposition: attachment; filename={$filename}");
echo $content;
