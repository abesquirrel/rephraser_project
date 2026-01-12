<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    <!-- Custom Logic -->
    <!-- Custom Logic & CSS (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js Core -->
    <script defer src="https://unpkg.com/alpinejs@3.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body x-data="rephraserApp()">
    <div class="container">
        <header class="header animate-fade" style="position: relative;">
            <div style="position: absolute; top: 0; right: 0;">
                <button @click="toggleTheme()" class="btn-ghost" style="padding: 0.5rem; border-radius: 50%;"
                    :title="theme === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode'">
                    <!-- Sun Icon (for Dark Mode) -->
                    <svg x-show="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                    <!-- Moon Icon (for Light Mode) -->
                    <svg x-show="theme === 'light'" xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </button>
            </div>
            <h1>Paul: The Rephraser</h1>
            <p>Precise Support Analysis. Clean Output.</p>
            <button @click="showGuide = true" class="btn-text-only"
                style="margin-top: 1rem; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                </svg>
                How to Use
            </button>
        </header>

        <div class="dashboard-grid">
            <!-- LEFT COLUMN: Input & Config -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <!-- Main Input -->
                <main class="glass-card animate-fade" style="padding: 0; overflow: visible; animation-delay: 0.1s">
                    <div style="padding: 2.25rem 2.25rem 1.5rem 2.25rem;">
                        <div class="section-title" style="margin-bottom: 1rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24"
                                stroke="currentColor" style="color: var(--accent-primary);">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                            <span>Compose Rephrasing</span>
                        </div>

                        <div style="margin-bottom: 1.5rem; flex: 1; display: flex; flex-direction: column;">
                            <textarea x-model="inputText"
                                placeholder="Write or paste your rough notes here... e.g., 'customer angry about latency, firmware update didn't help'"
                                style="padding: 1.5rem; font-size: 1.05rem; line-height: 1.6; width: 100%; min-height: 300px; resize: vertical; border-radius: 0.5rem; background: rgba(0,0,0,0.02); border: 1px solid var(--card-border); flex: 1;"></textarea>
                        </div>

                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <button class="btn btn-primary" @click="generateRephrase()"
                                :disabled="isGenerating || !inputText.trim()" style="flex: 1;">
                                <span x-show="!isGenerating" style="display: flex; align-items: center; gap: 0.5rem;">
                                    Generate Response
                                </span>
                                <span x-show="isGenerating" class="animate-pulse"
                                    style="display: flex; align-items: center; gap: 0.5rem;">
                                    Generating...
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Configuration Drawer -->
                    <div x-data="{ configOpen: false }" style="border-top: 1px solid var(--card-border);">
                        <div @click="configOpen = !configOpen" class="config-toggle">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24"
                                stroke="currentColor" :style="configOpen ? 'transform: rotate(180deg)' : ''"
                                style="transition: transform 0.3s;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                            <span x-text="configOpen ? 'Hide Configuration' : 'Show Configuration'"></span>
                        </div>

                        <div x-show="configOpen" x-collapse>
                            <div style="padding: 1.5rem 2.25rem; background: rgba(0,0,0,0.02);">
                                <div class="input-grid"
                                    style="grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 0;">
                                    <!-- Col 1 -->
                                    <div>
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <label class="label-text" style="font-size: 0.75rem;">Signature</label>
                                            <input type="text" x-model="signature" placeholder="Paul">
                                        </div>
                                        <div style="margin-top: 1rem;">
                                            <label class="label-text" style="font-size: 0.75rem;">Category</label>
                                            <select x-model="currentCategory" class="form-select">
                                                <option value="">Full KB</option>
                                                <template x-for="cat in categories" :key="cat">
                                                    <option :value="cat" x-text="cat"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Col 2 -->
                                    <div>
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <label class="custom-checkbox">
                                                <input type="checkbox" x-model="showThinking">
                                                <span>Show Thinking</span>
                                            </label>
                                            <label class="custom-checkbox">
                                                <input type="checkbox" x-model="enableWebSearch">
                                                <span>Online Research</span>
                                            </label>
                                            <label class="custom-checkbox">
                                                <input type="checkbox" x-model="templateMode">
                                                <span>Template Mode</span>
                                            </label>
                                            <label class="custom-checkbox">
                                                <input type="checkbox" x-model="abMode">
                                                <span>A/B Comparison</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tuning -->
                                <!-- Keywords & Tuning -->
                                <div
                                    style="margin-top: 1.5rem; border-top: 1px solid var(--card-border); padding-top: 1rem;">

                                    <!-- Keywords -->
                                    <div style="margin-bottom: 1.5rem;">
                                        <div
                                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <label class="label-text" style="font-size: 0.75rem; margin: 0;">Keywords
                                                Analysis</label>
                                            <button class="btn-text-only" @click="predictKeywords()"
                                                :disabled="!inputText.trim()"
                                                style="font-size: 0.75rem; display: flex; align-items: center; gap: 0.3rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                                    style="width: 1rem; height: 1rem;" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                                                </svg>
                                                Predict Keywords
                                            </button>
                                        </div>
                                        <input type="text" x-model="searchKeywords"
                                            placeholder="Detected keywords will appear here..." class="form-input"
                                            style="width: 100%;">
                                    </div>

                                    <!-- Model Selection -->
                                    <div style="margin-bottom: 1rem;">
                                        <div
                                            style="display: flex; justify-content: space-between; align-items: flex-end;">
                                            <label class="label-text" style="font-size: 0.75rem;">Model
                                                Configuration</label>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button class="info-pill" @click="applyPreset('creative')"
                                                    style="cursor: pointer; font-size: 0.7rem;">Creative</button>
                                                <button class="info-pill" @click="applyPreset('technical')"
                                                    style="cursor: pointer; font-size: 0.7rem;">Technical</button>
                                                <button class="info-pill" @click="applyPreset('tldr')"
                                                    style="cursor: pointer; font-size: 0.7rem;">Concise</button>
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                            <select x-model="modelA" class="form-select" style="font-size: 0.85rem;">
                                                <option value="">Primary Model...</option>
                                                <template x-for="m in availableModels" :key="m.id">
                                                    <option :value="m.id" x-text="m.name" :selected="m.id === modelA">
                                                    </option>
                                                </template>
                                            </select>
                                            <select x-model="modelB" class="form-select" x-show="abMode" x-transition
                                                style="font-size: 0.85rem;">
                                                <option value="">Model B...</option>
                                                <template x-for="m in availableModels" :key="m.id">
                                                    <option :value="m.id" x-text="m.name" :selected="m.id === modelB">
                                                    </option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem;">
                                        <div style="flex: 1;">
                                            <label class="label-text"
                                                style="font-size: 0.7rem; display: flex; justify-content: space-between;">
                                                <span>Creativity (Temp)</span> <span x-text="temperature"></span>
                                            </label>
                                            <div
                                                style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 0.3rem;">
                                                Lower for precision, higher for creative flair.</div>
                                            <input type="range" x-model="temperature" min="0" max="1" step="0.1"
                                                style="width: 100%; accent-color: var(--accent-primary);">
                                        </div>
                                        <div style="flex: 1;">
                                            <label class="label-text"
                                                style="font-size: 0.7rem; display: flex; justify-content: space-between;">
                                                <span>KB Context</span> <span x-text="kbCount"></span>
                                            </label>
                                            <div
                                                style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 0.3rem;">
                                                Number of similar past examples to reference.</div>
                                            <input type="range" x-model="kbCount" min="0" max="10" step="1"
                                                style="width: 100%; accent-color: var(--accent-primary);">
                                        </div>
                                    </div>

                                    <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem;">
                                        <div style="flex: 1;">
                                            <label class="label-text"
                                                style="font-size: 0.7rem; display: flex; justify-content: space-between; align-items: center;">
                                                <span title="Controls diversity via nucleus sampling.">Top P ⓘ</span>
                                                <span x-text="topP"></span>
                                            </label>
                                            <div
                                                style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 0.3rem;">
                                                Limits word choices to top probability mass.</div>
                                            <input type="range" x-model="topP" min="0" max="1" step="0.05"
                                                style="width: 100%; accent-color: var(--accent-primary);">
                                        </div>
                                        <div style="flex: 1;">
                                            <label class="label-text"
                                                style="font-size: 0.7rem; display: flex; justify-content: space-between;">
                                                <span>Max Tokens</span> <span x-text="maxTokens"></span>
                                            </label>
                                            <div
                                                style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 0.3rem;">
                                                Maximum length of the generated response.</div>
                                            <input type="range" x-model="maxTokens" min="100" max="4000" step="100"
                                                style="width: 100%; accent-color: var(--accent-primary);">
                                        </div>
                                    </div>

                                    <div style="display: flex; gap: 1.5rem;">
                                        <div style="flex: 1;">
                                            <label class="label-text"
                                                style="font-size: 0.7rem; display: flex; justify-content: space-between;">
                                                <span>Presence Penalty</span> <span x-text="presencePenalty"></span>
                                            </label>
                                            <div
                                                style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 0.3rem;">
                                                Corrects for repetition of topics.</div>
                                            <input type="range" x-model="presencePenalty" min="-2" max="2" step="0.1"
                                                style="width: 100%; accent-color: var(--accent-primary);">
                                        </div>
                                        <div style="flex: 1;">
                                            <label class="label-text"
                                                style="font-size: 0.7rem; display: flex; justify-content: space-between;">
                                                <span>Frequency Penalty</span> <span x-text="frequencyPenalty"></span>
                                            </label>
                                            <div
                                                style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 0.3rem;">
                                                Corrects for repetition of specific words.</div>
                                            <input type="range" x-model="frequencyPenalty" min="-2" max="2" step="0.1"
                                                style="width: 100%; accent-color: var(--accent-primary);">
                                        </div>
                                    </div>

                                    <div style="margin-top: 1rem;">
                                        <label class="label-text" style="font-size: 0.7rem;">Style Exclusions</label>
                                        <input type="text" x-model="negativePrompt"
                                            placeholder="e.g. no jargon, no apologies"
                                            style="font-size: 0.85rem; padding: 0.5rem; width: 100%; background: rgba(0,0,0,0.05); border: 1px solid var(--card-border); border-radius: 0.5rem; color: var(--text-main);">
                                    </div>
                                </div>
                            </div>
                        </div>
                </main>

                <!-- Status Pill Container (Floating) -->
                <div class="status-pill-container" x-show="isGenerating" x-cloak>
                    <div class="status-pill">
                        <span class="animate-pulse"
                            style="width: 8px; height: 8px; background: var(--accent-primary); border-radius: 50%;"></span>
                        <span
                            x-text="thinkingLines.length > 0 ? thinkingLines[thinkingLines.length - 1] : 'Thinking...'"></span>
                    </div>
                </div>

                <!-- KB Settings (Mini) -->
                <div x-data="{ expanded: false }" class="glass-card animate-fade"
                    style="padding: 0; animation-delay: 0.3s; overflow: hidden; margin-top: 1rem;">
                    <button @click="expanded = !expanded"
                        style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: transparent; border: none; cursor: pointer; text-align: left;">
                        <div
                            style="display: flex; align-items: center; gap: 0.75rem; color: var(--text-main); font-size: 0.9rem; font-weight: 500;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Knowledge Base
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="info-pill" style="font-size: 0.7rem;"
                                x-text="auditLogs.length + ' Items'"></span>
                            <span x-text="expanded ? 'Hide' : 'Manage'" class="info-pill"
                                style="font-size: 0.7rem;"></span>
                        </div>
                    </button>

                    <div x-show="expanded" x-cloak x-collapse
                        style="border-top: 1px solid var(--card-border); background: rgba(0,0,0,0.02);">
                        <div style="padding: 1rem;">
                            <div class="input-grid" style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="file" @change="kbFile = $event.target.files[0]"
                                    style="flex: 1; font-size: 0.8rem;">
                                <button class="btn btn-ghost" @click="importKB()" :disabled="!kbFile"
                                    style="font-size: 0.8rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon"
                                        style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                    Import
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Output & History -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <template x-if="history.length > 0">
                    <div class="animate-fade" style="animation-delay: 0.2s">
                        <h2 class="section-title"
                            style="margin-bottom: 1.5rem; font-size: 1.2rem; display: flex; align-items: center; justify-content: space-between;">
                            <span>Latest Response</span>
                            <span class="info-pill" x-text="new Date(history[0].timestamp).toLocaleTimeString()"
                                style="font-size: 0.75rem; font-weight: 400;"></span>
                        </h2>

                        <!-- Display only the latest item -->
                        <template x-for="(item, idx) in [history[0]]" :key="item.timestamp">
                            <div class="glass-card history-card" :class="{ 'approved': item.approved }"
                                style="padding: 0; overflow: hidden; border: 0; box-shadow: none; background: transparent;">

                                <div class="vertical-history-stack">
                                    <div class="glass-card" style="padding: 1.5rem; margin-bottom: 1rem;">
                                        <div
                                            style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                            <label class="label-text" style="margin: 0;">Original</label>
                                            <span class="approved-badge" x-show="item.approved">Saved</span>
                                        </div>
                                        <div class="bubble bubble-original" x-text="item.original"
                                            style="font-size: 0.95rem; padding: 1rem; border-left: 3px solid var(--accent-secondary);">
                                        </div>
                                    </div>

                                    <div class="glass-card"
                                        style="padding: 1.5rem; border-color: var(--accent-primary); box-shadow: 0 10px 30px -5px rgba(14, 165, 233, 0.15);">
                                        <div style="margin-bottom: 1rem;">
                                            <label class="label-text">Refined Output</label>
                                        </div>
                                        <div x-show="!item.isEditing">
                                            <div class="bubble bubble-rephrased" x-text="item.rephrased"
                                                style="font-size: 1.05rem; padding: 1.25rem; background: rgba(255,255,255,0.5);">
                                            </div>
                                        </div>
                                        <div x-show="item.isEditing" x-cloak>
                                            <textarea x-model="item.rephrased" class="edit-textarea"
                                                rows="10"></textarea>
                                        </div>

                                        <div class="btn-row"
                                            style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                                            <button class="btn btn-ghost" @click="copyText(item.rephrased)"
                                                style="font-size: 0.9rem; padding: 0.6rem 1rem;">Copy</button>
                                            <button class="btn btn-ghost" @click="toggleEdit(0, false)"
                                                style="font-size: 0.9rem; padding: 0.6rem 1rem;">Edit</button>
                                            <button class="btn"
                                                :class="item.approved ? 'btn-success-ghost' : 'btn-ghost'"
                                                @click="approveHistoryEntry(item, 0, false)" :disabled="item.approved"
                                                style="font-size: 0.9rem; padding: 0.6rem 1.25rem; font-weight: 600;">
                                                Approve
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Archive Section -->
                <template x-if="history.length > 1">
                    <div x-data="{ openArchive: false }">
                        <div class="glass-card"
                            style="padding: 1rem; cursor: pointer; border-style: dashed; opacity: 0.8;"
                            @click="openArchive = !openArchive">
                            <div style="display: flex; justify-content: space-between;">
                                <span class="section-title" style="margin: 0; font-size: 1rem;">Response Archive</span>
                                <span x-text="history.length - 1" class="info-pill"></span>
                            </div>
                        </div>
                        <div x-show="openArchive" x-collapse style="margin-top: 1rem;">
                            <template x-for="(item, idx) in history.slice(1)" :key="item.timestamp">
                                <div class="glass-card"
                                    style="padding: 1rem; margin-bottom: 1rem; background: rgba(0,0,0,0.02);">
                                    <div style="font-size: 0.85rem; opacity: 0.8;"
                                        x-text="item.rephrased.substring(0, 120) + '...'">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- KB Management (Advanced Section) -->
        <div x-data="{ expanded: false }" class="glass-card animate-fade"
            style="padding: 0; animation-delay: 0.3s; margin-top: 4rem; overflow: hidden;">
             <button @click="expanded = !expanded" style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; background: transparent; border: none; cursor: pointer; text-align: left;">
                <div class="section-title" style="margin: 0; display: flex; align-items: center; gap: 0.75rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" stroke="currentColor"
                        style="color: var(--accent-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    Knowledge Base Settings
                </div>
                 <span x-text="expanded ? 'Hide' : 'Expand'" class="btn-ghost" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;"></span>
            </button>

            <div x-show="expanded" x-cloak x-collapse style="border-top: 1px solid var(--card-border); background: rgba(0,0,0,0.01);">
                <div style="padding: 1.5rem;">


                     <!-- Manual Data Entry -->
                     <div style="margin-bottom: 2rem;">
                        <h3 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-main);">Add Training Data Manually</h3>
                        <div class="input-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <textarea x-model="manualOrig" placeholder="Original input (e.g. rough notes)..." rows="4"
                                class="form-input" style="width: 100%;"></textarea>
                            <textarea x-model="manualReph" placeholder="Ideal rephrased response..." rows="4"
                                class="form-input" style="width: 100%;"></textarea>
                        </div>
                        
                        <div class="input-grid" style="margin-top: 1rem; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <div style="flex: 2; min-width: 200px;">
                                <label class="label-text" style="font-size: 0.75rem; display: flex; justify-content: space-between;">
                                    Keywords
                                    <button @click="predictKeywords()" class="btn-text-only" :disabled="!manualOrig" style="font-size: 0.7rem;">
                                        Auto-Predict
                                    </button>
                                </label>
                                <input type="text" x-model="manualKeywords" placeholder="firmware, latency..." class="form-input" style="width: 100%;">
                            </div>
                            <div style="flex: 1; min-width: 150px;">
                                <label class="label-text" style="font-size: 0.75rem;">Category</label>
                                <select x-model="manualCategory" class="form-select" style="width: 100%;">
                                    <option value="">Select...</option>
                                    <template x-for="cat in categories" :key="cat">
                                        <option :value="cat" x-text="cat"></option>
                                    </template>
                                </select>
                            </div>
                             <div style="flex: 0.5; min-width: 100px;">
                                <label class="custom-checkbox" style="margin-top: 1.5rem;">
                                    <input type="checkbox" x-model="manualIsTemplate">
                                    <span>Template?</span>
                                </label>
                            </div>
                            <button class="btn btn-primary" @click="addManual()"
                                :disabled="!manualOrig.trim() || !manualReph.trim() || adding"
                                style="flex: 1; min-width: 120px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <span x-show="!adding">Add Entry</span>
                                <span x-show="adding">Saving...</span>
                            </button>
                        </div>
                    </div>

                <div style="border-top: 1px solid var(--card-border); padding-top: 2rem;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);">Recent Activity Log
                        </h3>
                        <button class="btn btn-ghost" @click="fetchAuditLogs()" style="font-size: 0.8rem;">
                            Refresh Logs
                        </button>
                    </div>
                    <div class="logs-container"
                        style="max-height: 250px; overflow-y: auto; background: rgba(0,0,0,0.02); border-radius: 0.5rem; padding: 0.5rem;">
                        <template x-if="auditLogs.length === 0">
                            <p style="text-align: center; opacity: 0.5; padding: 1rem; font-size: 0.85rem;">No recent
                                activity.</p>
                        </template>
                        <template x-for="log in auditLogs" :key="log.id">
                            <div
                                style="padding: 0.75rem; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 0.8rem;">
                                <div
                                    style="display: flex; justify-content: space-between; color: var(--accent-primary); font-weight: 500;">
                                    <span x-text="log.action"></span>
                                    <span x-text="new Date(log.created_at).toLocaleString()"
                                        style="color: var(--text-dim); font-weight: normal;"></span>
                                </div>
                                <div style="opacity: 0.7; margin-top: 0.2rem;">
                                    <span x-text="log.details || 'No details provided.'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div style="margin-top: 2rem; border-top: 1px solid var(--card-border); padding-top: 1.5rem;">
                    <div class="input-grid" style="align-items: end;">
                        <div>
                            <label class="label-text">Bulk Import (CSV)</label>
                            <p class="label-text" style="font-size: 0.7rem; opacity: 0.6; margin-bottom: 0.5rem;">
                                Format: original, rephrased, keywords, is_template, category</p>
                            <input type="file" @change="kbFile = $event.target.files[0]" id="kbFileInput"
                                style="font-size: 0.8rem; padding: 0.5rem; border: 1px dashed var(--card-border); border-radius: 0.5rem; width: 100%;">
                        </div>
                        <button class="btn btn-ghost" @click="importKB()" :disabled="!kbFile || importing">
                            Import Corpus
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div class="toast" x-show="toast.active" x-cloak x-transition:enter.duration.300ms
            x-transition:leave.duration.200ms>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
            </svg>
            <span x-text="toast.msg"></span>
        </div>
        <!-- Usage Guide Modal -->
        <div x-show="showGuide"
            style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem;"
            x-cloak>
            <div class="modal-backdrop" @click="showGuide = false" x-show="showGuide"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                style="position: absolute; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"></div>

            <div class="glass-card modal-content" x-show="showGuide"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                style="position: relative; max-width: 600px; width: 100%; max-height: 80vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 class="section-title" style="margin: 0;">How to use Paul</h2>
                    <button @click="showGuide = false" class="btn-text-only" style="font-size: 1.5rem;">&times;</button>
                </div>

                <div class="guide-steps">
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-content">
                            <h3>Compose</h3>
                            <p>Paste your raw notes or rough draft into the main text area. Don't worry about grammar.
                            </p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-content">
                            <h3>Configure</h3>
                            <p>Set your <strong>Signature</strong> (saved automatically). Toggle <strong>Web
                                    Search</strong>
                                for fact-checking or <strong>Template Mode</strong> for standard responses.</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-content">
                            <h3>Tune</h3>
                            <p>Use <strong>Temperature</strong> to control creativity (Lower = Precise, Higher =
                                Creative).
                                <strong>KB Count</strong> determines how many past examples are used for context.
                            </p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">4</div>
                        <div class="step-content">
                            <h3>Generate</h3>
                            <p>Click <strong>Generate Response</strong>. The AI will analyze your notes and the
                                Knowledge
                                Base to craft a professional reply.</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">5</div>
                        <div class="step-content">
                            <h3>Refine & Save</h3>
                            <p>Review the output. You can <strong>Edit</strong> it directly. If it's a good example,
                                click
                                <strong>Approve</strong> to save it to the Knowledge Base for future training.
                            </p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">6</div>
                        <div class="step-content">
                            <h3>Tips</h3>
                            <p>Use <strong>Style Exclusions</strong> to prevent specific bad habits (e.g., "no
                                apologies").
                                Import CSVs in <strong>Knowledge Base Settings</strong> for bulk training.</p>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 2rem; text-align: center;">
                    <button class="btn btn-primary" @click="showGuide = false">Got it!</button>
                </div>
            </div>
        </div>

    </div>
</body>

</html>