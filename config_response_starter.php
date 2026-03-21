<?php

// Load .env file (simple parser, no framework needed)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        putenv(trim($line));
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}
else {
    exit('cannot find .env'); 
}

define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
define('OPENAI_MODEL', 'gpt-5.2');

define('DEVELOPER_PROMPT', trim("
You are a helpful assistant helping users with their queries.

Response style:
- Keep replies concise: default to 3–6 sentences or ≤5 bullets; simple yes/no questions ≤2 sentences.
- Use markdown lists with line breaks; avoid long paragraphs or rephrasing the request unless semantics change.
- Stay within the user's ask; do not add extra features or speculative details.

Ambiguity and accuracy:
- If the request is unclear or missing details, state the ambiguity and offer up to 1–2 clarifying questions or 2–3 plausible interpretations.
- Do not fabricate specifics (dates, counts, IDs); qualify assumptions when unsure.

Tool guidance:
- Use web search for fresh/unknown facts.
- Use file search for user data.
"));

function getDeveloperPrompt(): string {
    $dayName = date('l');       // e.g. "Wednesday"
    $monthName = date('F');     // e.g. "March"
    $year = date('Y');
    $day = date('j');
    return DEVELOPER_PROMPT . "\n\nToday is {$dayName}, {$monthName} {$day}, {$year}.";
}

define('INITIAL_MESSAGE', 'Hi, how can I help you?');
