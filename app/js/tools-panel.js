// public/js/tools-panel.js

// ---- Default state ----
const DEFAULT_TOOLS_STATE = {
    webSearchEnabled: false,
    fileSearchEnabled: false,
    functionsEnabled: true,
    codeInterpreterEnabled: false,
    mcpEnabled: false,
    googleIntegrationEnabled: false,
    vectorStore: { id: '', name: '' },
    webSearchConfig: { user_location: { type: 'approximate', country: '', city: '', region: '' } },
    mcpConfig: { server_label: '', server_url: '', allowed_tools: '', skip_approval: true },
};

function loadToolsState() {
    return JSON.parse(localStorage.getItem('toolsState') || JSON.stringify(DEFAULT_TOOLS_STATE));
}

function saveToolsState(state) {
    localStorage.setItem('toolsState', JSON.stringify(state));
}

// ---- Initialize everything on page load ----
document.addEventListener('DOMContentLoaded', () => {
    const state = loadToolsState();

    // 1. Set up all toggle switches
    document.querySelectorAll('[data-tool-toggle]').forEach(toggle => {
        const key = toggle.dataset.toolToggle;
        toggle.checked = state[key] || false;

        // Show/hide the sub-panel based on initial state
        const subPanel = document.querySelector(`[data-panel-for="${key}"]`);
        if (subPanel) {
            subPanel.style.display = toggle.checked ? '' : 'none';
        }

        // Listen for changes
        toggle.addEventListener('change', () => {
            state[key] = toggle.checked;
            saveToolsState(state);

            // Show/hide sub-panel
            if (subPanel) {
                subPanel.style.display = toggle.checked ? '' : 'none';
            }
        });
    });

    // 2. Populate web search fields from saved state
    const loc = state.webSearchConfig?.user_location || {};
    const countryInput = document.getElementById('ws-country');
    const regionInput = document.getElementById('ws-region');
    const cityInput = document.getElementById('ws-city');
    if (countryInput) countryInput.value = loc.country || '';
    if (regionInput) regionInput.value = loc.region || '';
    if (cityInput) cityInput.value = loc.city || '';

    // 3. Populate MCP fields from saved state
    const mcp = state.mcpConfig || {};
    const mcpLabel = document.getElementById('mcp-label');
    const mcpUrl = document.getElementById('mcp-url');
    const mcpAllowed = document.getElementById('mcp-allowed');
    const mcpSkip = document.getElementById('mcp-skip');
    if (mcpLabel) mcpLabel.value = mcp.server_label || '';
    if (mcpUrl) mcpUrl.value = mcp.server_url || '';
    if (mcpAllowed) mcpAllowed.value = mcp.allowed_tools || '';
    if (mcpSkip) mcpSkip.checked = mcp.skip_approval !== false;

    // 4. Show/hide vector store display vs input
    updateVectorStoreUI(state);

    // 5. Check Google OAuth status
    checkGoogleStatus();
});

// ---- Vector Store ----
function updateVectorStoreUI(state) {
    const display = document.getElementById('vector-store-display');
    const input = document.getElementById('vector-store-input');
    const idSpan = document.getElementById('vector-store-id');

    if (state.vectorStore && state.vectorStore.id) {
        display.style.display = 'flex';
        input.style.display = 'none';
        idSpan.textContent = state.vectorStore.id;
    } else {
        display.style.display = 'none';
        input.style.display = 'flex';
    }
}

async function addVectorStore() {
    const storeId = document.getElementById('new-store-id').value.trim();
    if (!storeId) return;

    try {
        const res = await fetch('/api/vector_stores/retrieve_store.php?vector_store_id=' + encodeURIComponent(storeId));
        const data = await res.json();
        if (data.id) {
            const state = loadToolsState();
            state.vectorStore = { id: data.id, name: data.name || '' };
            saveToolsState(state);
            updateVectorStoreUI(state);
        } else {
            alert('Vector store not found');
        }
    } catch (err) {
        alert('Error retrieving vector store');
        console.error(err);
    }
}

function unlinkVectorStore() {
    const state = loadToolsState();
    state.vectorStore = { id: '', name: '' };
    saveToolsState(state);
    updateVectorStoreUI(state);
    document.getElementById('new-store-id').value = '';
}

// ---- File Upload ----
async function handleFileUpload(input) {
    const file = input.files[0];
    if (!file) return;

    const statusEl = document.getElementById('upload-status');
    statusEl.style.display = 'block';
    statusEl.textContent = 'Uploading ' + file.name + '...';

    try {
        // Read file as base64
        const arrayBuffer = await file.arrayBuffer();
        const bytes = new Uint8Array(arrayBuffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        const base64Content = btoa(binary);

        // 1. Upload file to OpenAI
        const uploadRes = await fetch('/api/vector_stores/upload_file.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fileObject: { name: file.name, content: base64Content } }),
        });
        const uploadData = await uploadRes.json();
        if (!uploadData.id) throw new Error('Upload failed');

        // 2. If no vector store linked, create one
        let state = loadToolsState();
        let vectorStoreId = state.vectorStore?.id;

        if (!vectorStoreId) {
            const createRes = await fetch('/api/vector_stores/create_store.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: 'Default store' }),
            });
            const createData = await createRes.json();
            vectorStoreId = createData.id;
            state.vectorStore = { id: vectorStoreId, name: createData.name || 'Default store' };
            saveToolsState(state);
            updateVectorStoreUI(state);
        }

        // 3. Add file to vector store
        await fetch('/api/vector_stores/add_file.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fileId: uploadData.id, vectorStoreId }),
        });

        statusEl.textContent = 'Uploaded ' + file.name + ' successfully!';
        setTimeout(() => { statusEl.style.display = 'none'; }, 3000);
    } catch (err) {
        statusEl.textContent = 'Error uploading file. Please try again.';
        console.error(err);
    }

    input.value = ''; // Reset file input
}

// ---- Web Search Config ----
function updateWebSearchConfig() {
    const state = loadToolsState();
    state.webSearchConfig = {
        user_location: {
            type: 'approximate',
            country: document.getElementById('ws-country')?.value || '',
            region: document.getElementById('ws-region')?.value || '',
            city: document.getElementById('ws-city')?.value || '',
        },
    };
    saveToolsState(state);
}

function clearWebSearchLocation() {
    document.getElementById('ws-country').value = '';
    document.getElementById('ws-region').value = '';
    document.getElementById('ws-city').value = '';
    updateWebSearchConfig();
}

// ---- MCP Config ----
function updateMcpConfig() {
    const state = loadToolsState();
    state.mcpConfig = {
        server_label: document.getElementById('mcp-label')?.value || '',
        server_url: document.getElementById('mcp-url')?.value || '',
        allowed_tools: document.getElementById('mcp-allowed')?.value || '',
        skip_approval: document.getElementById('mcp-skip')?.checked ?? true,
    };
    saveToolsState(state);
}

function clearMcpConfig() {
    document.getElementById('mcp-label').value = '';
    document.getElementById('mcp-url').value = '';
    document.getElementById('mcp-allowed').value = '';
    document.getElementById('mcp-skip').checked = true;
    updateMcpConfig();
}

// ---- Google Integration ----
async function checkGoogleStatus() {
    try {
        const res = await fetch('/api/google/status.php');
        const data = await res.json();
        const toggle = document.getElementById('google-toggle');

        if (data.oauthConfigured) {
            // OAuth env vars are set — enable the toggle
            toggle.disabled = false;

            if (data.connected) {
                document.getElementById('google-connected').style.display = '';
                document.getElementById('google-not-connected').style.display = 'none';
                document.getElementById('google-not-configured').style.display = 'none';
            } else {
                document.getElementById('google-connected').style.display = 'none';
                document.getElementById('google-not-connected').style.display = '';
                document.getElementById('google-not-configured').style.display = 'none';
            }
        } else {
            // OAuth not configured — disable toggle and show message
            toggle.disabled = true;
            document.getElementById('google-connected').style.display = 'none';
            document.getElementById('google-not-connected').style.display = 'none';
            document.getElementById('google-not-configured').style.display = '';
        }
    } catch {
        // API not available — hide Google section
        document.getElementById('google-toggle').disabled = true;
    }
}