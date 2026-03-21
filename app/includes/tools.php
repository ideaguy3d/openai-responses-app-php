<?php
// includes/tools.php

function buildTools(array $toolsState): array {
    $tools = [];

    // Web Search
    if (!empty($toolsState['webSearchEnabled'])) {
        $webSearch = ['type' => 'web_search'];
        $loc = $toolsState['webSearchConfig']['user_location'] ?? null;
        if ($loc && ($loc['country'] || $loc['city'] || $loc['region'])) {
            $webSearch['user_location'] = $loc;
        }
        $tools[] = $webSearch;
    }

    // File Search
    if (!empty($toolsState['fileSearchEnabled']) && !empty($toolsState['vectorStore']['id'])) {
        $tools[] = [
            'type' => 'file_search',
            'vector_store_ids' => [$toolsState['vectorStore']['id']],
        ];
    }

    // Code Interpreter
    if (!empty($toolsState['codeInterpreterEnabled'])) {
        $tools[] = ['type' => 'code_interpreter', 'container' => ['type' => 'auto']];
    }

    // Custom Functions
    if (!empty($toolsState['functionsEnabled'])) {
        $tools[] = [
            'type' => 'function',
            'name' => 'get_weather',
            'description' => 'Get the weather for a given location',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'location' => ['type' => 'string', 'description' => 'Location to get weather for'],
                    'unit' => ['type' => 'string', 'description' => 'Unit', 'enum' => ['celsius', 'fahrenheit']],
                ],
                'required' => ['location', 'unit'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
        $tools[] = [
            'type' => 'function',
            'name' => 'get_joke',
            'description' => 'Get a programming joke',
            'parameters' => [
                'type' => 'object',
                'properties' => new stdClass(), // empty object
                'required' => [],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ];
    }

    // MCP
    if (!empty($toolsState['mcpEnabled']) && !empty($toolsState['mcpConfig']['server_url'])) {
        $mcp = [
            'type' => 'mcp',
            'server_label' => $toolsState['mcpConfig']['server_label'],
            'server_url' => $toolsState['mcpConfig']['server_url'],
        ];
        if (!empty($toolsState['mcpConfig']['skip_approval'])) {
            $mcp['require_approval'] = 'never';
        }
        if (!empty($toolsState['mcpConfig']['allowed_tools'])) {
            $mcp['allowed_tools'] = array_filter(array_map('trim',
                explode(',', $toolsState['mcpConfig']['allowed_tools'])
            ));
        }
        $tools[] = $mcp;
    }

    return $tools;
}
