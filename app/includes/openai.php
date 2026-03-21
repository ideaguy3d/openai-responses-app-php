<?php

// includes/openai.php

/**
 * Call OpenAI Responses API (non-streaming).
 * Returns the decoded JSON response.
 */
function openai_responses_create(array $params): array {
    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
    ]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

/**
 * Call OpenAI Responses API with STREAMING (SSE).
 * Reads chunks and calls $onEvent($eventData) for each SSE event.
 */
function openai_responses_stream(array $params, callable $onEvent): void {
    $params['stream'] = true;
    $buffer = '';

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_RETURNTRANSFER => false,  // Don't buffer — stream it
        CURLOPT_TIMEOUT => 300,
        // This callback fires for each chunk received from OpenAI
        CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$buffer, $onEvent) {
            $buffer .= $chunk;
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line === 'data: [DONE]') continue;
                if (str_starts_with($line, 'data: ')) {
                    $json = substr($line, 6);
                    $data = json_decode($json, true);
                    if ($data) {
                        $onEvent($data);
                    }
                }
            }
            return strlen($chunk); // Must return length to continue
        },
    ]);
    curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('OpenAI stream cURL error: ' . curl_error($ch));
    }
    curl_close($ch);
}

/**
 * Upload a file to OpenAI.
 */
function openai_upload_file(string $filePath, string $fileName, string $purpose = 'assistants'): array {
    $ch = curl_init('https://api.openai.com/v1/files');
    $cfile = new CURLFile($filePath, 'application/octet-stream', $fileName);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => [
            'file' => $cfile,
            'purpose' => $purpose,
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

/**
 * Generic OpenAI API call (GET or POST).
 */
function openai_api(string $method, string $endpoint, ?array $body = null): array {
    $url = 'https://api.openai.com/v1/' . ltrim($endpoint, '/');
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . OPENAI_API_KEY,
        'Content-Type: application/json',
    ];
    $opts = [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ];
    if (strtoupper($method) === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($body) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}