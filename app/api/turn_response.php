<?php
// public/api/turn_response.php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/../includes/openai.php';
require_once dirname(__DIR__, 2) . '/includes/tools.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Read JSON body (like file_get_contents('php://input') — your old friend)
$input = json_decode(file_get_contents('php://input'), true);
$messages = $input['messages'] ?? [];
$toolsState = $input['toolsState'] ?? [];

// Build the tools array based on what the user has toggled on
$tools = buildTools($toolsState);

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