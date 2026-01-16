<!-- Response Details Modal -->
<div x-show="viewModal" class="fixed inset-0 z-[100] flex items-center justify-center p-2" x-cloak>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        @click="viewModal = false" x-show="viewModal" x-transition:enter="duration-300 ease-out"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="duration-200 ease-in" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"></div>

    <template x-if="itemToView">
        <div class="glass-card relative w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col shadow-2xl ring-1 ring-white/10"
            x-show="viewModal" x-transition:enter="duration-300 ease-out"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            <div class="modal-header">
                <div class="flex items-center gap-3">
                    <h3 class="modal-title">Response Details</h3>
                    <span class="info-pill bg-sky-100 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400"
                        x-text="itemToView.modelA_name || itemToView.modelA || 'Unknown Model'"></span>
                </div>
                <button @click="viewModal = false"
                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-black/5 dark:hover:bg-white/5 transition-colors text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="modal-body custom-scrollbar">
                <div>
                    <h4
                        class="text-xs uppercase tracking-widest font-bold text-gray-400 mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Original Input
                    </h4>
                    <div class="p-6 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-gray-700/50 text-sm md:text-base text-gray-600 dark:text-gray-300 font-serif leading-relaxed"
                        x-text="itemToView.original"></div>
                </div>

                <div class="flex items-center gap-4 opacity-50">
                    <div class="h-px bg-gray-300 dark:bg-gray-700 flex-1"></div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                    <div class="h-px bg-gray-300 dark:bg-gray-700 flex-1"></div>
                </div>

                <div>
                    <h4
                        class="text-xs uppercase tracking-widest font-bold text-sky-500 mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Rephrased Output
                    </h4>
                    <div
                        class="p-6 rounded-2xl bg-gradient-to-br from-sky-50 dark:from-sky-900/10 to-indigo-50 dark:to-indigo-900/10 border border-sky-100 dark:border-sky-500/20 shadow-sm">

                        <div x-show="!itemToView.isEditing">
                            <p class="text-base text-gray-800 dark:text-gray-100 leading-chill font-medium"
                                x-text="itemToView.rephrased || itemToView.response || itemToView.text">
                            </p>
                        </div>

                        <div x-show="itemToView.isEditing" x-cloak>
                            <textarea x-model="itemToView.rephrased"
                                class="w-full p-3 rounded-lg bg-white dark:bg-black/20 border border-sky-500/30 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 h-48 text-base"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-ghost text-sm"
                    @click="copyText(itemToView.rephrased || itemToView.response || itemToView.text)">Copy
                    Text</button>
                <button class="btn btn-ghost text-sm"
                    @click="itemToView.isEditing = !itemToView.isEditing"
                    x-text="itemToView.isEditing ? 'Cancel Edit' : 'Edit'">
                </button>
                <button class="btn btn-primary text-sm px-6" x-show="!itemToView.isEditing"
                    @click="viewModal = false">Close</button>
                <button class="btn btn-success text-sm px-6" x-show="itemToView.isEditing" @click="
                        inputText = itemToView.original;
                        rephrasedContent = itemToView.rephrased;
                        approveEntry().then(() => {
                            itemToView.isEditing = false;
                        });
                    ">Save Changes</button>
            </div>
        </div>
    </template>
</div>
