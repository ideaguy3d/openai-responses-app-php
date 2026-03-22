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
function dispatch_openai_stream_line(string $line, callable $onEvent, bool &$sawEvent): void {
    $line = trim($line);
    if ($line === '' || $line === 'data: [DONE]') {
        return;
    }

    if (!str_starts_with($line, 'data: ')) {
        return;
    }

    $json = substr($line, 6);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return;
    }

    $sawEvent = true;
    $onEvent($data);
}

function openai_responses_stream(array $params, callable $onEvent): void {
    $params['stream'] = true;
    $buffer = '';
    $sawEvent = false;

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
        CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$buffer, &$sawEvent, $onEvent) {
            $buffer .= $chunk;
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);
            foreach ($lines as $line) {
                dispatch_openai_stream_line($line, $onEvent, $sawEvent);
            }
            return strlen($chunk); // Must return length to continue
        },
    ]);

    $ok = curl_exec($ch);
    $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    if ($buffer !== '') {
        dispatch_openai_stream_line($buffer, $onEvent, $sawEvent);
    }

    if (curl_errno($ch)) {
        $message = 'OpenAI stream cURL error: ' . curl_error($ch);
        error_log($message);
        $onEvent([
            'type' => 'error',
            'message' => $message,
        ]);
    } 
    elseif (!$sawEvent) {
        $trimmedBuffer = trim($buffer);
        $decodedError = $trimmedBuffer !== '' ? json_decode($trimmedBuffer, true) : null;
        $errorMessage = $decodedError['error']['message'] ?? null;
        if ($errorMessage === null && $responseCode >= 400) {
            $errorMessage = 'OpenAI returned HTTP ' . $responseCode;
        }
        if ($errorMessage === null && $ok === false) {
            $errorMessage = 'OpenAI stream ended without any events.';
        }
        if ($errorMessage !== null) {
            error_log('OpenAI stream error response: ' . $errorMessage);
            $onEvent([
                'type' => 'error',
                'message' => $errorMessage,
                'status_code' => $responseCode,
            ]);
        }
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
