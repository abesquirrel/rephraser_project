<!-- Edit KB Entry Modal -->
<div x-show="showEditKbModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4" x-cloak>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showEditKbModal = false"
        x-show="showEditKbModal" x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="duration-200 ease-in"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <div class="glass-card relative w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl ring-1 ring-white/10"
        x-show="showEditKbModal" x-transition:enter="duration-300 ease-out"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <!-- Header -->
        <div class="modal-header">
            <h3 class="modal-title text-sky-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Entry
            </h3>
            <button @click="showEditKbModal = false" class="text-gray-400 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body custom-scrollbar">

            <!-- Category & Role -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Category</label>
                    <input type="text" list="categoryOptions" x-model="editionKbEntry.category"
                        class="w-full bg-black/20 border border-gray-700 rounded-lg p-2 text-sm text-gray-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition-colors"
                        placeholder="Select or type custom...">
                    <datalist id="categoryOptions">
                        <template x-for="cat in categories" :key="cat">
                            <option :value="cat"></option>
                        </template>
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role</label>
                    <select x-model="editionKbEntry.role"
                        class="w-full bg-black/20 border border-gray-700 rounded-lg p-2 text-sm text-gray-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition-colors appearance-none">
                        <template x-for="role in promptRoles" :key="role.name">
                            <option :value="role.name" x-text="role.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Original -->
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Original Text</label>
                <textarea x-model="editionKbEntry.original_text" rows="3"
                    class="w-full bg-black/20 border border-gray-700 rounded-lg p-3 text-sm text-gray-300 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition-colors font-mono custom-scrollbar"></textarea>
            </div>

            <!-- Rephrased -->
            <div>
                <label class="block text-xs font-bold text-emerald-500/80 uppercase mb-1">Rephrased Text</label>
                <textarea x-model="editionKbEntry.rephrased_text" rows="4"
                    class="w-full bg-emerald-500/5 border border-emerald-500/30 rounded-lg p-3 text-sm text-emerald-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors font-mono custom-scrollbar"></textarea>
            </div>

            <!-- Keywords & Model -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Keywords</label>
                    <input type="text" x-model="editionKbEntry.keywords"
                        class="w-full bg-black/20 border border-gray-700 rounded-lg p-2 text-sm text-gray-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Model Used</label>
                    <select x-model="editionKbEntry.model_used"
                        class="w-full bg-black/20 border border-gray-700 rounded-lg p-2 text-sm text-gray-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 outline-none transition-colors appearance-none">
                        <template
                            x-if="editionKbEntry.model_used && availableModels && !availableModels.find(m => m.id === editionKbEntry.model_used)">
                            <option :value="editionKbEntry.model_used"
                                x-text="editionKbEntry.model_used + ' (Original)'"></option>
                        </template>
                        <option value="">Select Model</option>
                        <template x-for="model in availableModels" :key="model.id">
                            <option :value="model.id" x-text="model.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Template Toggle -->
            <div class="flex items-center gap-3 pt-2">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="editionKbEntry.is_template" class="sr-only peer">
                    <div
                        class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sky-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-300">Save as Template</span>
                </label>
            </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-ghost px-6 py-2" @click="showEditKbModal = false">Cancel</button>
            <button
                class="btn bg-sky-500 hover:bg-sky-600 text-white font-bold px-8 py-2 rounded-xl shadow-lg shadow-sky-500/20"
                @click="saveKbEdit()">
                Save Changes
            </button>
        </div>
    </div>
</div>
