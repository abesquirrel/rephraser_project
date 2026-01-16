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



                <!-- Status Pill Container (Floating) -->
                <!-- Centered Generation Feedback Overlay -->
                <div x-show="isGenerating" x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition-opacity"
                    x-transition:enter="duration-300 ease-out" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="duration-200 ease-in"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                    <div
                        class="glass-card p-8 rounded-3xl flex flex-col items-center gap-6 shadow-2xl border border-sky-500/30 bg-black/80 max-w-sm w-full mx-4">
                        <!-- Pulsing AI Brain / Spinner -->
                        <div class="relative w-20 h-20 flex items-center justify-center">
                            <div class="absolute inset-0 bg-sky-500/20 rounded-full animate-ping"></div>
                            <div class="absolute inset-2 bg-indigo-500/20 rounded-full animate-pulse delay-75"></div>
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-10 h-10 text-sky-400 animate-pulse relative z-10" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>

                        <div class="text-center space-y-2">
                            <h3 class="text-xl font-bold text-white bg-clip-text text-transparent bg-gradient-to-r from-sky-400 to-indigo-400"
                                x-text="friendlyStatus">
                            </h3>
                            <p class="text-sm text-gray-400" x-text="status !== friendlyStatus ? status : ''"></p>
                            <!-- Sub-text for tech details if different -->
                        </div>

                        <!-- Progress Bar (Fake but visual) -->
                        <div class="w-full bg-gray-700 rounded-full h-1.5 overflow-hidden relative">
                            <div
                                class="absolute inset-y-0 left-0 bg-gradient-to-r from-sky-500 to-indigo-500 w-1/3 animate-[progress_2s_ease-in-out_infinite]">
                            </div>
                        </div>
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

                @include('components.modals.response-details')
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
                            <button class="btn btn-ghost text-xs" @click="fetchKbStats()">
                                Refresh
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

        @include('components.modals.usage-guide')
    </div>

    @include('components.modals.prune-review')

    @include('components.modals.edit-kb-entry')
    @include('components.modals.success-alert')

    @include('components.modals.configuration')
</body>

</html>