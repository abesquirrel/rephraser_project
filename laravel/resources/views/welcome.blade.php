<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masha: The Rephraser</title>
    <meta name="description" content="A premium, AI-powered support analyst rephrasing tool.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Alpine.js Plugins -->
    <script defer src="https://unpkg.com/@alpinejs/persist@3.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x/dist/cdn.min.js"></script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js Core -->
    <script defer src="https://unpkg.com/alpinejs@3.x/dist/cdn.min.js"></script>
</head>

<body x-data="rephraserApp()" class="antialiased min-h-screen transition-colors duration-300">
    <div class="container mx-auto px-4 py-4 max-w-7xl">

        <!-- Header -->
        <header class="header animate-fade mb-4 flex flex-col md:flex-row justify-between items-center gap-4 px-2">
            <div class="text-left">
                <h1
                    class="text-2xl font-bold tracking-tight mb-1 bg-clip-text text-transparent bg-gradient-to-r from-sky-500 to-indigo-500 font-display">
                    Masha: The Rephraser
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    The lazy cat with the best ideas.<br>
                    <i>
                        In training — furballs may occur.
                    </i>
                </p>
            </div>

            <div class="flex items-center gap-4">


                <button @click="showGuide = true"
                    class="inline-flex items-center gap-2 text-sm text-sky-500 hover:text-sky-600 hover:underline focus:outline-none focus:ring-2 focus:ring-sky-500 rounded px-2 py-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" stroke="currentColor"
                        fill="none" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                    </svg>
                    How to Use
                </button>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-4 items-start">

            <!-- LEFT COLUMN: Input & Config -->
            <section class="flex flex-col gap-8" aria-label="Input Configuration">
                <!-- Main Input -->
                <div class="glass-card animate-fade p-0 overflow-visible delay-[100ms]">
                    <div class="p-5 pb-4">
                        <div class="section-title mb-2 text-sky-500 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                stroke="currentColor" fill="none" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                            <span>Compose</span>
                        </div>

                        <div class="mb-4 flex-1 flex flex-col">
                            <label for="rawInputArea" class="sr-only">Input text to rephrase</label>
                            <textarea id="rawInputArea" x-model="inputText" placeholder="Input notes..."
                                class="w-full min-h-[160px] p-4 text-base leading-relaxed rounded-xl bg-black/5 dark:bg-white/5 border border-transparent focus:border-sky-500 focus:ring-0 transition-colors resize-y placeholder-gray-400 font-mono"></textarea>
                        </div>

                        <!-- Input Configuration Controls (Moved from Modal) -->
                        <div class="mb-5 flex flex-wrap gap-4 items-end animate-fade delay-[100ms]">
                            <!-- Keywords Input -->
                            <div class="flex-1 min-w-[240px]">
                                <label class="label-text flex justify-between mb-1.5 text-xs text-gray-500 font-medium">
                                    <span>Keywords & Context</span>
                                    <button @click="predictKeywords()"
                                        class="text-sky-500 hover:text-sky-600 hover:underline text-xs flex items-center gap-1.5 transition-colors"
                                        :disabled="!inputText || isPredictingKeywords">
                                        <span x-show="isPredictingKeywords"
                                            class="animate-spin h-3 w-3 border-2 border-sky-500 border-t-transparent rounded-full"></span>
                                        <span
                                            x-text="isPredictingKeywords ? 'Analyzing...' : 'Auto-Predict Tags'"></span>
                                    </button>
                                </label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <input type="text" x-model="searchKeywords"
                                        placeholder="e.g. professional, email response..."
                                        class="form-input w-full pl-9 p-2.5 text-sm rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-gray-700/50 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all placeholder-gray-400">
                                </div>
                            </div>

                            <!-- Role Selector -->
                            <div class="flex-1 min-w-[200px]">
                                <label class="label-text flex justify-between mb-1.5 text-xs text-gray-500 font-medium">
                                    <span>Target Role</span>
                                    <button @click="showConfigModal = true; configTab = 'roles'"
                                        class="text-xs text-sky-500 hover:underline">Manage</button>
                                </label>
                                <select x-model="selectedRoleName"
                                    class="form-select w-full p-2.5 text-sm rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-gray-700/50 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all font-medium text-gray-700 dark:text-gray-200">
                                    <template x-for="role in promptRoles" :key="role.id">
                                        <option :value="role.name"
                                            x-text="role.name + (role.is_default ? ' (Default)' : '')"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Toggles Group -->
                            <div class="flex gap-3 pb-0.5">
                                <!-- Template Mode -->
                                <label
                                    class="flex items-center gap-2.5 cursor-pointer px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-white/5 transition-all select-none group"
                                    :class="templateMode ? 'bg-sky-50 dark:bg-sky-900/20 border-sky-200 dark:border-sky-500/30' : ''">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" x-model="templateMode" class="peer sr-only">
                                        <div
                                            class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-sky-500 shadow-inner">
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold uppercase tracking-wider"
                                        :class="templateMode ? 'text-sky-600 dark:text-sky-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300'">Template</span>
                                </label>

                                <!-- Online Research -->
                                <label
                                    class="flex items-center gap-2.5 cursor-pointer px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-white/5 transition-all select-none group"
                                    :class="enableWebSearch ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-500/30' : ''">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" x-model="enableWebSearch" class="peer sr-only">
                                        <div
                                            class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-500 shadow-inner">
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold uppercase tracking-wider"
                                        :class="enableWebSearch ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300'">Research</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex gap-4 items-center">
                            <button class="btn btn-primary w-full py-3 flex items-center justify-center gap-2"
                                @click="generateRephrase()" :disabled="isGenerating || !inputText.trim()">
                                <span x-show="!isGenerating" class="flex items-center gap-2">
                                    Generate Response
                                </span>
                                <span x-show="isGenerating" class="flex items-center gap-2 animate-pulse"
                                    style="display: none;">
                                    Generating...
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Configuration Toggle -->
                    <div class="border-t border-gray-200/50 dark:border-gray-700/50">
                        <button @click="showConfigModal = true"
                            class="w-full flex items-center justify-center gap-2 py-3 text-sm text-gray-500 hover:bg-black/5 dark:hover:bg-white/5 transition-colors focus:outline-none rounded-b-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                                stroke="currentColor" fill="none">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            <span>Configure Model & Settings</span>
                        </button>
                    </div>
                </div>



                <!-- Floating Loader (Non-blocking) -->
                <div x-show="isGenerating" x-cloak
                    class="fixed bottom-8 right-8 z-[60] flex items-center gap-4 bg-black/80 backdrop-blur-md p-4 rounded-2xl shadow-2xl border border-sky-500/30 animate-fade-in"
                    x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="duration-200 ease-in"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-4">

                    <div class="relative w-10 h-10 flex items-center justify-center">
                        <div class="absolute inset-0 bg-sky-500/20 rounded-full animate-ping"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-sky-400 animate-pulse relative z-10"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="pr-2">
                        <div class="text-sm font-bold text-white" x-text="friendlyStatus"></div>
                        <div class="text-[10px] text-gray-400 uppercase tracking-widest"
                            x-text="status !== friendlyStatus ? status : ''"></div>
                    </div>
                </div>
            </section>

            <!-- RIGHT COLUMN: Output & History -->
            <section class="flex flex-col gap-4" aria-label="Output">
                <template x-if="history.length > 0">
                    <div class="animate-fade delay-[200ms]">
                        <h2 class="section-title mb-3 flex items-center justify-between">
                            <span>Latest Response</span>
                            <span class="info-pill font-normal"
                                x-text="new Date(history[0].timestamp).toLocaleTimeString()"></span>
                        </h2>

                        <!-- Display only the latest item -->
                        <template x-for="(item, idx) in [history[0]]" :key="item.timestamp">
                            <article
                                class="glass-card history-card p-0 overflow-hidden border-0 shadow-none bg-transparent"
                                :class="{ 'approved': item.approved }">

                                <div class="flex flex-col gap-8">
                                    <!-- Original Input Display -->
                                    <div class="glass-card p-6 mb-4 relative">
                                        <div class="flex justify-between mb-4">
                                            <span class="label-text m-0">Original</span>
                                            <span
                                                class="bg-emerald-500/10 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider border border-emerald-500/20 flex items-center justify-center min-w-[45px]"
                                                x-show="item.approved">Saved</span>
                                        </div>
                                        <div class="bubble bubble-original text-sm p-3 border-l-4 border-indigo-500"
                                            x-text="item.original"></div>
                                    </div>

                                    <!-- Refined Output -->
                                    <!-- Refined Output Area -->
                                    <!-- Refined Output Area -->
                                    <div id="results-area"
                                        class="glass-card p-6 border-sky-500 shadow-xl shadow-sky-500/10">

                                        <!-- Standard Single Output -->
                                        <div>
                                            <div class="mb-4">
                                                <span class="label-text">Refined Output</span>
                                            </div>
                                            <div x-show="!item.isEditing">
                                                <div class="bubble bubble-rephrased text-base p-4 bg-white/50 dark:bg-black/20"
                                                    x-text="item.rephrased"></div>
                                            </div>
                                            <div x-show="item.isEditing" x-cloak>
                                                <textarea :id="'edit-' + idx" x-model="item.rephrased"
                                                    class="edit-textarea w-full p-4 rounded-xl bg-white dark:bg-gray-800 border border-green-500/20 focus:border-sky-500"
                                                    rows="10"></textarea>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-gray-200/10">

                                            <div class="flex justify-end gap-3 mt-6">
                                                <button class="btn btn-ghost px-4 py-2 text-sm"
                                                    @click="copyText(item.rephrased)">Copy</button>
                                                <button class="btn btn-ghost px-4 py-2 text-sm" @click="toggleEdit(0)"
                                                    x-text="item.isEditing ? 'Save' : 'Edit'"></button>
                                                <button
                                                    class="btn btn-ghost px-4 py-2 text-sm text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                    @click="regenerateResponse(item)" :disabled="isGenerating">
                                                    Regenerate
                                                </button>
                                                <button class="btn px-5 py-2 text-sm font-semibold"
                                                    :class="item.approved ? 'btn-success-ghost' : 'btn-ghost'"
                                                    @click="approveHistoryEntry(item, 0)">
                                                    Approve
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                            </article>
                        </template>
                    </div>
                </template>

                <!-- Archive Section -->
                <template x-if="history.length > 1">
                    <div x-data="{ openArchive: false }">
                        <button
                            class="w-full glass-card p-4 cursor-pointer border-dashed opacity-80 hover:opacity-100 transition-opacity flex justify-between items-center"
                            @click="openArchive = !openArchive">
                            <h3 class="section-title m-0 text-base flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-sky-500" viewBox="0 0 24 24"
                                    stroke="currentColor" fill="none">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
                                Response Archive
                            </h3>
                            <div class="flex items-center gap-3">
                                <span x-text="totalFilteredCount + ' Items'"
                                    class="info-pill bg-sky-100 dark:bg-sky-900/30"></span>
                            </div>
                        </button>

                        <div x-show="openArchive" x-collapse class="mt-6">
                            <!-- Archive Controls -->
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                                <div class="flex p-1 bg-black/5 dark:bg-white/5 rounded-lg">
                                    <button @click="archiveFilter = 'all'; currentPage = 1"
                                        :class="{'bg-white dark:bg-white/10 shadow-sm text-sky-500': archiveFilter === 'all', 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200': archiveFilter !== 'all'}"
                                        class="px-4 py-1.5 text-xs font-medium rounded-md transition-all">All</button>
                                    <button @click="archiveFilter = 'saved'; currentPage = 1"
                                        :class="{'bg-white dark:bg-white/10 shadow-sm text-emerald-500': archiveFilter === 'saved', 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200': archiveFilter !== 'saved'}"
                                        class="px-4 py-1.5 text-xs font-medium rounded-md transition-all">Saved</button>
                                    <button @click="archiveFilter = 'unsaved'; currentPage = 1"
                                        :class="{'bg-white dark:bg-white/10 shadow-sm text-amber-500': archiveFilter === 'unsaved', 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200': archiveFilter !== 'unsaved'}"
                                        class="px-4 py-1.5 text-xs font-medium rounded-md transition-all">Unsaved</button>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button @click="refreshArchive()"
                                        class="text-xs text-gray-500 hover:text-sky-500 font-medium flex items-center gap-1 transition-colors px-3 py-1.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                            :class="isRefreshingArchive ? 'animate-spin' : ''" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span x-text="isRefreshingArchive ? 'Refreshing...' : 'Refresh'"></span>
                                    </button>
                                    <button @click="clearUnsaved()"
                                        class="text-xs text-red-400 hover:text-red-500 font-medium flex items-center gap-1 transition-colors px-3 py-1.5 rounded-lg hover:bg-red-500/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Clear Unsaved
                                    </button>
                                </div>
                            </div>

                            <!-- Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <template x-for="(item, idx) in paginatedHistory" :key="item.timestamp">
                                    <div class="glass-card p-5 hover:bg-white/40 dark:hover:bg-black/40 transition-colors group relative cursor-pointer border border-gray-200/50 dark:border-gray-700/50"
                                        @click="itemToView = item; viewModal = true">

                                        <div class="flex justify-between items-start mb-3">
                                            <span class="text-xs font-mono text-gray-400"
                                                x-text="new Date(item.timestamp).toLocaleString()"></span>
                                            <span x-show="item.approved"
                                                class="text-[10px] bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full font-bold">SAVED</span>
                                        </div>

                                        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3 mb-3 leading-relaxed"
                                            x-text="item.rephrased || item.response || item.text"></p>

                                        <div
                                            class="flex items-center gap-2 mt-auto pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                                            <span class="text-[10px] uppercase tracking-wider font-bold text-sky-500"
                                                x-text="item.modelA_name || item.modelA || 'AI Model'"></span>
                                            <span
                                                class="text-xs text-gray-400 ml-auto group-hover:text-sky-500 transition-colors">View
                                                Details &rarr;</span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Pagination -->
                            <div class="flex justify-between items-center mt-6" x-show="totalPages > 1">
                                <button class="btn btn-ghost text-xs" :disabled="currentPage === 1"
                                    @click="currentPage--">
                                    &larr; Prev
                                </button>
                                <span class="text-xs text-gray-400">Page <span x-text="currentPage"></span> of <span
                                        x-text="totalPages"></span></span>
                                <button class="btn btn-ghost text-xs" :disabled="currentPage === totalPages"
                                    @click="currentPage++">
                                    Next &rarr;
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

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

                            <div class="flex justify-between items-center p-6 border-b border-gray-200/10">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-xl font-bold font-display text-sky-500">Response Details</h3>
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

                            <div class="overflow-y-auto p-8 space-y-8 custom-scrollbar">
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

                            <div
                                class="p-6 border-t border-gray-200/10 bg-gray-50/50 dark:bg-black/20 flex justify-end gap-3">
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
            </section>
        </div>

        <!-- KB Management (Advanced Section) -->
        <div x-data="{ expanded: false }" class="glass-card animate-fade p-0 overflow-hidden delay-[300ms] mt-6">
            <button @click="expanded = !expanded"
                class="w-full flex justify-between items-center p-4 hover:bg-black/5 dark:hover:bg-white/5 transition-colors focus:outline-none">
                <div class="section-title m-0 flex items-center gap-3 text-sky-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" stroke="currentColor"
                        fill="none">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    Knowledge Base Settings
                </div>
                <span x-text="expanded ? 'Hide' : 'Expand'"
                    class="btn-ghost text-xs px-3 py-1.5 rounded-full border border-current"></span>
            </button>

            <div x-show="expanded" x-cloak x-collapse
                class="border-t border-gray-200/50 dark:border-gray-700/50 bg-black/5 dark:bg-white/5">
                <div class="p-8">
                    <!-- Manual Data Entry -->
                    <div class="mb-10">
                        <h3 class="text-sm font-semibold mb-4 text-gray-900 dark:text-gray-100">Add Training Data
                            Manually</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <textarea x-model="manualOrig" placeholder="Original input (e.g. rough notes)..." rows="4"
                                class="form-input w-full p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"></textarea>
                            <textarea x-model="manualReph" placeholder="Ideal rephrased response..." rows="4"
                                class="form-input w-full p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"></textarea>
                        </div>

                        <div class="flex flex-wrap gap-4 items-end">
                            <div class="flex-1 min-w-[200px]">
                                <label class="label-text flex justify-between">
                                    Keywords
                                    <button @click="predictKeywords()"
                                        class="text-sky-500 hover:underline text-xs flex items-center gap-1"
                                        :disabled="!manualOrig || isPredictingKeywords">
                                        <template x-if="isPredictingKeywords">
                                            <svg class="animate-spin h-3 w-3 text-sky-500"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                        </template>
                                        <span x-text="isPredictingKeywords ? 'Predicting...' : 'Auto-Predict'"></span>
                                    </button>
                                </label>
                                <input type="text" x-model="manualKeywords" placeholder="firmware, latency..."
                                    class="form-input w-full p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="label-text">Category</label>
                                <select x-model="manualCategory"
                                    class="form-select w-full p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                    <option value="">Select...</option>
                                    <template x-for="cat in categories" :key="cat">
                                        <option :value="cat" x-text="cat"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex items-center pb-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="manualIsTemplate"
                                        class="rounded text-sky-500 focus:ring-sky-500">
                                    <span class="text-sm font-medium">Template?</span>
                                </label>
                            </div>
                            <button class="btn btn-primary px-6 py-2.5 flex items-center justify-center min-w-[120px]"
                                @click="addManual()" :disabled="!manualOrig.trim() || !manualReph.trim() || adding">
                                <span x-show="!adding">Add Entry</span>
                                <span x-show="adding">Saving...</span>
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-gray-200/50 dark:border-gray-700/50 pt-8">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Knowledge Base Insights
                            </h3>
                            <button
                                class="btn btn-ghost p-2 rounded-lg hover:bg-sky-500/10 hover:text-sky-500 transition-all group"
                                @click="fetchKbStats()" :disabled="status.includes('stats')" title="Refresh Stats">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                    :class="status.includes('stats') ? 'animate-spin text-sky-500' : 'text-gray-500 group-hover:text-sky-500'"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                        <div class="bg-black/5 dark:bg-white/5 rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="text-center p-2 rounded bg-white/5 border border-white/10">
                                    <div class="text-2xl font-bold text-sky-500" x-text="kbStats.total_entries">0</div>
                                    <div class="text-[10px] uppercase text-gray-500 font-bold">Total Entries</div>
                                </div>
                                <div class="text-center p-2 rounded bg-white/5 border border-white/10">
                                    <div class="text-xs font-mono text-gray-400 mt-2"
                                        x-text="kbStats.last_updated ? new Date(kbStats.last_updated).toLocaleDateString() : 'Never'">
                                    </div>
                                    <div class="text-[10px] uppercase text-gray-500 font-bold mt-1">Last Updated</div>
                                </div>
                            </div>

                            <template x-if="kbStats.category_breakdown && kbStats.category_breakdown.length > 0">
                                <div>
                                    <h4 class="text-[10px] uppercase font-bold text-gray-500 mb-2">Top Categories</h4>
                                    <div class="space-y-1">
                                        <template x-for="cat in kbStats.category_breakdown" :key="cat.category">
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="text-gray-400" x-text="cat.category"></span>
                                                <span class="font-mono text-sky-400" x-text="cat.count"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-gray-200/50 dark:border-gray-700/50 pt-6">
                        <div class="flex flex-col md:flex-row gap-4 items-end">
                            <div class="flex-1 w-full">
                                <label class="label-text" for="bulkImport">Bulk Import (CSV)</label>
                                <p class="text-xs opacity-60 mb-2">Format: original, rephrased, keywords, is_template,
                                    category</p>
                                <input type="file" @change="kbFile = $event.target.files[0]" id="bulkImport"
                                    class="w-full p-2 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg text-sm">
                            </div>
                            <button class="btn btn-ghost whitespace-nowrap" @click="importKB()"
                                :disabled="!kbFile || importing">
                                Import Corpus
                            </button>
                        </div>



                    </div>
                </div>
            </div>

            <!-- Login Modal -->
            <div x-show="showLogin" x-cloak @click.self="showLogin = false"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 backdrop-blur-sm">
                <div @click.stop
                    class="glass-card p-8 rounded-3xl w-full max-w-md mx-4 border border-white/10 shadow-2xl"
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">

                    <h2 class="text-2xl font-bold text-white mb-6">Welcome Back</h2>

                    <form @submit.prevent="login" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Username or Email</label>
                            <input type="text" x-model="loginForm.login" required
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="Enter username or email">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                            <input type="password" x-model="loginForm.password" required
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="Enter password">
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="submit"
                                class="flex-1 bg-sky-500 hover:bg-sky-600 text-white font-semibold py-3 px-6 rounded-xl transition-all shadow-lg hover:shadow-sky-500/50">
                                Login
                            </button>
                            <button type="button" @click="showLogin = false"
                                class="px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>

                    <p class="mt-6 text-center text-sm text-gray-400">
                        Don't have an account?
                        <button @click="showLogin = false; showRegister = true"
                            class="text-sky-400 hover:text-sky-300 font-semibold">
                            Register
                        </button>
                    </p>
                </div>
            </div>

            <!-- Register Modal -->
            <div x-show="showRegister" x-cloak @click.self="showRegister = false"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 backdrop-blur-sm">
                <div @click.stop
                    class="glass-card p-8 rounded-3xl w-full max-w-md mx-4 border border-white/10 shadow-2xl"
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">

                    <h2 class="text-2xl font-bold text-white mb-6">Create Account</h2>

                    <form @submit.prevent="register" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                            <input type="text" x-model="registerForm.name" required
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="John Doe">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                            <input type="text" x-model="registerForm.username" required
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="johndoe">
                            <p class="text-xs text-gray-500 mt-1">Will be used as your default signature</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                            <input type="email" x-model="registerForm.email" required
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="john@example.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                            <input type="password" x-model="registerForm.password" required minlength="8"
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                placeholder="At least 8 characters">
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="submit"
                                class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-3 px-6 rounded-xl transition-all shadow-lg hover:shadow-emerald-500/50">
                                Register
                            </button>
                            <button type="button" @click="showRegister = false"
                                class="px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>

                    <p class="mt-6 text-center text-sm text-gray-400">
                        Already have an account?
                        <button @click="showRegister = false; showLogin = true"
                            class="text-sky-400 hover:text-sky-300 font-semibold">
                            Login
                        </button>
                    </p>
                </div>
            </div>

            <!-- Toast Notifications -->
            <div x-show="toast.active" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                class="toast fixed top-6 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-5 py-2.5 text-white rounded-2xl shadow-2xl font-semibold backdrop-blur-md ring-1 ring-white/20 max-w-[90vw] md:max-w-lg w-auto min-w-0"
                :class="{
                    'bg-emerald-500/90': toast.type === 'success',
                    'bg-rose-500/90': toast.type === 'error', 
                    'bg-sky-500/90': toast.type === 'info'
                }">
                <!-- Icon based on type -->
                <template x-if="toast.type === 'success'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </template>

                <span x-text="toast.msg" class="tracking-wide text-sm"></span>
            </div>



        </div>

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
                <div class="flex justify-between items-center p-6 border-b border-gray-700/30 bg-[#1a1b26]/50 shrink-0">
                    <div class="flex items-center gap-4">
                        <h2
                            class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-sky-400 to-indigo-400">
                            Help &amp; Documentation
                        </h2>
                    </div>
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
                <div class="overflow-y-auto p-6 custom-scrollbar h-full">

                    <!-- Tab Navigation -->
                    <div class="flex gap-8 border-b border-gray-700/30 mb-8 pt-2 pb-0 select-none">
                        <button @click="activeHelpTab = 'guide'"
                            class="pb-4 px-1 text-xs font-bold uppercase tracking-widest transition-all duration-300 border-b-2 relative group outline-none"
                            :class="activeHelpTab === 'guide' ? 'border-sky-400 text-sky-400' : 'border-transparent text-gray-500 hover:text-gray-300'">
                            User Guide
                        </button>
                        <button @click="activeHelpTab = 'stats'"
                            class="pb-4 px-1 text-xs font-bold uppercase tracking-widest transition-all duration-300 border-b-2 relative group outline-none"
                            :class="activeHelpTab === 'stats' ? 'border-purple-400 text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-300'">
                            Stats &amp; Models
                        </button>
                    </div>

                    <!-- GUIDE TAB -->
                    <div x-show="activeHelpTab === 'guide'" class="space-y-12 animate-fade">

                        <!-- Section 1: The Masha Ecosystem -->
                        <section>
                            <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                                <span
                                    class="w-8 h-8 rounded-full bg-sky-500/10 text-sky-600 flex items-center justify-center text-sm font-display">01</span>
                                The Ecosystem
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div
                                    class="p-5 rounded-2xl bg-black/5 dark:bg-white/5 border border-gray-200/10 hover:border-sky-500/30 transition-colors">
                                    <h4 class="font-bold mb-2 text-sky-500 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Drafting
                                    </h4>
                                    <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-400">Input raw
                                        diagnostic notes or fragmented thoughts. Masha utilizes <strong>real-time token
                                            streaming</strong> to begin drafting your response word-by-word instantly.
                                    </p>
                                </div>
                                <div
                                    class="p-5 rounded-2xl bg-black/5 dark:bg-white/5 border border-gray-200/10 hover:border-indigo-500/30 transition-colors">
                                    <h4 class="font-bold mb-2 text-indigo-500 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.364-6.364l-.707-.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        Intelligence
                                    </h4>
                                    <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-400">Switch roles
                                        between <strong>Tech Support</strong> or <strong>Customer Care</strong>. Enable
                                        <strong>Research Mode</strong> to cross-reference data against live web sources.
                                    </p>
                                </div>
                                <div
                                    class="p-5 rounded-2xl bg-black/5 dark:bg-white/5 border border-gray-200/10 hover:border-emerald-500/30 transition-colors">
                                    <h4 class="font-bold mb-2 text-emerald-500 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Memory
                                    </h4>
                                    <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-400">Clicking
                                        <strong>Approve</strong> commits the pair to the Knowledge Base. Masha learns
                                        your stylistic preferences and technical corrections for future requests.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Section 2: Pro Optimization -->
                        <section>
                            <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                                <span
                                    class="w-8 h-8 rounded-full bg-indigo-500/10 text-indigo-600 flex items-center justify-center text-sm font-display">02</span>
                                Performance Latency & Optimization
                            </h3>
                            <div class="space-y-4">
                                <div class="p-5 rounded-2xl bg-indigo-500/5 border border-indigo-500/20">
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-500 shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 dark:text-gray-200 text-sm mb-1">
                                                Technical Fast-Path (Regex)</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Masha proactively scans
                                                for <strong>IMEIs, MSISDNs, and Error Codes</strong>. If detected, she
                                                bypasses the keyword extraction sub-call, cutting startup latency by ~1s
                                                and ensuring technical accuracy in KB retrieval.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-5 rounded-2xl bg-sky-500/5 border border-sky-500/20">
                                    <div class="flex items-start gap-4">
                                        <div class="p-2 bg-sky-500/10 rounded-lg text-sky-500 shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 dark:text-gray-200 text-sm mb-1">Resource
                                                Profiles (Auto-Limiting)</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">To maintain system
                                                stability on local hardware, Masha classifies models into tiers (e.g.,
                                                Llama-3 vs Phi-3). Parameters like <strong>Response Length</strong> and
                                                <strong>KB Breadth</strong> are automatically capped/adjusted when you
                                                switch models.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Section 3: Specialized Task Modes -->
                        <section>
                            <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                                <span
                                    class="w-8 h-8 rounded-full bg-purple-500/10 text-purple-600 flex items-center justify-center text-sm font-display">03</span>
                                Inference Tasks
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div
                                    class="p-4 rounded-2xl bg-sky-50 dark:bg-sky-900/10 border border-sky-100 dark:border-sky-800">
                                    <h4 class="font-bold text-sky-600 dark:text-sky-400 mb-2 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        Live Research
                                    </h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                        <strong>Utility:</strong> Aggregates technical data from Apple, Samsung, and
                                        carrier
                                        forums. Designed for real-time validation of emerging connectivity issues.
                                    </p>
                                </div>
                                <div
                                    class="p-4 rounded-2xl bg-purple-50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-800">
                                    <h4
                                        class="font-bold text-purple-600 dark:text-purple-400 mb-2 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Template Injection
                                    </h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                        <strong>Utility:</strong> Enforces strict inheritance of KB structures. Mirrors
                                        the
                                        standardized format of your chosen "Golden Samples" for ticket consistency.
                                    </p>
                                </div>
                                <div
                                    class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-bold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Heuristic Mode
                                    </h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                        <strong>Utility:</strong> Balanced RAG-assisted rephrasing focused on the active
                                        Role profile. Best suited for fluid, high-volume log documentation.
                                    </p>
                                </div>
                            </div>
                        </section>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                            <p>Masha optimizes her response profile based on the selected <strong>Professional
                                    Persona</strong>. This determines the structural output and tone.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="p-4 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200/10">
                                    <strong class="text-sky-500 block mb-1">Technical Support</strong>
                                    <div class="text-xs leading-relaxed">Produces highly structured diagnostic logs:
                                        <span class="font-mono text-gray-400">Observations</span>, <span
                                            class="font-mono text-gray-400">Actions Taken</span>, <span
                                            class="font-mono text-gray-400">Recommendations</span>. Optimized for
                                        internal ticketing systems.
                                    </div>
                                </div>
                                <div class="p-4 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200/10">
                                    <strong class="text-indigo-500 block mb-1">Customer Liaison</strong>
                                    <div class="text-xs leading-relaxed">Drafts direct, empathetic correspondence.
                                        Includes professional
                                        Subject lines and appropriate salutations for external communication.</div>
                                </div>
                            </div>
                            <p class="mt-4 text-xs bg-sky-500/10 text-sky-400 p-2 rounded inline-block">
                                <strong>System Tip:</strong> You can define and extend these roles via the Configuration
                                menu.
                            </p>
                        </div>
                        </section>

                        <!-- Section 4: Lifecycle Management -->
                        <section>
                            <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                                <span
                                    class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-sm font-display">04</span>
                                Knowledge Lifecycle
                            </h3>
                            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                                <p>Masha's long-term utility depends on the quality of her Knowledge Base (KB). This is
                                    not just a search log, but a <strong>curated training set</strong> for the local
                                    model.</p>
                                <ul class="space-y-4 list-none pl-0">
                                    <li class="flex gap-4">
                                        <div class="p-1 bg-emerald-500/10 rounded text-emerald-500 h-fit mt-1"><svg
                                                xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg></div>
                                        <div><strong>Curation:</strong> By editing and approving a response, you are
                                            providing a "Golden Sample." Masha prioritizes these samples during future
                                            semantic searches.</div>
                                    </li>
                                    <li class="flex gap-4">
                                        <div class="p-1 bg-sky-500/10 rounded text-sky-500 h-fit mt-1"><svg
                                                xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg></div>
                                        <div><strong>Maintenance (Pruning):</strong> Use the <strong>Review &
                                                Prune</strong> workflow to identify entries that are no longer accurate
                                            or have low retrieval utility.</div>
                                    </li>
                                    <li class="flex gap-4">
                                        <div class="p-1 bg-indigo-500/10 rounded text-indigo-500 h-fit mt-1"><svg
                                                xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg></div>
                                        <div><strong>Indexing:</strong> Periodically "Optimize Index" to rebuild the
                                            FAISS vector cache. This ensures semantic search remains performant as your
                                            dataset grows.</div>
                                    </li>
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
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Active Models & Resource
                                Profiles</h4>
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

                <div class="p-6 pt-4 border-t border-gray-200/10 bg-gray-50/50 dark:bg-black/20 text-center">
                    <button class="btn btn-primary px-10 py-3 shadow-xl shadow-sky-500/20" @click="showGuide = false">
                        Start Rephrasing
                    </button>
                </div>
            </div>
        </div>
    </div>

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
            <div class="flex justify-between items-center p-6 border-b border-gray-700/30 bg-[#1a1b26]/50">
                <h3 class="text-xl font-bold text-red-400 flex items-center gap-2">
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
                            <th class="p-4 cursor-pointer hover:bg-white/5 transition-colors group"
                                @click="sortPrune('hits')">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Hits</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-transform"
                                        :class="sortPruneBy === 'hits' ? (sortPruneOrder === 'asc' ? 'rotate-180 text-red-500' : 'text-red-500') : 'text-gray-600 opacity-0 group-hover:opacity-100'"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
                            <th class="p-4 cursor-pointer hover:bg-white/5 transition-colors group"
                                @click="sortPrune('age')">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Age (Days)</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-transform"
                                        :class="sortPruneBy === 'age' ? (sortPruneOrder === 'asc' ? 'rotate-180 text-red-500' : 'text-red-500') : 'text-gray-600 opacity-0 group-hover:opacity-100'"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </th>
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
            <div class="flex items-center gap-4 text-xs">
                <div
                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/5 border border-emerald-500/10 text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Tip: Use <b>Optimize Index</b> after large prunes to rebuild the search cache.</span>
                    <button @click="optimizeIndex()"
                        class="ml-2 text-emerald-400 hover:text-emerald-300 underline font-bold">Optimize Now</button>
                </div>
            </div>

            <div class="p-5 border-t border-gray-700/30 bg-[#1a1b26]/50 flex justify-between items-center z-10">
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
            <div class="flex justify-between items-center p-6 border-b border-gray-700/30 bg-[#1a1b26]/50">
                <h3 class="text-xl font-bold text-sky-400 flex items-center gap-2">
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
            <div class="p-6 space-y-4 overflow-y-auto custom-scrollbar">

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
            <div class="p-5 border-t border-gray-700/30 bg-[#1a1b26]/50 flex justify-end gap-3">
                <button class="btn btn-ghost px-6 py-2" @click="showEditKbModal = false">Cancel</button>
                <button
                    class="btn bg-sky-500 hover:bg-sky-600 text-white font-bold px-8 py-2 rounded-xl shadow-lg shadow-sky-500/20"
                    @click="saveKbEdit()">
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Success Alert Modal -->
    <div x-show="showSuccessModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none" x-cloak>
        <div
            class="glass-card bg-emerald-500/10 border-emerald-500/50 p-8 rounded-2xl shadow-2xl backdrop-blur-xl flex flex-col items-center gap-4 text-center max-w-sm mx-4 transform">
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
            <div class="flex justify-between items-center p-5 border-b border-gray-200/10 bg-white/5">
                <h3 class="text-lg font-bold font-display text-gray-900 dark:text-gray-100">
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
            <div class="overflow-y-auto p-5 custom-scrollbar">
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

                <!-- Modal Footer -->
                <div class="p-5 border-t border-gray-200/10 bg-gray-50/50 dark:bg-black/20 flex justify-end gap-3 z-10">
                    <button class="btn btn-primary px-8 py-3" @click="showConfigModal = false">
                        Done
                    </button>
                </div>
            </div>
        </div>
</body>

</html>