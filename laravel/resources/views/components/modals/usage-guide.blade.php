<!-- Usage Guide Modal -->
<div x-show="showGuide" class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-cloak>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showGuide = false"
        x-show="showGuide" x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="duration-200 ease-in"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <div class="glass-card relative w-full h-full max-w-5xl overflow-hidden flex flex-col shadow-2xl ring-1 ring-white/10"
        @click.stop x-show="showGuide" x-transition:enter="duration-300 ease-out"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <!-- Header -->
        <div class="modal-header">
            <h2 class="modal-title">
                Help &amp; Documentation
            </h2>
            <button @click="showGuide = false"
                class="p-2 rounded-full hover:bg-black/5 dark:hover:bg-white/5 transition-colors text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Content -->
        <div class="modal-body custom-scrollbar h-full">

            <!-- Tab Navigation -->
            <div
                class="flex gap-8 border-b border-gray-700/30 mb-8 sticky top-0 bg-gradient-to-b from-[#0f1115] via-[#0f1115] to-transparent backdrop-blur-sm z-10 pt-2 pb-1">
                <button @click="activeHelpTab = 'guide'"
                    class="pb-3 px-2 text-sm font-bold uppercase tracking-widest transition-all duration-300 border-b-2 relative group"
                    :class="activeHelpTab === 'guide' ? 'border-sky-400 text-sky-400' : 'border-transparent text-gray-500 hover:text-gray-300'">
                    <span class="relative z-10">User Guide</span>
                    <span x-show="activeHelpTab === 'guide'"
                        class="absolute inset-0 bg-sky-500/5 blur-xl rounded-lg -z-10"></span>
                </button>
                <button @click="activeHelpTab = 'stats'"
                    class="pb-3 px-2 text-sm font-bold uppercase tracking-widest transition-all duration-300 border-b-2 relative group"
                    :class="activeHelpTab === 'stats' ? 'border-purple-400 text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-300'">
                    <span class="relative z-10">Stats &amp; Models</span>
                    <span x-show="activeHelpTab === 'stats'"
                        class="absolute inset-0 bg-purple-500/5 blur-xl rounded-lg -z-10"></span>
                </button>
            </div>

            <!-- GUIDE TAB -->
            <div x-show="activeHelpTab === 'guide'" class="space-y-12 animate-fade">

                <!-- Section 1: Core Workflow -->
                <section>
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        <span
                            class="w-8 h-8 rounded-full bg-sky-500/10 text-sky-600 flex items-center justify-center text-sm">1</span>
                        The Core Workflow
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 rounded-xl bg-black/5 dark:bg-white/5 border border-gray-200/10">
                            <h4 class="font-semibold mb-2 text-sky-500">1. Compose</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Dump your raw thoughts, bullet
                                points, or rough drafts into the <strong>Compose Rephrasing</strong> area. Masha
                                will handle the grammar and tone.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-black/5 dark:bg-white/5 border border-gray-200/10">
                            <h4 class="font-semibold mb-2 text-indigo-500">2. Configure</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Use the Settings or dropdowns to
                                adjust the Target Role, Creativity, or enable Web Search.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-black/5 dark:bg-white/5 border border-gray-200/10">
                            <h4 class="font-semibold mb-2 text-emerald-500">3. Refine & Save</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Generate the response. Edit if
                                needed, then click <strong>Approve</strong> to save it to Masha's Knowledge
                                Base.</p>
                        </div>
                    </div>
                </section>

                <!-- Section 2: Task Modes -->
                <section>
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        <span
                            class="w-8 h-8 rounded-full bg-indigo-500/10 text-indigo-600 flex items-center justify-center text-sm">2</span>
                        Task Modes
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            class="p-4 rounded-xl bg-sky-50 dark:bg-sky-900/10 border border-sky-100 dark:border-sky-800">
                            <h4 class="font-bold text-sky-600 dark:text-sky-400 mb-2 flex items-center gap-2">
                                Fact Check Mode
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                <strong>Trigger:</strong> Enable "Online Research".<br>
                                <strong>Goal:</strong> Validate claims against live web data (Reddit, Apple,
                                etc.).
                            </p>
                        </div>
                        <div
                            class="p-4 rounded-xl bg-purple-50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-800">
                            <h4
                                class="font-bold text-purple-600 dark:text-purple-400 mb-2 flex items-center gap-2">
                                Template Mode
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                <strong>Trigger:</strong> Enable "Template Mode".<br>
                                <strong>Goal:</strong> Rigidly follow the structure of similar KB entries. Ideal
                                for forms.
                            </p>
                        </div>
                        <div
                            class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                            <h4 class="font-bold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                Standard Mode
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                <strong>Trigger:</strong> Both OFF.<br>
                                <strong>Goal:</strong> Professional rephrasing based on your Target Role.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Section 3: Dynamic Roles (NEW) -->
                <section>
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        <span
                            class="w-8 h-8 rounded-full bg-purple-500/10 text-purple-600 flex items-center justify-center text-sm">3</span>
                        Dynamic Roles
                    </h3>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                        <p>Masha can adopt different personas to suit your audience. Use the <strong>Target
                                Role</strong> dropdown to switch contexts instantly.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="p-4 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200/10">
                                <strong class="text-sky-500 block mb-1">Tech Support (Default)</strong>
                                <div class="text-xs">Produces structured analysis: <span
                                        class="font-mono text-gray-400">Observations</span>, <span
                                        class="font-mono text-gray-400">Actions Taken</span>, <span
                                        class="font-mono text-gray-400">Recommendations</span>. Ideal for
                                    internal tickets and detailed logs.</div>
                            </div>
                            <div class="p-4 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200/10">
                                <strong class="text-indigo-500 block mb-1">Customer Support</strong>
                                <div class="text-xs">Drafts professional, empathetic emails with clear Subject
                                    lines and salutations. Ideal for direct customer communication.</div>
                            </div>
                        </div>
                        <p class="mt-4 text-xs bg-sky-500/10 text-sky-400 p-2 rounded inline-block">
                            <strong>Tip:</strong> Create custom roles in the Configuration menu!
                        </p>
                    </div>
                </section>

                <!-- Section 4: KB Management -->
                <section>
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                        <span
                            class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-sm">4</span>
                        Knowledge Base (KB)
                    </h3>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                        <p>The Knowledge Base is Masha's long-term memory. It allows her to learn from your
                            corrections and get smarter over time.</p>
                        <ul>
                            <li><strong>Approve Button:</strong> Saves the "Original + Rephrased" pair to the
                                KB. Masha will use this as a reference for similar future requests.</li>
                            <li><strong>Review & Prune:</strong> Keep Masha sharp! Use the "Prune Low Usage"
                                tool to find and remove entries that haven't been helpful recently.</li>
                            <li><strong>Edit & Refine:</strong> Spotted a typo in an old entry? Open the Prune
                                list and click <strong>Edit</strong> to fix the text, update the category, or
                                change the role instantly.</li>
                            <li><strong>Optimize Index:</strong> If Masha feels a bit slow retrieving memories,
                                click "Optimize Index" to reorganize her vector database.</li>
                        </ul>
                    </div>

                    <!-- Maintenance Section -->
                    <div
                        class="mt-8 p-6 rounded-xl bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800">
                        <h4 class="font-bold text-orange-800 dark:text-orange-400 mb-4 flex items-center gap-2">
                            System Maintenance
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <button @click="optimizeIndex()" :disabled="status === 'Optimizing Index...'"
                                    class="w-full btn bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-700 hover:border-sky-500 text-gray-700 dark:text-gray-200 px-4 py-3 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="status !== 'Optimizing Index...'"
                                        xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-500"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <svg x-show="status === 'Optimizing Index...'"
                                        class="animate-spin w-4 h-4 text-sky-500"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span
                                        x-text="status === 'Optimizing Index...' ? 'Optimizing...' : 'Optimize Index'"></span>
                                </button>
                                <p class="text-[10px] text-gray-500 mt-2 text-center">Rebuilds vector cache.
                                    Run if search feels slow.</p>
                            </div>
                            <div>
                                <button @click="openPruneModal()"
                                    class="w-full btn bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-700 hover:border-red-500 text-gray-700 dark:text-gray-200 px-4 py-3 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Review & Prune
                                </button>
                                <p class="text-[10px] text-gray-500 mt-2 text-center">Review and delete old,
                                    unused entries.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- STATS TAB -->
            <div x-show="activeHelpTab === 'stats'" class="space-y-8 animate-fade" x-cloak>

                <!-- KB Stats -->
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        Knowledge Base Status
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="p-6 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-center flex flex-col items-center justify-center">
                            <div class="text-3xl font-bold text-emerald-500 mb-1"
                                x-text="kbStats.total_entries || 0">
                            </div>
                            <div
                                class="text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-bold opacity-80">
                                Total Entries</div>
                        </div>
                        <div
                            class="p-6 rounded-xl bg-sky-500/10 border border-sky-500/20 text-center flex flex-col items-center justify-center">
                            <div class="text-lg font-mono text-sky-500 mb-1"
                                x-text="kbStats.last_updated ? new Date(kbStats.last_updated).toLocaleDateString() : 'Never'">
                            </div>
                            <div
                                class="text-xs text-sky-600 dark:text-sky-400 uppercase tracking-wider font-bold opacity-80">
                                Last Updated</div>
                        </div>
                    </div>
                </div>

                <!-- Active Models -->
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Active Models</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <template x-for="model in availableModels" :key="model.id">
                            <div
                                class="glass-card p-4 border border-gray-200 dark:border-gray-700 flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <h5 class="font-bold text-sky-500 font-mono text-sm" x-text="model.name">
                                    </h5>
                                    <span class="text-[10px] bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded"
                                        x-text="model.id"></span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="getModelDescription(model.id)">
                                </p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Approval Logic -->
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        Global Leaderboard
                    </h4>
                    <div
                        class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-gray-700 mb-6">
                        <div class="mt-2">
                            <template x-if="modelStats.length === 0">
                                <div class="text-xs text-gray-500 italic">No approvals recorded locally yet.
                                </div>
                            </template>
                            <table class="w-full text-sm text-left" x-show="modelStats.length > 0">
                                <thead>
                                    <tr class="text-gray-400 border-b border-gray-200/10">
                                        <th class="pb-2">Model</th>
                                        <th class="pb-2 text-right">Approvals</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200/10">
                                    <template x-for="stat in modelStats" :key="stat.name">
                                        <tr>
                                            <td class="py-2 font-mono text-sky-500"
                                                x-text="formatModelName(stat.name)">
                                            </td>
                                            <td class="py-2 text-right font-bold text-emerald-500"
                                                x-text="stat.count">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-primary px-10 py-3 shadow-xl shadow-sky-500/20" @click="showGuide = false">
                Start Rephrasing
            </button>
        </div>
    </div>
</div>
