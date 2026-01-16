<!-- Configuration Modal -->
<div x-show="showConfigModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-cloak>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showConfigModal = false"
        x-show="showConfigModal" x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="duration-200 ease-in"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <div class="glass-card relative w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl ring-1 ring-white/10"
        x-show="showConfigModal" x-transition:enter="duration-300 ease-out"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <!-- Modal Header -->
        <div class="modal-header">
            <h3 class="modal-title">
                Configuration & Settings
            </h3>
            <button @click="showConfigModal = false"
                class="p-2 rounded-full hover:bg-black/5 dark:hover:bg-white/5 transition-colors text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Scrollable Content -->
        <div class="modal-body custom-scrollbar">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Tab Navigation -->
                <div class="col-span-1 lg:col-span-2 border-b border-gray-200/10 mb-6 flex gap-6">
                    <button @click="configTab = 'general'"
                        class="pb-3 text-sm font-bold uppercase tracking-widest transition-colors border-b-2"
                        :class="configTab === 'general' ? 'border-sky-500 text-sky-500' : 'border-transparent text-gray-400 hover:text-gray-200'">
                        General
                    </button>
                    <button @click="configTab = 'roles'"
                        class="pb-3 text-sm font-bold uppercase tracking-widest transition-colors border-b-2"
                        :class="configTab === 'roles' ? 'border-sky-500 text-sky-500' : 'border-transparent text-gray-400 hover:text-gray-200'">
                        Prompt Roles
                    </button>
                </div>

                <!-- General Tab Content -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 col-span-1 lg:col-span-2"
                    x-show="configTab === 'general'">
                    <!-- Left Column: User & Model -->
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-sky-500 mb-4">Core Model
                            </h4>
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="label-text">Signature</label>
                                    <input type="text" x-model="signature" placeholder="Masha"
                                        class="form-input w-full p-3 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200 dark:border-gray-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                                </div>

                                <div>
                                    <label class="label-text mb-2 block flex justify-between items-center">
                                        <span>Primary Model</span>
                                        <button @click="fetchOllamaModels()"
                                            class="text-xs text-sky-500 hover:underline flex items-center gap-1 transition-opacity"
                                            :class="isRefreshingModels ? 'opacity-50 cursor-wait' : ''"
                                            :disabled="isRefreshingModels">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3"
                                                :class="isRefreshingModels ? 'animate-spin' : ''" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span x-text="isRefreshingModels ? 'Refreshing...' : 'Refresh'"></span>
                                        </button>
                                    </label>
                                    <select x-model="modelA"
                                        class="form-select w-full p-3 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200 dark:border-gray-700 mb-4 focus:ring-1 focus:ring-sky-500">
                                        <option value="">Select Model...</option>
                                        <template x-for="m in availableModels" :key="m.id">
                                            <option :value="m.id" x-text="m.name" :selected="m.id === modelA">
                                            </option>
                                        </template>
                                    </select>

                                    <!-- Dynamic Import List -->
                                    <div
                                        class="border border-gray-200/50 dark:border-gray-700/50 rounded-lg overflow-hidden flex flex-col h-auto max-h-96">
                                        <div
                                            class="bg-gray-50 dark:bg-white/5 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 items-center flex justify-between">
                                            <span>Detected in Ollama</span>
                                            <span class="text-xs" x-show="ollamaModels.length > 0"
                                                x-text="ollamaModels.length"></span>
                                        </div>
                                        <div class="flex-1 overflow-y-auto p-1 space-y-1 relative"
                                            style="scrollbar-width: thin; scrollbar-color: rgba(156, 163, 175, 0.3) transparent;">
                                            <template x-if="ollamaModels.length === 0 && !isRefreshingModels">
                                                <div
                                                    class="h-full flex flex-col items-center justify-center p-4 text-center opacity-50 space-y-2">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="1.5"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span class="text-xs">No models detected</span>
                                                </div>
                                            </template>
                                            <template x-if="isRefreshingModels">
                                                <div class="h-full flex items-center justify-center">
                                                    <svg class="animate-spin h-5 w-5 text-sky-500"
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            </template>
                                            <template x-for="modelName in ollamaModels" :key="modelName">
                                                <div
                                                    class="flex justify-between items-center p-2 rounded hover:bg-black/5 dark:hover:bg-white/5 transition-all duration-200 group animate-fade">
                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                        <div class="w-1.5 h-1.5 rounded-full"
                                                            :class="isModelImported(modelName) ? 'bg-sky-400' : 'bg-gray-300 dark:bg-gray-600'">
                                                        </div>
                                                        <span x-text="modelName"
                                                            class="text-xs font-mono truncate max-w-[160px]"
                                                            :class="isModelImported(modelName) ? 'text-sky-500 font-medium' : 'text-gray-600 dark:text-gray-400'"></span>
                                                    </div>
                                                    <button @click="toggleModelImport(modelName)"
                                                        class="text-[10px] font-bold uppercase px-2 py-1 rounded transition-colors border shadow-sm opacity-80 group-hover:opacity-100"
                                                        :class="isModelImported(modelName)
                                                        ? 'border-red-500/30 text-red-500 hover:bg-red-500 hover:text-white'
                                                        : 'border-emerald-500/30 text-emerald-500 hover:bg-emerald-500 hover:text-white'"
                                                        x-text="isModelImported(modelName) ? 'Unlink' : 'Import'">
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2 pt-2">
                                    <button
                                        class="flex-1 info-pill justify-center text-center cursor-pointer hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors py-2"
                                        @click="applyPreset('creative')">Creative</button>
                                    <button
                                        class="flex-1 info-pill justify-center text-center cursor-pointer hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors py-2"
                                        @click="applyPreset('technical')">Technical</button>
                                    <button
                                        class="flex-1 info-pill justify-center text-center cursor-pointer hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors py-2"
                                        @click="applyPreset('tldr')">Concise</button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-indigo-500 mb-4">Context &
                                Category</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="label-text">Select Category</label>
                                    <select x-model="currentCategory"
                                        class="form-select w-full p-3 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200 dark:border-gray-700">
                                        <option value="">Full Knowledge Base</option>
                                        <template x-for="cat in categories" :key="cat">
                                            <option :value="cat" x-text="cat"></option>
                                        </template>
                                    </select>
                                </div>


                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Fine Tuning & KB -->
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-emerald-500 mb-4">Fine
                                Tuning
                            </h4>
                            <div
                                class="glass-card bg-transparent border border-gray-200/50 dark:border-gray-700/50 p-6 space-y-6">
                                <!-- Creativity -->
                                <div>
                                    <label class="label-text flex justify-between">
                                        <span>Creativity</span> <span x-text="temperature"></span>
                                    </label>
                                    <input type="range" x-model="temperature" min="0" max="1" step="0.1"
                                        class="w-full accent-sky-500 mt-2">
                                    <div class="text-xs text-gray-400 mt-1">Low (0.2) for strict facts, High (0.8)
                                        for
                                        creative writing.</div>
                                </div>
                                <!-- Past Examples -->
                                <div>
                                    <label class="label-text flex justify-between">
                                        <span>Use Past Examples</span> <span x-text="kbCount"></span>
                                    </label>
                                    <input type="range" x-model="kbCount" min="0" max="5" step="1"
                                        class="w-full accent-emerald-500 mt-2">
                                    <div class="text-xs text-gray-400 mt-1">Number of saved responses to use as
                                        style
                                        references.</div>
                                </div>
                                <!-- Response Length -->
                                <div>
                                    <label class="label-text flex justify-between">
                                        <span>Response Length</span> <span x-text="maxTokens"></span>
                                    </label>
                                    <input type="range" x-model="maxTokens" min="100" max="2048" step="100"
                                        class="w-full accent-indigo-500 mt-2">
                                    <div class="text-xs text-gray-400 mt-1">Max token limit (100 tokens ≈ 75 words).
                                    </div>
                                </div>
                                <!-- Topic Repetition -->
                                <div>
                                    <label class="label-text flex justify-between">
                                        <span>Topic Repetition</span> <span x-text="presencePenalty"></span>
                                    </label>
                                    <input type="range" x-model="presencePenalty" min="-2" max="2" step="0.1"
                                        class="w-full accent-purple-500 mt-2">
                                    <div class="text-xs text-gray-400 mt-1">Increase to stop the AI from staying on
                                        one
                                        topic.</div>
                                </div>
                                <!-- Word Repetition -->
                                <div>
                                    <label class="label-text flex justify-between">
                                        <span>Word Repetition</span> <span x-text="frequencyPenalty"></span>
                                    </label>
                                    <input type="range" x-model="frequencyPenalty" min="-2" max="2" step="0.1"
                                        class="w-full accent-pink-500 mt-2">
                                    <div class="text-xs text-gray-400 mt-1">Increase to stop the AI from repeating
                                        specific words.</div>
                                </div>
                                <!-- Style Exclusions -->
                                <div class="pt-2">
                                    <label class="label-text flex justify-between items-center mb-2">
                                        Style Exclusions
                                        <button @click="negativePrompt = ''" x-show="negativePrompt"
                                            class="text-xs text-red-400 hover:text-red-500">Clear</button>
                                    </label>
                                    <input type="text" x-model="negativePrompt"
                                        placeholder="e.g. no jargon, no apologies"
                                        class="w-full p-2 text-sm rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200 dark:border-gray-700 focus:ring-1 focus:ring-sky-500">
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div> <!-- End General Tab Grid -->

            <!-- Roles Tab Content -->
            <div class="col-span-1 lg:col-span-2" x-show="configTab === 'roles'" x-cloak>
                <div class="flex flex-col h-full space-y-6">

                    <!-- Roles List and Editor Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        <!-- Role List -->
                        <div class="lg:col-span-1 space-y-4">
                            <div class="flex justify-between items-center">
                                <h4 class="text-sm font-bold uppercase tracking-widest text-sky-500">Defined Roles
                                </h4>
                                <button @click="createNewRole()" class="text-xs btn btn-ghost px-2 py-1">+ New
                                    Role</button>
                            </div>

                            <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                <template x-for="role in promptRoles" :key="role.id">
                                    <div @click="selectRole(role)"
                                        class="p-3 rounded-xl border cursor-pointer transition-all hover:bg-white/5 relative group"
                                        :class="currRole?.id === role.id ? 'bg-white/10 border-sky-500/50' : 'bg-transparent border-gray-200/20 dark:border-gray-700/50'">

                                        <div class="flex justify-between items-start pt-1">
                                            <div>
                                                <h5 class="font-bold text-sm text-gray-200" x-text="role.name"></h5>
                                                <span x-show="role.is_default"
                                                    class="text-[10px] bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded uppercase font-bold tracking-wider inline-block mt-1">Default</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Role Editor -->
                        <div class="lg:col-span-2 glass-card bg-black/20 p-6 min-h-[400px]">
                            <template x-if="currRole">
                                <div class="space-y-4">
                                    <div
                                        class="flex justify-between items-center border-b border-gray-200/10 pb-4 mb-4">
                                        <h4 class="font-bold text-gray-200">Edit Role</h4>
                                        <div class="flex gap-2">
                                            <button x-show="!currRole.is_default" @click="deleteRole(currRole.id)"
                                                class="text-xs text-red-400 hover:text-red-300 px-3 py-1.5 rounded hover:bg-red-500/10 transition-colors">Delete</button>
                                            <button @click="saveRole()"
                                                class="btn btn-primary text-xs px-4 py-1.5">Save Changes</button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="label-text mb-1">Role Name</label>
                                            <input type="text" x-model="currRole.name"
                                                class="form-input w-full p-2 text-sm rounded-lg bg-black/20 border border-gray-700">
                                        </div>
                                        <div class="flex items-end pb-3">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" x-model="currRole.is_default"
                                                    class="rounded text-emerald-500 focus:ring-emerald-500 bg-black/20 border-gray-700">
                                                <span class="text-sm font-medium text-gray-300">Set as Default
                                                    Role</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="label-text mb-1">Identity (System Prompt Start)</label>
                                        <input type="text" x-model="currRole.identity"
                                            class="form-input w-full p-2 text-sm rounded-lg bg-black/20 border border-gray-700 placeholder-gray-500"
                                            placeholder="You are {signature}...">
                                        <p class="text-xs text-gray-500 mt-1">Use <code>{signature}</code>
                                            placeholder.</p>
                                    </div>

                                    <div>
                                        <label class="label-text mb-1">Protocol (Instructions)</label>
                                        <textarea x-model="currRole.protocol" rows="4"
                                            class="form-input w-full p-2 text-sm rounded-lg bg-black/20 border border-gray-700 font-mono text-xs text-gray-300"></textarea>
                                    </div>

                                    <div>
                                        <label class="label-text mb-1">Response Format</label>
                                        <textarea x-model="currRole.format" rows="6"
                                            class="form-input w-full p-2 text-sm rounded-lg bg-black/20 border border-gray-700 font-mono text-xs text-gray-300"></textarea>
                                    </div>

                                </div>
                            </template>
                            <template x-if="!currRole">
                                <div class="h-full flex items-center justify-center text-gray-500 flex-col gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 opacity-50" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <p>Select a role to edit or create a new one.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
        <!-- Modal Footer -->
        <div class="modal-footer">
            <button class="btn btn-primary px-8 py-3" @click="showConfigModal = false">
                Done
            </button>
        </div>
</div>
