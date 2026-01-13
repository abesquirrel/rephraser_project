<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Paul: The Rephraser</title>
    <meta name="description" content="A premium, AI-powered support analyst rephrasing tool.">

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
                    Paul: The Rephraser
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Precise Support Analysis. Clean Output.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <button @click="toggleTheme()"
                    class="p-2 rounded-full hover:bg-black/5 dark:hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-sky-500"
                    :title="theme === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode'"
                    aria-label="Toggle Color Theme">
                    <!-- Sun Icon (for Dark Mode) -->
                    <svg x-show="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" class="icon w-5 h-5"
                        viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                    <!-- Moon Icon (for Light Mode) -->
                    <svg x-show="theme === 'light'" xmlns="http://www.w3.org/2000/svg" class="icon w-5 h-5"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </button>

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
                <div class="fixed bottom-6 right-6 z-20 pointer-events-none" x-show="isGenerating" x-cloak>
                    <div
                        class="status-pill flex items-center gap-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur border border-sky-500 px-4 py-2 rounded-full shadow-lg">
                        <span class="w-2 h-2 bg-sky-500 rounded-full animate-pulse"></span>
                        <span
                            x-text="thinkingLines.length > 0 ? thinkingLines[thinkingLines.length - 1] : 'Thinking...'"
                            class="text-sm font-medium"></span>
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
                                            <span class="approved-badge" x-show="item.approved">Saved</span>
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
                                                <button class="btn btn-ghost px-4 py-2 text-sm"
                                                    @click="toggleEdit(0)">Edit</button>
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
                                    <button @click="predictKeywords()" class="text-sky-500 hover:underline text-xs"
                                        :disabled="!manualOrig">
                                        Auto-Predict
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
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Recent Activity Log</h3>
                            <button class="btn btn-ghost text-xs" @click="fetchAuditLogs()">
                                Refresh Logs
                            </button>
                        </div>
                        <div
                            class="max-h-60 overflow-y-auto bg-black/5 dark:bg-white/5 rounded-lg p-2 custom-scrollbar">
                            <template x-if="auditLogs.length === 0">
                                <p class="text-center opacity-50 p-4 text-sm">No recent activity.</p>
                            </template>
                            <template x-for="log in auditLogs" :key="log.id">
                                <div class="p-3 border-b border-gray-200/10 last:border-0 text-sm">
                                    <div class="flex justify-between font-medium text-sky-500 mb-1">
                                        <span x-text="log.action"></span>
                                        <span x-text="new Date(log.created_at).toLocaleString()"
                                            class="text-gray-500 dark:text-gray-400 font-normal"></span>
                                    </div>
                                    <div class="opacity-70">
                                        <span x-text="log.details || 'No details provided.'"></span>
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

                        <!-- Usage Statistics -->
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-indigo-500 mb-4">Usage Stats
                            </h4>
                            <div class="grid grid-cols-4 gap-2">
                                <div
                                    class="glass-card bg-transparent border border-gray-200/50 dark:border-gray-700/50 p-3 text-center">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white"
                                        x-text="history.length"></div>
                                    <div class="text-[10px] uppercase font-bold text-gray-400 tracking-wide mt-1">Total
                                        Gen</div>
                                </div>
                                <div
                                    class="glass-card bg-transparent border border-gray-200/50 dark:border-gray-700/50 p-3 text-center">
                                    <div class="text-2xl font-bold text-emerald-500"
                                        x-text="history.length > 0 ? Math.round((history.filter(h => h.approved).length / history.length) * 100) + '%' : '0%'">
                                    </div>
                                    <div class="text-[10px] uppercase font-bold text-gray-400 tracking-wide mt-1">
                                        Success Rate</div>
                                </div>
                                <div
                                    class="glass-card bg-transparent border border-gray-200/50 dark:border-gray-700/50 p-3 text-center">
                                    <div class="text-xl font-bold text-sky-500 whitespace-nowrap overflow-hidden text-ellipsis"
                                        x-text="preferredModel"></div>
                                    <div class="text-[10px] uppercase font-bold text-gray-400 tracking-wide mt-1">Top
                                        Model</div>
                                </div>
                                <div
                                    class="glass-card bg-transparent border border-gray-200/50 dark:border-gray-700/50 p-3 text-center">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                                        x-text="avgLatency"></div>
                                    <div class="text-[10px] uppercase font-bold text-gray-400 tracking-wide mt-1">Avg
                                        Latency</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Toast Notifications -->
            <div x-show="toast.active" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                class="toast fixed top-6 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-6 py-3 text-white rounded-full shadow-2xl font-semibold backdrop-blur-md ring-1 ring-white/20"
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

            <div class="glass-card relative w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col shadow-2xl ring-1 ring-white/10"
                x-show="showGuide" x-transition:enter="duration-300 ease-out"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">

                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 pb-4 border-b border-gray-200/10">
                    <div>
                        <h2
                            class="text-2xl font-bold font-display bg-clip-text text-transparent bg-gradient-to-r from-sky-500 to-indigo-500">
                            Mastering Paul</h2>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">The complete guide to AI-powered support
                            analysis.</p>
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
                <div class="overflow-y-auto p-6 custom-scrollbar space-y-8">

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
                                    points, or rough drafts into the <strong>Compose Rephrasing</strong> area. Don't
                                    worry about grammar or tone.</p>
                            </div>
                            <div class="p-4 rounded-xl bg-black/5 dark:bg-white/5 border border-gray-200/10">
                                <h4 class="font-semibold mb-2 text-indigo-500">2. Configure</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Open the
                                    <strong>Settings Modal</strong> to tweak the AI's behavior. Adjust creativity,
                                    select models, or enable web search.
                                </p>
                            </div>
                            <div class="p-4 rounded-xl bg-black/5 dark:bg-white/5 border border-gray-200/10">
                                <h4 class="font-semibold mb-2 text-emerald-500">3. Refine & Save</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Generate the response. Edit it if
                                    needed, then click <strong>Approve</strong> to save it to your Knowledge Base for
                                    future learning.</p>
                            </div>
                        </div>
                    </section>

                    <!-- Section 2: Detailed Configuration -->
                    <section>
                        <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            <span
                                class="w-8 h-8 rounded-full bg-indigo-500/10 text-indigo-600 flex items-center justify-center text-sm">2</span>
                            Controls & Customization
                        </h3>
                        <div class="space-y-6">
                            <!-- Toggles -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                <div>
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Online Research</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Enables the AI to browse the
                                        live web. Use this when you need to check current status pages or documentation
                                        updates.</p>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Template Mode</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Forces the AI to strictly follow
                                        the format of your saved examples. Ideal for producing consistent reports.</p>
                                </div>
                            </div>

                            <hr class="border-gray-200/10">

                            <!-- Sliders -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Creativity</h4>
                                    <ul
                                        class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <li><strong>Low (0.0 - 0.3):</strong> Strict and factual. Best for technical
                                            logs.</li>
                                        <li><strong>High (0.7 - 1.0):</strong> Expressive and varied. Best for customer
                                            emails.</li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Other Controls</h4>
                                    <ul
                                        class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <li><strong>Use Past Examples:</strong> Increasing this helps the AI copy your
                                            writing style.</li>
                                        <li><strong>Response Length:</strong> Sets the maximum limit for the answer.
                                        </li>
                                        <li><strong>Repetition:</strong> Increase "Topic" or "Word" penalties if the AI
                                            sounds repetitive.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 3: Knowledge Base Management -->
                    <section>
                        <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            <span
                                class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-sm">3</span>
                            Knowledge Base (KB) Management
                        </h3>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                            <p>The Knowledge Base is Paul's long-term memory. It allows the system to learn from your
                                corrections and preferences over time.</p>
                            <ul>
                                <li><strong>Approve Button:</strong> Every time you click "Approve" on a generated
                                    response, that specific Original Input + Rephrased Output pair is saved to the KB.
                                </li>
                                <li><strong>Category Tagging:</strong> Assign categories (e.g., "Outage", "Billing",
                                    "Technical") to responses. You can then filter current generations to only use
                                    context from a specific category.</li>
                                <li><strong>Manual Entry:</strong> Open the "Knowledge Base Settings" at the bottom to
                                    manually add training data or "Golden Samples" without generating them first.</li>
                                <li><strong>Bulk Import:</strong> You can upload a CSV file to bulk-train the system.
                                    Format: <code>original, rephrased, keywords, is_template, category</code>.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section 4: Models -->
                    <section>
                        <h3 class="flex items-center gap-3 text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                            <span
                                class="w-8 h-8 rounded-full bg-purple-500/10 text-purple-600 flex items-center justify-center text-sm">4</span>
                            Available Models
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div
                                class="p-4 border border-gray-200/10 rounded-xl bg-white/5 hover:bg-white/10 transition-colors">
                                <span class="text-base font-bold block mb-1 text-sky-500">Llama 3 (Local)</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">The balanced choice for standard
                                    support tickets. Excellent at turning bullet points into polite, professional email
                                    replies without over-embellishing.</span>
                            </div>
                            <div
                                class="p-4 border border-gray-200/10 rounded-xl bg-white/5 hover:bg-white/10 transition-colors">
                                <span class="text-base font-bold block mb-1 text-indigo-500">Gemma 2 9B (Local)</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Best for "humanizing" robotic
                                    text. Use for sensitive customer escalations where tone and empathy are as important
                                    as the facts.</span>
                            </div>
                            <div
                                class="p-4 border border-gray-200/10 rounded-xl bg-white/5 hover:bg-white/10 transition-colors">
                                <span class="text-base font-bold block mb-1 text-emerald-500">Mistral (Local)</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Ideal for concise status updates
                                    and internal technical notes. Strips away fluff and gets straight to the point very
                                    quickly.</span>
                            </div>
                        </div>
                    </section>

                </div>

                <div class="p-6 pt-4 border-t border-gray-200/10 bg-gray-50/50 dark:bg-black/20 text-center">
                    <button class="btn btn-primary px-10 py-3 shadow-xl shadow-sky-500/20" @click="showGuide = false">
                        Start Rephrasing
                    </button>
                </div>
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

                    <!-- Left Column: User & Model -->
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-sky-500 mb-4">Core Model</h4>
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="label-text">Signature</label>
                                    <input type="text" x-model="signature" placeholder="Paul"
                                        class="form-input w-full p-3 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200 dark:border-gray-700">
                                </div>

                                <div>
                                    <label class="label-text mb-2 block">Primary Model</label>
                                    <select x-model="modelA"
                                        class="form-select w-full p-3 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200 dark:border-gray-700">
                                        <option value="">Select Model...</option>
                                        <template x-for="m in availableModels" :key="m.id">
                                            <option :value="m.id" x-text="m.name" :selected="m.id === modelA"></option>
                                        </template>
                                    </select>
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

                                <!-- Keywords (Restored) -->
                                <div>
                                    <label class="label-text flex justify-between">
                                        Keywords
                                        <button @click="predictKeywords()" class="text-sky-500 hover:underline text-xs"
                                            :disabled="!inputText">
                                            Auto-Predict
                                        </button>
                                    </label>
                                    <div class="relative">
                                        <input type="text" x-model="searchKeywords" placeholder="firmware, latency..."
                                            class="form-input w-full p-3 rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200 dark:border-gray-700 pr-10">
                                        <!-- Simple icon or loading state could go here -->
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">Tags to guide the AI's focus.</div>
                                </div>

                                <div class="space-y-3 pt-2">
                                    <label
                                        class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                        <input type="checkbox" x-model="showThinking"
                                            class="rounded text-sky-500 focus:ring-sky-500 w-5 h-5">
                                        <span class="font-medium">Show Thinking Process</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                        <input type="checkbox" x-model="enableWebSearch"
                                            class="rounded text-sky-500 focus:ring-sky-500 w-5 h-5">
                                        <span class="font-medium">Online Research</span>
                                    </label>
                                    <label
                                        class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                        <input type="checkbox" x-model="templateMode"
                                            class="rounded text-sky-500 focus:ring-sky-500 w-5 h-5">
                                        <span class="font-medium">Template Mode</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Fine Tuning & KB -->
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-emerald-500 mb-4">Fine Tuning
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
                                    <div class="text-xs text-gray-400 mt-1">Low (0.2) for strict facts, High (0.8) for
                                        creative writing.</div>
                                </div>
                                <!-- Past Examples -->
                                <div>
                                    <label class="label-text flex justify-between">
                                        <span>Use Past Examples</span> <span x-text="kbCount"></span>
                                    </label>
                                    <input type="range" x-model="kbCount" min="0" max="5" step="1"
                                        class="w-full accent-emerald-500 mt-2">
                                    <div class="text-xs text-gray-400 mt-1">Number of saved responses to use as style
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
                                    <div class="text-xs text-gray-400 mt-1">Increase to stop the AI from staying on one
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

                        <!-- KB Widget Embedded -->
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Knowledge Base
                            </h4>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2 group relative">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span
                                            class="text-sm font-medium cursor-help border-b border-dashed border-gray-400">Memory
                                            Active</span>
                                        <!-- Tooltip -->
                                        <div
                                            class="absolute bottom-full left-0 mb-2 w-48 p-2 bg-black text-white text-xs rounded hidden group-hover:block z-50 shadow-xl">
                                            New approved responses will be saved to the Knowledge Base.
                                        </div>
                                    </div>
                                    <span class="info-pill bg-gray-100 dark:bg-gray-800"
                                        x-text="auditLogs.length + ' Items'"></span>
                                </div>
                                <div class="flex gap-2">
                                    <input type="file" @change="kbFile = $event.target.files[0]"
                                        class="block w-full text-xs text-gray-500 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 transition-colors">
                                    <button class="btn btn-ghost text-xs" @click="importKB()"
                                        :disabled="!kbFile">Import</button>
                                </div>
                            </div>
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