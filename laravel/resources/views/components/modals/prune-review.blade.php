<!-- Prune Review Modal -->
<div x-show="showPruneModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4" x-cloak>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showPruneModal = false"
        x-show="showPruneModal" x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="duration-200 ease-in"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <div class="glass-card relative w-full max-w-[98%] max-h-[90vh] overflow-hidden flex flex-col shadow-2xl ring-1 ring-white/10"
        x-show="showPruneModal" x-transition:enter="duration-300 ease-out"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <!-- Header -->
        <div class="modal-header">
            <h3 class="modal-title text-red-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Review &amp; Prune
            </h3>
            <button @click="showPruneModal = false"
                class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-black/5 dark:hover:bg-white/5 transition-colors text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Filters / Stats -->
        <div class="p-6 border-b border-gray-200/10 bg-black/20 flex flex-wrap gap-4 items-center justify-between">
            <div class="flex gap-4 items-center">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Max Hits Threshold</label>
                    <input type="number" x-model.number="pruneThreshold" @change="fetchPruneCandidates()"
                        class="form-input w-20 p-2 text-sm rounded-lg bg-black/20 border border-gray-700 text-center">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Older Than (Days)</label>
                    <input type="number" x-model.number="pruneDays" @change="fetchPruneCandidates()"
                        class="form-input w-20 p-2 text-sm rounded-lg bg-black/20 border border-gray-700 text-center">
                </div>
                <button @click="fetchPruneCandidates()" :disabled="status === 'Scanning usage data...'"
                    class="text-xs text-sky-500 hover:text-sky-600 hover:underline transition-colors flex items-center gap-1.5 mt-4 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="status !== 'Scanning usage data...'" xmlns="http://www.w3.org/2000/svg"
                        class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <svg x-show="status === 'Scanning usage data...'" class="animate-spin w-3.5 h-3.5"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span x-text="status === 'Scanning usage data...' ? 'Scanning...' : 'Rescan'"></span>
                </button>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-red-400" x-text="pruneCandidates.length"></div>
                <div class="text-xs text-gray-500 uppercase font-bold">Candidates Found</div>
            </div>
        </div>

        <!-- List -->
        <div class="flex-1 overflow-y-auto p-0 custom-scrollbar relative">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#1a1b26]/80 text-gray-400 font-bold sticky top-0 backdrop-blur-md z-10">
                    <tr>
                        <th class="p-4 w-12 text-center">
                            <input type="checkbox" @click="toggleAllPrune()"
                                :checked="selectedPruneIds.length === pruneCandidates.length && pruneCandidates.length > 0"
                                class="rounded border-gray-700 bg-black/20 text-red-500 focus:ring-red-500">
                        </th>
                        <th class="p-4">Content Preview</th>
                        <th class="p-4 w-24 text-center">Hits</th>
                        <th class="p-4 w-32 text-center">Age (Days)</th>
                        <th class="p-4 w-40 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <template x-if="pruneCandidates.length === 0">
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500 italic">No candidates found
                                matching
                                criteria.</td>
                        </tr>
                    </template>
                    <template x-for="candidate in pruneCandidates" :key="candidate.id">
                        <tr class="hover:bg-white/5 transition-colors group"
                            :class="selectedPruneIds.includes(candidate.id) ? 'bg-red-500/10' : ''">
                            <td class="p-4 text-center">
                                <input type="checkbox" :checked="selectedPruneIds.includes(candidate.id)"
                                    @change="togglePruneSelection(candidate.id)"
                                    class="rounded border-gray-700 bg-black/20 text-red-500 focus:ring-red-500">
                            </td>
                            <td class="p-4">
                                <div class="line-clamp-2 text-gray-300 font-medium"
                                    x-text="candidate.rephrased_text || candidate.original_text"></div>
                                <div class="text-xs text-gray-500 mt-1"
                                    x-text="(candidate.keywords || 'No keywords')">
                                </div>
                            </td>
                            <td class="p-4 text-center font-mono text-gray-400" x-text="candidate.hits"></td>
                            <td class="p-4 text-center text-gray-400">
                                <span
                                    x-text="Math.floor((new Date() - new Date(candidate.created_at)) / (1000 * 60 * 60 * 24))"></span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click.stop="openEditKbModal(candidate)"
                                        class="px-3 py-1.5 rounded-lg bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-sky-500 hover:text-white transition-all text-xs font-bold uppercase tracking-wider">
                                        Edit
                                    </button>
                                    <button type="button" @click.stop="keepEntry(candidate.id)"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white transition-all text-xs font-bold uppercase tracking-wider">
                                        Keep
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <div class="text-xs text-gray-500">
                <span x-text="selectedPruneIds.length"></span> selected <span class="mx-1 opacity-50">|</span> <span
                    x-text="pruneCandidates.length"></span> candidates found
            </div>
            <div class="flex gap-3">
                <button class="btn btn-ghost px-6 py-2" @click="showPruneModal = false">Cancel</button>
                <button
                    class="btn bg-red-500 hover:bg-red-600 text-white font-bold px-8 py-2 rounded-xl shadow-lg shadow-red-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="confirmPrune()" :disabled="selectedPruneIds.length === 0">
                    Delete Selected
                </button>
            </div>
        </div>
    </div>
</div>
