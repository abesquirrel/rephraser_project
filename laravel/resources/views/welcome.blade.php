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
    <div class="container mx-auto px-4 py-10 max-w-7xl">

        <!-- Header -->
        <header class="header animate-fade relative text-center mb-14">
            <div class="absolute top-0 right-0">
                <button @click="toggleTheme()"
                    class="p-2 rounded-full hover:bg-black/5 dark:hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-sky-500"
                    :title="theme === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode'"
                    aria-label="Toggle Color Theme">
                    <!-- Sun Icon (for Dark Mode) -->
                    <svg x-show="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" class="icon w-6 h-6"
                        viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                    <!-- Moon Icon (for Light Mode) -->
                    <svg x-show="theme === 'light'" xmlns="http://www.w3.org/2000/svg" class="icon w-6 h-6"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </button>
            </div>

            <h1
                class="text-4xl md:text-6xl font-bold tracking-tight mb-3 bg-clip-text text-transparent bg-gradient-to-r from-sky-500 to-indigo-500 font-display">
                Paul: The Rephraser
            </h1>
            <p class="text-lg text-gray-500 dark:text-gray-400">
                Precise Support Analysis. Clean Output.
            </p>

            <button @click="showGuide = true"
                class="mt-4 inline-flex items-center gap-2 text-sm text-sky-500 hover:text-sky-600 hover:underline focus:outline-none focus:ring-2 focus:ring-sky-500 rounded px-2 py-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" stroke="currentColor"
                    fill="none" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                </svg>
                How to Use
            </button>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-[40%_60%] gap-8 items-start">

            <!-- LEFT COLUMN: Input & Config -->
            <section class="flex flex-col gap-8" aria-label="Input Configuration">
                <!-- Main Input -->
                <div class="glass-card animate-fade p-0 overflow-visible delay-[100ms]">
                    <div class="p-8 pb-6">
                        <div class="section-title mb-4 text-sky-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                                stroke="currentColor" fill="none" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                            <span>Compose Rephrasing</span>
                        </div>

                        <div class="mb-6 flex-1 flex flex-col">
                            <label for="rawInputArea" class="sr-only">Input text to rephrase</label>
                            <textarea id="rawInputArea" x-model="inputText"
                                placeholder="Write or paste your rough notes here... e.g., 'customer angry about latency, firmware update didn't help'"
                                class="w-full min-h-[300px] p-6 text-lg leading-relaxed rounded-lg bg-black/5 dark:bg-white/5 border border-transparent focus:border-sky-500 focus:ring-0 transition-colors resize-y placeholder-gray-400"></textarea>
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

                    <!-- Collapsible Configuration Drawer -->
                    <div x-data="{ configOpen: false }" class="border-t border-gray-200/50 dark:border-gray-700/50">
                        <button @click="configOpen = !configOpen"
                            class="w-full flex items-center justify-center gap-2 py-2 text-sm text-gray-500 hover:bg-black/5 transition-colors focus:outline-none focus:bg-black/5"
                            :aria-expanded="configOpen" aria-controls="configDrawer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300"
                                :class="configOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" stroke="currentColor"
                                fill="none">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                            <span x-text="configOpen ? 'Hide Configuration' : 'Show Configuration'"></span>
                        </button>

                        <div id="configDrawer" x-show="configOpen" x-collapse>
                            <div class="p-8 bg-black/5 dark:bg-white/5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <!-- Col 1 -->
                                    <div class="space-y-4">
                                        <div class="form-group">
                                            <label class="label-text">Signature</label>
                                            <input type="text" x-model="signature" placeholder="Paul"
                                                class="form-input w-full p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                        </div>
                                        <div>
                                            <label class="label-text">Category</label>
                                            <select x-model="currentCategory"
                                                class="form-select w-full p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                                <option value="">Full KB</option>
                                                <template x-for="cat in categories" :key="cat">
                                                    <option :value="cat" x-text="cat"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Col 2 -->
                                    <div class="flex flex-col gap-3">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="showThinking"
                                                class="rounded text-sky-500 focus:ring-sky-500">
                                            <span class="text-sm">Show Thinking</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="enableWebSearch"
                                                class="rounded text-sky-500 focus:ring-sky-500">
                                            <span class="text-sm">Online Research</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="templateMode"
                                                class="rounded text-sky-500 focus:ring-sky-500">
                                            <span class="text-sm">Template Mode</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="abMode"
                                                class="rounded text-sky-500 focus:ring-sky-500">
                                            <span class="text-sm">A/B Comparison</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Tuning -->
                                <div class="mt-6 pt-4 border-t border-gray-200/50 dark:border-gray-700/50">
                                    <!-- Keywords -->
                                    <div class="mb-6">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="label-text mb-0">Keywords Analysis</label>
                                            <button class="text-sky-500 text-xs hover:underline flex items-center gap-1"
                                                @click="predictKeywords()" :disabled="!inputText.trim()">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                                    viewBox="0 0 24 24" stroke="currentColor" fill="none">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                                                </svg>
                                                Predict Keywords
                                            </button>
                                        </div>
                                        <input type="text" x-model="searchKeywords"
                                            placeholder="Detected keywords will appear here..."
                                            class="form-input w-full p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                    </div>

                                    <!-- Model Selection -->
                                    <div class="mb-4">
                                        <div class="flex justify-between items-end mb-2">
                                            <label class="label-text">Model Configuration</label>
                                            <div class="flex gap-2">
                                                <button
                                                    class="info-pill cursor-pointer hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors"
                                                    @click="applyPreset('creative')">Creative</button>
                                                <button
                                                    class="info-pill cursor-pointer hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors"
                                                    @click="applyPreset('technical')">Technical</button>
                                                <button
                                                    class="info-pill cursor-pointer hover:bg-sky-100 dark:hover:bg-sky-900/30 transition-colors"
                                                    @click="applyPreset('tldr')">Concise</button>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <select x-model="modelA"
                                                class="form-select flex-1 text-sm p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                                <option value="">Primary Model...</option>
                                                <template x-for="m in availableModels" :key="m.id">
                                                    <option :value="m.id" x-text="m.name" :selected="m.id === modelA">
                                                    </option>
                                                </template>
                                            </select>
                                            <select x-model="modelB"
                                                class="form-select flex-1 text-sm p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
                                                x-show="abMode" x-transition>
                                                <option value="">Model B...</option>
                                                <template x-for="m in availableModels" :key="m.id">
                                                    <option :value="m.id" x-text="m.name" :selected="m.id === modelB">
                                                    </option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Sliders -->
                                    <div class="grid grid-cols-2 gap-6 mb-4">
                                        <div>
                                            <label class="label-text flex justify-between">
                                                <span>Creativity</span> <span x-text="temperature"></span>
                                            </label>
                                            <div class="text-xs text-gray-400 mb-1">Low for strict facts, high for
                                                creative writing.</div>
                                            <input type="range" x-model="temperature" min="0" max="1" step="0.1"
                                                class="w-full accent-sky-500">
                                        </div>
                                        <div>
                                            <label class="label-text flex justify-between">
                                                <span>Use Past Examples</span> <span x-text="kbCount"></span>
                                            </label>
                                            <div class="text-xs text-gray-400 mb-1">How many previous saved responses to
                                                mimic style from.</div>
                                            <input type="range" x-model="kbCount" min="0" max="5" step="1"
                                                class="w-full accent-sky-500">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-6 mb-4">
                                        <div>
                                            <label class="label-text flex justify-between items-center">
                                                <span title="Controls diversity via nucleus sampling.">Top P ⓘ</span>
                                                <span x-text="topP"></span>
                                            </label>
                                            <div class="text-xs text-gray-400 mb-1">Limits word choices to top
                                                probability mass.</div>
                                            <input type="range" x-model="topP" min="0" max="1" step="0.05"
                                                class="w-full accent-sky-500">
                                        </div>
                                        <div>
                                            <label class="label-text flex justify-between">
                                                <span>Response Length</span> <span x-text="maxTokens"></span>
                                            </label>
                                            <div class="text-xs text-gray-400 mb-1">Maximum length of the generated
                                                answer.</div>
                                            <input type="range" x-model="maxTokens" min="100" max="2048" step="100"
                                                class="w-full accent-sky-500">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-6 mb-4">
                                        <div>
                                            <label class="label-text flex justify-between">
                                                <span>Topic Repetition</span> <span x-text="presencePenalty"></span>
                                            </label>
                                            <div class="text-xs text-gray-400 mb-1">Increase to stop the AI from staying
                                                on one topic.</div>
                                            <input type="range" x-model="presencePenalty" min="-2" max="2" step="0.1"
                                                class="w-full accent-sky-500">
                                        </div>
                                        <div>
                                            <label class="label-text flex justify-between">
                                                <span>Word Repetition</span> <span x-text="frequencyPenalty"></span>
                                            </label>
                                            <div class="text-xs text-gray-400 mb-1">Increase to stop the AI from
                                                repeating the same words.</div>
                                            <input type="range" x-model="frequencyPenalty" min="-2" max="2" step="0.1"
                                                class="w-full accent-sky-500">
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label class="label-text flex justify-between items-center">
                                            Style Exclusions
                                            <button @click="negativePrompt = ''" x-show="negativePrompt"
                                                class="text-xs text-red-400 hover:text-red-500 transition-colors">Clear</button>
                                        </label>
                                        <input type="text" x-model="negativePrompt"
                                            placeholder="e.g. no jargon, no apologies"
                                            class="w-full p-2 text-sm rounded-lg bg-black/5 dark:bg-white/5 border border-gray-200 dark:border-gray-700 focus:ring-1 focus:ring-sky-500 outline-none transition-all">
                                    </div>
                                </div>
                            </div>
                        </div>
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

                <!-- KB Settings (Mini) -->
                <div x-data="{ expanded: false }" class="glass-card animate-fade p-0 overflow-hidden delay-[300ms]">
                    <button @click="expanded = !expanded"
                        class="w-full flex justify-between items-center p-4 hover:bg-black/5 dark:hover:bg-white/5 transition-colors focus:outline-none">
                        <div class="flex items-center gap-3 text-sm font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                                stroke="currentColor" fill="none">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Knowledge Base
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="info-pill text-xs" x-text="auditLogs.length + ' Items'"></span>
                            <span x-text="expanded ? 'Hide' : 'Manage'" class="info-pill text-xs"></span>
                        </div>
                    </button>

                    <div x-show="expanded" x-cloak x-collapse
                        class="border-t border-gray-200/50 dark:border-gray-700/50 bg-black/5 dark:bg-white/5">
                        <div class="p-4 flex gap-2 items-center">
                            <label class="sr-only" for="miniKBImport">Import KB File</label>
                            <input type="file" id="miniKBImport" @change="kbFile = $event.target.files[0]"
                                class="flex-1 text-xs">
                            <button class="btn btn-ghost text-xs flex items-center gap-1" @click="importKB()"
                                :disabled="!kbFile">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                    stroke="currentColor" fill="none">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                Import
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- RIGHT COLUMN: Output & History -->
            <section class="flex flex-col gap-8" aria-label="Output">
                <template x-if="history.length > 0">
                    <div class="animate-fade delay-[200ms]">
                        <h2 class="section-title mb-6 flex items-center justify-between">
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
                                        <div class="bubble bubble-original text-sm p-4 border-l-4 border-indigo-500"
                                            x-text="item.original"></div>
                                    </div>

                                    <!-- Refined Output -->
                                    <div class="glass-card p-6 border-sky-500 shadow-xl shadow-sky-500/10">
                                        <div class="mb-4">
                                            <span class="label-text">Refined Output</span>
                                        </div>
                                        <div x-show="!item.isEditing">
                                            <div class="bubble bubble-rephrased text-base p-5 bg-white/50 dark:bg-black/20"
                                                x-text="item.rephrased"></div>
                                        </div>
                                        <div x-show="item.isEditing" x-cloak>
                                            <label :for="'edit-' + idx" class="sr-only">Edit Rephrased Text</label>
                                            <textarea :id="'edit-' + idx" x-model="item.rephrased"
                                                class="edit-textarea w-full p-4 rounded-xl bg-white dark:bg-gray-800 border border-green-500/20 focus:border-sky-500"
                                                rows="10"></textarea>
                                        </div>

                                        <div class="flex justify-end gap-3 mt-6">
                                            <button class="btn btn-ghost px-4 py-2 text-sm"
                                                @click="copyText(item.rephrased)">Copy</button>
                                            <button class="btn btn-ghost px-4 py-2 text-sm"
                                                @click="toggleEdit(0, false)">Edit</button>
                                            <button class="btn px-5 py-2 text-sm font-semibold"
                                                :class="item.approved ? 'btn-success-ghost' : 'btn-ghost'"
                                                @click="approveHistoryEntry(item, 0, false)" :disabled="item.approved">
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
                            <span x-text="history.length - 1 + ' Items'"
                                class="info-pill bg-sky-100 dark:bg-sky-900/30"></span>
                        </button>

                        <div x-show="openArchive" x-collapse class="mt-6">
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
                                            x-text="item.rephrased"></p>

                                        <div
                                            class="flex items-center gap-2 mt-auto pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                                            <span class="text-[10px] uppercase tracking-wider font-bold text-sky-500"
                                                x-text="item.modelA || 'AI Model'"></span>
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
                <div x-show="viewModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-cloak>
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
                                        x-text="itemToView.modelA || 'Unknown Model'"></span>
                                </div>
                                <button @click="viewModal = false"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
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
                                        class="p-8 rounded-2xl bg-gradient-to-br from-sky-50 dark:from-sky-900/10 to-indigo-50 dark:to-indigo-900/10 border border-sky-100 dark:border-sky-500/20 shadow-sm">
                                        <p class="text-base md:text-lg text-gray-800 dark:text-gray-100 leading-chill font-medium"
                                            x-text="itemToView.rephrased"></p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="p-6 border-t border-gray-200/10 bg-gray-50/50 dark:bg-black/20 flex justify-end gap-3">
                                <button class="btn btn-ghost text-sm" @click="copyText(itemToView.rephrased)">Copy
                                    Text</button>
                                <button class="btn btn-primary text-sm" @click="viewModal = false">Close</button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <!-- KB Management (Advanced Section) -->
        <div x-data="{ expanded: false }" class="glass-card animate-fade p-0 overflow-hidden delay-[300ms] mt-16">
            <button @click="expanded = !expanded"
                class="w-full flex justify-between items-center p-6 hover:bg-black/5 dark:hover:bg-white/5 transition-colors focus:outline-none">
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
                    </div>
                </div>
            </div>

            <!-- Toast Notifications -->
            <div x-show="toast.active" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
                class="toast fixed bottom-8 right-8 z-50 flex items-center gap-3 px-6 py-4 bg-sky-500 text-white rounded-xl shadow-2xl font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" stroke="currentColor"
                    fill="none">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                </svg>
                <span x-text="toast.msg"></span>
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
                <div class="flex justify-between items-center p-8 pb-4 border-b border-gray-200/10">
                    <div>
                        <h2
                            class="text-3xl font-bold font-display bg-clip-text text-transparent bg-gradient-to-r from-sky-500 to-indigo-500">
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
                <div class="overflow-y-auto p-8 custom-scrollbar space-y-12">

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
                                    <strong>Configuration</strong> drawer to tweak the AI's behavior. Adjust creativity,
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

                <div class="p-8 pt-4 border-t border-gray-200/10 bg-gray-50/50 dark:bg-black/20 text-center">
                    <button class="btn btn-primary px-10 py-3 shadow-xl shadow-sky-500/20" @click="showGuide = false">
                        Start Rephrasing
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>