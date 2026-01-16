<!-- Success Alert Modal -->
<div x-show="showSuccessModal" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90"
    class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none" x-cloak>
    <div
        class="glass-card bg-emerald-500/10 border-emerald-500/50 p-8 rounded-2xl shadow-2xl backdrop-blur-xl flex flex-col items-center gap-4 text-center max-w-sm mx-4 transform modal-body">
        <div
            class="w-16 h-16 rounded-full bg-emerald-500 flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Saved!</h3>
            <p class="text-gray-600 dark:text-gray-300" x-text="successMessage">The response has been added to your
                Knowledge Base.</p>
        </div>
    </div>
</div>
