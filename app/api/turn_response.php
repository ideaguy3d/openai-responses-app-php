<?php

require_once __DIR__ . '/../../config_response_starter.php';
require_once __DIR__ . '/../includes/openai.php';
require_once __DIR__ . '/../includes/tools.php';

$log_what_js_sent = false; 
$log_validation_check = false; 

function validate_request_payload(array $payload): array {
    $errors = [];

    if (empty($payload['model']) || !is_string($payload['model'])) {
        $errors[] = 'model must be a non-empty string';
    }

    if (!isset($payload['input']) || !is_array($payload['input'])) {
        $errors[] = 'input must be an array of messages';
    } 
    else {
        foreach ($payload['input'] as $idx => $msg) {
            if (!is_array($msg)) {
                $errors[] = "input[$idx] must be an object";
                continue;
            }
            if (empty($msg['role']) || !is_string($msg['role'])) {
                $errors[] = "input[$idx].role must be a non-empty string";
            }
            if (!array_key_exists('content', $msg)) {
                $errors[] = "input[$idx].content is required";
            } elseif (!is_string($msg['content']) && !is_array($msg['content'])) {
                $errors[] = "input[$idx].content must be string or array";
            }
        }
    }

    if (empty($payload['instructions']) || !is_string($payload['instructions'])) {
        $errors[] = 'instructions must be a non-empty string';
    }

    if (isset($payload['tools']) && !is_array($payload['tools'])) {
        $errors[] = 'tools must be an array when provided';
    } 
    elseif (isset($payload['tools'])) {
        foreach ($payload['tools'] as $idx => $tool) {
            if (!is_array($tool) || empty($tool['type'])) {
                $errors[] = "tools[$idx] must be an object with a type";
                continue;
            }

            if ($tool['type'] === 'web_search' && isset($tool['user_location']['country'])) {
                $country = (string) $tool['user_location']['country'];
                if (!preg_match('/^[A-Z]{2}$/', $country)) {
                    $errors[] = "tools[$idx].user_location.country must be a 2-letter ISO code";
                }
            }
        }
    }

    return $errors;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Read JSON body 
$input = json_decode(file_get_contents('php://input'), true);
$messages = $input['messages'] ?? [];
$toolsState = $input['toolsState'] ?? [];

if ($log_what_js_sent) {
    // Keep a simple log of the incoming JSON just before we stream a response.
    $logFile  = __DIR__ . '/turn_response.log';
    $logEntry = sprintf("[%s] %s\n",
        date('c'),
        json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}


// Build the tools array based on what the user has toggled on
$tools = buildTools($toolsState);

// Validate the OpenAI request we are about to send and log the result
$requestPayload = [
    'model'               => OPENAI_MODEL,
    'input'               => $messages,
    'instructions'        => getDeveloperPrompt(),
    'tools'               => $tools,
    'parallel_tool_calls' => false,
];
$validationErrors = validate_request_payload($requestPayload);
$validationLog = [
    'timestamp' => date('c'),
    'valid'     => empty($validationErrors),
    'errors'    => $validationErrors,
    'payload'   => $requestPayload,
];

if ($log_validation_check) {
    file_put_contents(
        __DIR__ . '/request_validation.log',
        json_encode($validationLog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

// --- Set up SSE headers ---
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');  // Tell Nginx not to buffer (if behind Nginx)

// Disable PHP output buffering so data flows immediately
if (ob_get_level()) ob_end_clean();

// Disable Apache buffering for mod_php
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}

// --- Stream the response ---
openai_responses_stream(
    [
        'model'               => OPENAI_MODEL,
        'input'               => $messages,
        'instructions'        => getDeveloperPrompt(),
        'tools'               => $tools,
        'parallel_tool_calls' => false,
    ],
    function (array $eventData) {
        // Forward each OpenAI event to the browser as SSE
        echo "data: " . json_encode([
            'event' => $eventData['type'] ?? '',
            'data'  => $eventData,
        ]) . "\n\n";

        // Flush immediately so the browser gets it in real time
        if (ob_get_level()) ob_flush();
        flush();
    }
);

// Signal end of stream
echo "data: [DONE]\n\n";
if (ob_get_level()) ob_flush();
flush();
