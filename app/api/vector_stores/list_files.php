<?php

// public/api/vector_stores/list_files.php

require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/openai.php';

header('Content-Type: application/json');
$vectorStoreId = $_GET['vector_store_id'] ?? '';

$result = openai_api('GET', "/vector_stores/{$vectorStoreId}/files");
echo json_encode($result);
