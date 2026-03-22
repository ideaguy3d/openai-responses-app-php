// public/js/chat.js
// replaces `lib/assistant.ts` + `components/assistant.tsx` + `stores/useConversationStore.ts`

// --- State (replaces Zustand stores) ---
const state = {
    conversationItems: [],  // Full conversation history sent to API
    chatMessages: [],       // Messages displayed in UI
    isLoading: false,
};

function appUrl(path) {
    const basePath = (window.APP_BASE_PATH || '').replace(/\/$/, '');
    const normalizedPath = path.replace(/^\/+/, '');
    return basePath ? `${basePath}/${normalizedPath}` : `/${normalizedPath}`;
}

// --- DOM references ---
const chatMessages = document.getElementById('chat-messages');
const chatInput = document.getElementById('chat-input');
const sendButton = document.getElementById('send-button');

// Enable/disable send button based on input
chatInput.addEventListener('input', () => {
    sendButton.disabled = !chatInput.value.trim();
});

// Enter to send (Shift+Enter for new line)
chatInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (chatInput.value.trim()) sendMessage();
    }
});


// --- Get current tool settings from the tools panel ---
function getToolsState() {
    // Read toggle states from the DOM or localStorage
    // (tools-panel.js manages these)
    return JSON.parse(localStorage.getItem('toolsState') || JSON.stringify({
        webSearchEnabled: false,
        fileSearchEnabled: false,
        functionsEnabled: true,
        codeInterpreterEnabled: false,
        mcpEnabled: false,
        vectorStore: { id: '', name: '' },
        webSearchConfig: { user_location: { type: 'approximate', country: '', city: '', region: '' } },
        mcpConfig: { server_label: '', server_url: '', allowed_tools: '', skip_approval: true },
    }));
}


// --- Send a message ---
async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;

    chatInput.value = '';
    setComposerDisabled(true);

    // Add user message to UI
    appendMessage('user', message);

    // Add to conversation history (API format)
    state.conversationItems.push({
        role: 'user',
        content: message,
    });

    // Show loading indicator
    showLoading(true);

    // Call the PHP API with streaming
    await processMessages();
}


// --- Core: Call PHP API and read SSE stream ---
async function processMessages() {
    const toolsState = getToolsState();

    try {
        const response = await fetch(appUrl('api/turn_response.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                messages: state.conversationItems,
                toolsState: toolsState,
            }),
        });

        if (!response.ok) {
            console.error('API error:', response.status);
            appendErrorMessage('API error: ' + response.status);
            showLoading(false);
            setComposerDisabled(false);
            return;
        }

        if (!response.body) {
            appendErrorMessage('The response stream was empty.');
            showLoading(false);
            setComposerDisabled(false);
            return;
        }

        // Read the SSE stream
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let assistantText = '';
        let assistantEl = null;  // The DOM element we're streaming into

        while (true) {
            const { value, done } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n\n');
            buffer = lines.pop() || '';

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                const dataStr = line.slice(6);
                if (dataStr === '[DONE]') break;

                let eventPayload;
                try {
                    eventPayload = JSON.parse(dataStr);
                } catch (error) {
                    console.error('Invalid SSE payload:', dataStr, error);
                    continue;
                }
                const { event, data } = eventPayload;
                
                switch (event) {
                    case 'response.output_text.delta': {
                        // Append streaming text character-by-character
                        showLoading(false);
                        const delta = data.delta || '';
                        assistantText += delta;

                        if (!assistantEl) {
                            assistantEl = appendMessage('assistant', '');
                        }
                        assistantEl.innerHTML = renderAssistantHtml(assistantText);
                        scrollToBottom();
                        break;
                    }

                    case 'response.output_item.added': {
                        const item = data.item;
                        if (!item) break;
                        showLoading(false);

                        if (item.type === 'web_search_call') {
                            appendToolCall('Web Search', 'Searching...', item.id);
                        } else if (item.type === 'file_search_call') {
                            appendToolCall('File Search', 'Searching...', item.id);
                        } else if (item.type === 'function_call') {
                            appendToolCall('Function: ' + item.name, 'Running...', item.id);
                        }
                        break;
                    }

                    case 'response.output_item.done': {
                        const item = data.item;
                        if (!item) break;

                        // If it's a function call, execute it and loop
                        if (item.type === 'function_call') {
                            state.conversationItems.push(item);
                            const result = await executeFunction(item.name, item.arguments);
                            updateToolCall(item.id, 'Completed');

                            // Add function result to conversation
                            state.conversationItems.push({
                                type: 'function_call_output',
                                call_id: item.call_id,
                                output: JSON.stringify(result),
                            });

                            // Reset for next assistant message
                            assistantText = '';
                            assistantEl = null;

                            // Process again (tool loop — like the Next.js recursive call)
                            await processMessages();
                            return;
                        }

                        // Mark other tool calls as completed
                        if (item.type === 'web_search_call' || item.type === 'file_search_call') {
                            state.conversationItems.push(item);
                            updateToolCall(item.id, 'Completed');
                        } else if (item.type === 'message') {
                            state.conversationItems.push(item);
                            if (!assistantText) {
                                const messageText = extractOutputText(item);
                                if (messageText) {
                                    assistantText = messageText;
                                    if (!assistantEl) {
                                        assistantEl = appendMessage('assistant', messageText);
                                    }
                                }
                            }
                        }
                        break;
                    }

                    case 'error':
                    case 'response.failed':
                    case 'response.incomplete': {
                        const errorMessage = data?.message || data?.error?.message || 'The response failed before completion.';
                        appendErrorMessage(errorMessage);
                        showLoading(false);
                        setComposerDisabled(false);
                        return;
                    }

                    case 'response.completed': {
                        showLoading(false);
                        break;
                    }
                }
            }
        }

    } catch (error) {
        console.error('Error processing messages:', error);
        appendErrorMessage(error.message || 'Error processing messages.');
    }

    showLoading(false);
    setComposerDisabled(false);
}


// --- Execute a local function call ---
async function executeFunction(name, argsJson) {
    const args = JSON.parse(argsJson || '{}');

    switch (name) {
        case 'get_weather': {
            const params = new URLSearchParams({ location: args.location, unit: args.unit });
            const res = await fetch(appUrl('api/functions/get_weather.php?' + params));
            return parseJsonResponse(res, 'Weather request failed');
        }
        case 'get_joke': {
            const res = await fetch(appUrl('api/functions/get_joke.php'));
            return parseJsonResponse(res, 'Joke request failed');
        }
        default:
            return { error: 'Unknown function: ' + name };
    }
}


// --- DOM Helper Functions ---
function appendMessage(role, text) {
    const container = chatMessages.querySelector('.space-y-5');
    const div = document.createElement('div');

    if (role === 'user') {
        div.className = 'flex justify-end';
        div.innerHTML = `<div class="bg-stone-100 rounded-2xl px-4 py-2 max-w-[80%]">
            <p class="text-sm">${escapeHtml(text)}</p>
        </div>`;
    } else {
        div.className = 'max-w-none text-sm leading-7 text-stone-700';
        div.innerHTML = renderAssistantHtml(text);
    }

    container.appendChild(div);
    scrollToBottom();
    return role === 'assistant' ? div : null;
}

function appendToolCall(name, status, id) {
    const container = chatMessages.querySelector('.space-y-5');
    const div = document.createElement('div');
    div.id = 'tool-' + id;
    div.className = 'text-xs text-stone-400 flex items-center gap-2';
    div.innerHTML = `<span class="animate-pulse">&#9679;</span> ${escapeHtml(name)}: ${escapeHtml(status)}`;
    container.appendChild(div);
    scrollToBottom();
}

function appendErrorMessage(message) {
    const text = typeof message === 'string' ? message : 'Something went wrong.';
    appendMessage('assistant', 'Error: ' + text);
}

function updateToolCall(id, status) {
    const el = document.getElementById('tool-' + id);
    if (el) {
        el.querySelector('span')?.classList.remove('animate-pulse');
        el.innerHTML = el.innerHTML.replace(/: .*$/, ': ' + escapeHtml(status));
    }
}

function showLoading(show) {
    state.isLoading = show;
    let loader = document.getElementById('loading-indicator');
    if (show && !loader) {
        const container = chatMessages.querySelector('.space-y-5');
        loader = document.createElement('div');
        loader.id = 'loading-indicator';
        loader.className = 'text-sm text-stone-400 animate-pulse';
        loader.textContent = 'Thinking...';
        container.appendChild(loader);
        scrollToBottom();
    } else if (!show && loader) {
        loader.remove();
    }
}

function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function renderAssistantHtml(text) {
    if (!text) {
        return '';
    }

    const blocks = text.trim().split(/\n\s*\n/);
    return blocks.map(renderAssistantBlock).join('');
}

function renderAssistantBlock(block) {
    const lines = block.split('\n').map(line => line.trim()).filter(Boolean);
    if (!lines.length) {
        return '';
    }

    if (lines.every(line => /^[-*]\s+/.test(line))) {
        const items = lines
            .map(line => line.replace(/^[-*]\s+/, ''))
            .map(item => `<li class="ml-5 list-disc pl-1">${renderInlineFormatting(item)}</li>`)
            .join('');
        return `<ul class="my-3 space-y-2">${items}</ul>`;
    }

    if (lines.every(line => /^\d+\.\s+/.test(line))) {
        const items = lines
            .map(line => line.replace(/^\d+\.\s+/, ''))
            .map(item => `<li class="ml-5 list-decimal pl-1">${renderInlineFormatting(item)}</li>`)
            .join('');
        return `<ol class="my-3 space-y-2">${items}</ol>`;
    }

    return `<p class="my-3">${lines.map(renderInlineFormatting).join('<br>')}</p>`;
}

function renderInlineFormatting(text) {
    let html = escapeHtml(text);

    html = html.replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold text-stone-900">$1</strong>');
    html = html.replace(/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-sky-700 underline decoration-sky-300 underline-offset-2">$1</a>');

    return html;
}

function extractOutputText(item) {
    if (!Array.isArray(item.content)) return '';
    return item.content
        .filter(part => part.type === 'output_text' && typeof part.text === 'string')
        .map(part => part.text)
        .join('');
}

async function parseJsonResponse(response, fallbackMessage) {
    let data = null;
    try {
        data = await response.json();
    } catch (error) {
        return { error: fallbackMessage };
    }

    if (!response.ok) {
        return { error: data?.error || fallbackMessage };
    }

    return data;
}

function setComposerDisabled(disabled) {
    chatInput.disabled = disabled;
    sendButton.disabled = disabled || !chatInput.value.trim();
}
