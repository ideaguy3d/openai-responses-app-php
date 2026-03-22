<!-- templates/tools-panel.php -->
<div class="h-full p-8 w-full bg-[#f9f9f9] rounded-t-xl md:rounded-none border-l border-stone-100">
  <div class="flex flex-col overflow-y-scroll h-full">

    <!-- ============================================================ -->
    <!-- File Search -->
    <!-- ============================================================ -->
    <div class="space-y-4 mb-6" id="panel-fileSearch">
      <div class="flex justify-between items-center">
        <h1 class="text-black font-medium" title="Allows to search a knowledge base (vector store)">File Search</h1>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" data-tool-toggle="fileSearchEnabled">
          <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
        </label>
      </div>
      <!-- File Search sub-panel (shown when enabled) -->
      <div class="mt-1 tool-sub-panel" data-panel-for="fileSearchEnabled" style="display:none;">
        <div class="text-sm text-zinc-500">
          Upload a file to create a new vector store, or use an existing one.
        </div>
        <div class="flex items-center gap-2 mt-2 h-10">
          <div class="flex items-center gap-2 w-full">
            <div class="text-sm font-medium w-24 text-nowrap">Vector store</div>
            <!-- Shows vector store ID if linked, otherwise shows input -->
            <div id="vector-store-display" class="flex items-center gap-2 min-w-0" style="display:none;">
              <span id="vector-store-id" class="text-zinc-400 text-xs font-mono flex-1 text-ellipsis truncate"></span>
              <button onclick="unlinkVectorStore()" class="text-zinc-400 hover:text-zinc-700 transition-all" title="Unlink vector store">&times;</button>
            </div>
            <div id="vector-store-input" class="flex items-center gap-2">
              <input type="text" id="new-store-id" placeholder="ID (vs_XXXX...)"
                class="border border-zinc-300 rounded text-sm bg-white px-2 py-1"
                onkeydown="if(event.key==='Enter') addVectorStore()">
              <span class="text-zinc-400 text-sm px-1 transition-colors hover:text-zinc-600 cursor-pointer" onclick="addVectorStore()">Add</span>
            </div>
          </div>
        </div>
        <!-- File upload button -->
        <div class="flex mt-4">
          <label class="bg-white rounded-full flex items-center justify-center py-1 px-3 border border-zinc-200 gap-1 font-medium text-sm cursor-pointer hover:bg-zinc-50 transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Upload
            <input type="file" id="file-upload-input" class="hidden" onchange="handleFileUpload(this)">
          </label>
        </div>
        <div id="upload-status" class="text-xs text-zinc-400 mt-2" style="display:none;"></div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- Web Search -->
    <!-- ============================================================ -->
    <div class="space-y-4 mb-6" id="panel-webSearch">
      <div class="flex justify-between items-center">
        <h1 class="text-black font-medium" title="Allows to search the web">Web Search</h1>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" data-tool-toggle="webSearchEnabled">
          <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
        </label>
      </div>
      <div class="mt-1 tool-sub-panel" data-panel-for="webSearchEnabled" style="display:none;">
        <div class="flex items-center justify-between">
          <div class="text-zinc-600 text-sm">User's location</div>
          <span class="text-zinc-400 text-sm px-1 transition-colors hover:text-zinc-600 cursor-pointer" onclick="clearWebSearchLocation()">Clear</span>
        </div>
        <div class="mt-3 space-y-3 text-zinc-400">
          <div class="flex items-center gap-2">
            <label for="ws-country" class="text-sm w-20">Country</label>
            <input id="ws-country" type="text" placeholder="US"
              class="bg-white border text-sm flex-1 text-zinc-900 placeholder:text-zinc-400 rounded px-2 py-1"
              data-ws-field="country" onchange="updateWebSearchConfig()">
          </div>
          <div class="flex items-center gap-2">
            <label for="ws-region" class="text-sm w-20">Region</label>
            <input id="ws-region" type="text" placeholder="Region"
              class="bg-white border text-sm flex-1 text-zinc-900 placeholder:text-zinc-400 rounded px-2 py-1"
              data-ws-field="region" onchange="updateWebSearchConfig()">
          </div>
          <div class="flex items-center gap-2">
            <label for="ws-city" class="text-sm w-20">City</label>
            <input id="ws-city" type="text" placeholder="City"
              class="bg-white border text-sm flex-1 text-zinc-900 placeholder:text-zinc-400 rounded px-2 py-1"
              data-ws-field="city" onchange="updateWebSearchConfig()">
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- Code Interpreter -->
    <!-- ============================================================ -->
    <div class="space-y-4 mb-6" id="panel-codeInterpreter">
      <div class="flex justify-between items-center">
        <h1 class="text-black font-medium" title="Allows the assistant to run Python code">Code Interpreter</h1>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" data-tool-toggle="codeInterpreterEnabled">
          <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
        </label>
      </div>
      <!-- No sub-panel for Code Interpreter (just a toggle) -->
    </div>

    <!-- ============================================================ -->
    <!-- Functions -->
    <!-- ============================================================ -->
    <div class="space-y-4 mb-6" id="panel-functions">
      <div class="flex justify-between items-center">
        <h1 class="text-black font-medium" title="Allows to use locally defined functions">Functions</h1>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" data-tool-toggle="functionsEnabled" checked>
          <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
        </label>
      </div>
      <div class="mt-1 tool-sub-panel" data-panel-for="functionsEnabled">
        <div class="flex flex-col space-y-4">
          <!-- get_weather -->
          <div class="flex items-start gap-2">
            <div class="bg-blue-100 text-blue-500 rounded-md p-1">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </div>
            <div class="text-zinc-800 font-mono text-sm mt-0.5">
              get_weather(
              <div class="ml-4">
                <div class="flex items-center text-xs space-x-2 my-1">
                  <span class="text-blue-500">location:</span>
                  <span class="text-zinc-400">string</span>
                </div>
                <div class="flex items-center text-xs space-x-2 my-1">
                  <span class="text-blue-500">unit:</span>
                  <span class="text-zinc-400">string</span>
                </div>
              </div>
              )
            </div>
          </div>
          <!-- get_joke -->
          <div class="flex items-start gap-2">
            <div class="bg-blue-100 text-blue-500 rounded-md p-1">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            </div>
            <div class="text-zinc-800 font-mono text-sm mt-0.5">
              get_joke()
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MCP (Model Context Protocol) -->
    <!-- ============================================================ -->
    <div class="space-y-4 mb-6" id="panel-mcp">
      <div class="flex justify-between items-center">
        <h1 class="text-black font-medium" title="Allows to call tools via remote MCP server">MCP</h1>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" data-tool-toggle="mcpEnabled">
          <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
        </label>
      </div>
      <div class="mt-1 tool-sub-panel" data-panel-for="mcpEnabled" style="display:none;">
        <div class="flex items-center justify-between">
          <div class="text-zinc-600 text-sm">Server details</div>
          <span class="text-zinc-400 text-sm px-1 transition-colors hover:text-zinc-600 cursor-pointer" onclick="clearMcpConfig()">Clear</span>
        </div>
        <div class="mt-3 space-y-3 text-zinc-400">
          <div class="flex items-center gap-2">
            <label for="mcp-label" class="text-sm w-24">Label</label>
            <input id="mcp-label" type="text" placeholder="deepwiki"
              class="bg-white border text-sm flex-1 text-zinc-900 placeholder:text-zinc-400 rounded px-2 py-1"
              data-mcp-field="server_label" onchange="updateMcpConfig()">
          </div>
          <div class="flex items-center gap-2">
            <label for="mcp-url" class="text-sm w-24">URL</label>
            <input id="mcp-url" type="text" placeholder="https://example.com/mcp"
              class="bg-white border text-sm flex-1 text-zinc-900 placeholder:text-zinc-400 rounded px-2 py-1"
              data-mcp-field="server_url" onchange="updateMcpConfig()">
          </div>
          <div class="flex items-center gap-2">
            <label for="mcp-allowed" class="text-sm w-24">Allowed</label>
            <input id="mcp-allowed" type="text" placeholder="tool1,tool2"
              class="bg-white border text-sm flex-1 text-zinc-900 placeholder:text-zinc-400 rounded px-2 py-1"
              data-mcp-field="allowed_tools" onchange="updateMcpConfig()">
          </div>
          <div class="flex items-center gap-2">
            <label for="mcp-skip" class="text-sm w-24">Skip approval</label>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="mcp-skip" class="sr-only peer" data-mcp-field="skip_approval" checked onchange="updateMcpConfig()">
              <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- Google Integration -->
    <!-- ============================================================ -->
    <div class="space-y-4 mb-6" id="panel-google">
      <div class="flex justify-between items-center">
        <h1 class="text-black font-medium" title="Connect your Google account to enable Gmail and Calendar features.">Google Integration</h1>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" data-tool-toggle="googleIntegrationEnabled" id="google-toggle" disabled>
          <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black peer-disabled:opacity-50"></div>
        </label>
      </div>
      <div class="mt-1 tool-sub-panel" data-panel-for="googleIntegrationEnabled" style="display:none;">
        <!-- Google connection status (populated by JS on page load) -->
        <div id="google-not-connected" style="display:none;">
          <a href="<?= APP_BASE_PATH ?>/api/google/auth.php">
            <button class="bg-black text-white text-sm px-4 py-2 rounded-md hover:opacity-70 transition-all">
              Connect Google Integration
            </button>
          </a>
        </div>
        <div id="google-connected" style="display:none;">
          <div class="flex items-center gap-2 rounded-lg shadow-sm border p-3 bg-white">
            <div class="bg-blue-100 text-blue-500 rounded-md p-1">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <p class="text-sm text-zinc-800">Google OAuth set up</p>
          </div>
        </div>
        <div id="google-not-configured" style="display:none;">
          <button disabled class="bg-black text-white text-sm px-4 py-2 rounded-md opacity-50 cursor-not-allowed">
            Connect Google Integration
          </button>
          <p class="text-xs text-zinc-400 mt-2">
            GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI must be set in .env to use Google Integration.
          </p>
        </div>
      </div>
    </div>

  </div>
</div>
