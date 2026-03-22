<div class="h-full p-4 w-full bg-white">
  <div class="flex justify-center items-center size-full">
    <div class="flex grow flex-col h-full max-w-[750px] gap-2">
      <!-- Messages area -->
      <div id="chat-messages" class="h-[90vh] overflow-y-scroll px-10 flex flex-col">
        <div class="mt-auto space-y-5 pt-4">
          <!-- Initial assistant message -->
          <div class="max-w-none text-sm leading-7 text-stone-700">
            <?= htmlspecialchars(INITIAL_MESSAGE) ?>
          </div>
        </div>
      </div>
      <!-- Input area -->
      <div class="flex-1 p-4 px-10">
        <div class="flex items-center">
          <div class="flex w-full items-center pb-4 md:pb-1">
            <div class="flex w-full flex-col gap-1.5 rounded-[20px] p-2.5 pl-1.5 transition-colors bg-white border border-stone-200 shadow-sm">
              <div class="flex items-end gap-1.5 md:gap-2 pl-4">
                <div class="flex min-w-0 flex-1 flex-col">
                  <textarea
                    id="chat-input"
                    rows="2"
                    placeholder="Message..."
                    class="mb-2 resize-none border-0 focus:outline-none text-sm bg-transparent px-0 pb-6 pt-2"
                  ></textarea>
                </div>
                <button
                  id="send-button"
                  onclick="sendMessage()"
                  disabled
                  class="flex size-8 items-end justify-center rounded-full bg-black text-white transition-colors hover:opacity-70 disabled:bg-[#D7D7D7] disabled:text-[#f4f4f4]"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32">
                    <path fill="currentColor" fill-rule="evenodd"
                      d="M15.192 8.906a1.143 1.143 0 0 1 1.616 0l5.143 5.143a1.143 1.143 0 0 1-1.616 1.616l-3.192-3.192v9.813a1.143 1.143 0 0 1-2.286 0v-9.813l-3.192 3.192a1.143 1.143 0 1 1-1.616-1.616z"
                      clip-rule="evenodd"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
