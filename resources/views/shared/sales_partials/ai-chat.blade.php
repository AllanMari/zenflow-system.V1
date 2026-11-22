<div id="aiChatWidget" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 no-print">
    <button onclick="toggleAiChat()" class="group flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white rounded-full pl-3.5 pr-4 py-3 sm:pl-4 sm:pr-5 sm:py-3.5 shadow-xl transition-all hover:scale-105 hover:shadow-2xl">
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-300 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
        </span>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        <span class="text-sm font-bold">Ask Mari</span>
    </button>

    <div id="aiChatPanel" class="hidden absolute bottom-16 sm:bottom-20 right-0 w-[calc(100vw-2rem)] max-w-[380px] sm:w-96 bg-white dark:bg-neutral-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-neutral-700 overflow-hidden max-h-[82vh] sm:max-h-[520px] flex flex-col">
        <div class="bg-gradient-to-r from-brand-600 to-brand-500 p-3.5 sm:p-4 flex justify-between items-center shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="relative">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Mari&backgroundColor=14b8a6" alt="Mari" class="w-9 h-9 rounded-full bg-white/20 p-0.5">
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full {{ ($aiOnline ?? false) ? 'bg-green-400 border-2 border-brand-600' : 'bg-red-400 border-2 border-brand-600' }}"></span>
                </div>
                <div>
                    <span class="text-white text-sm font-bold block leading-tight">Mari — AI Advisor</span>
                    <span class="text-[10px] text-brand-100 font-medium">{{ ($aiOnline ?? false) ? 'Online & Ready' : 'Offline Mode' }}</span>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="clearAiChat()" class="text-brand-100 hover:text-white text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg hover:bg-white/10 transition" title="Clear conversation">Clear</button>
                <button onclick="toggleAiChat()" class="text-white hover:text-brand-100 p-1 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div id="aiChatMessages" class="p-3.5 sm:p-4 flex-1 h-72 sm:h-80 overflow-y-auto space-y-3 bg-gray-50/50 dark:bg-neutral-900/50">
            <div class="bg-brand-50 dark:bg-brand-900/20 p-3.5 sm:p-4 rounded-xl text-xs text-gray-600 dark:text-neutral-300 border border-brand-100 dark:border-brand-800">
                <p class="font-bold text-brand-700 dark:text-brand-300 mb-1 text-sm">👋 Hey there! I'm Mari, your spa business advisor.</p>
                <p class="leading-relaxed">I analyze your sales, appointment trends, and revenue metrics. Ask me about your numbers or business strategy! ✨</p>
            </div>
        </div>

        <div id="aiQuickChips" class="px-3 pt-2 pb-1.5 flex flex-wrap gap-1.5 border-t border-gray-100 dark:border-neutral-700/60 bg-gray-50/70 dark:bg-neutral-900/40 shrink-0">
            <button type="button" onclick="askMariQuick('How can we boost revenue on our slowest days?')" class="text-[11px] bg-white dark:bg-neutral-800 text-gray-700 dark:text-neutral-200 border border-gray-200 dark:border-neutral-700 rounded-full px-2.5 py-1 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-900/30 transition shadow-2xs font-medium">💡 Boost slow days</button>
            <button type="button" onclick="askMariQuick('How do we reduce lost revenue from no-shows and cancellations?')" class="text-[11px] bg-white dark:bg-neutral-800 text-gray-700 dark:text-neutral-200 border border-gray-200 dark:border-neutral-700 rounded-full px-2.5 py-1 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-900/30 transition shadow-2xs font-medium">📉 Cut cancellation loss</button>
            <button type="button" onclick="askMariQuick('What service package or promotion should we launch next?')" class="text-[11px] bg-white dark:bg-neutral-800 text-gray-700 dark:text-neutral-200 border border-gray-200 dark:border-neutral-700 rounded-full px-2.5 py-1 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-900/30 transition shadow-2xs font-medium">🎯 Promo idea</button>
            <button type="button" onclick="askMariQuick('How is our customer retention and repeat client rate?')" class="text-[11px] bg-white dark:bg-neutral-800 text-gray-700 dark:text-neutral-200 border border-gray-200 dark:border-neutral-700 rounded-full px-2.5 py-1 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-900/30 transition shadow-2xs font-medium">👥 Repeat clients</button>
        </div>

        <div class="p-2.5 sm:p-3 border-t border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 shrink-0">
            <form onsubmit="sendAiQuestion(event)" class="flex gap-2">
                <input type="text" id="aiQuestionInput" placeholder="Ask Mari about sales, revenue, booking trends..."
                    class="flex-1 text-xs sm:text-sm border-gray-200 dark:border-neutral-700 rounded-lg px-3 py-2 sm:px-3.5 sm:py-2.5 dark:bg-neutral-900 dark:text-neutral-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition shadow-sm"
                    maxlength="200" autocomplete="off">
                <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white p-2 sm:p-2.5 rounded-lg transition shadow-sm flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>