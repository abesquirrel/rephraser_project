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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js Plugins -->
    <script defer src="https://unpkg.com/@alpinejs/persist@3.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x/dist/cdn.min.js"></script>
    <!-- Alpine.js Core -->
    <script defer src="https://unpkg.com/alpinejs@3.x/dist/cdn.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body x-data="rephraserApp()">
    <div class="container">
        <header class="header animate-fade">
            <h1>Paul: The Rephraser</h1>
            <p>Precise Support Analysis. Clean Output.</p>
        </header>

        <!-- Main Input -->
        <main class="glass-card animate-fade" style="animation-delay: 0.1s">
            <div class="section-title">
                <span>✍️</span>
                <span>Compose Rephrasing</span>
            </div>

            <div class="input-grid">
                <div>
                    <label class="label-text">Your Notes</label>
                    <textarea x-model="inputText" placeholder="e.g., customer reported high latency in the Midwest region after firmware update..." rows="8"></textarea>
                </div>
                <div>
                    <label class="label-text">Configuration</label>
                    <div class="control-panel">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="label-text" style="font-size: 0.75rem;">Signature</label>
                            <input type="text" x-model="signature" placeholder="Paul">
                        </div>
                        
                        <label class="custom-checkbox">
                            <input type="checkbox" x-model="showThinking">
                            <span>Process Logs</span>
                        </label>
                        <label class="custom-checkbox">
                            <input type="checkbox" x-model="templateMode" @change="if(templateMode) enableWebSearch = false">
                            <span>Direct Template</span>
                        </label>
                        <label class="custom-checkbox">
                            <input type="checkbox" x-model="enableWebSearch" @change="if(enableWebSearch) templateMode = false">
                            <span>Online Research</span>
                        </label>
                    </div>

                    <div x-show="enableWebSearch" x-transition.opacity style="margin-top: 1.5rem;">
                        <label class="label-text" style="font-size: 0.75rem;">Target Keywords</label>
                        <input type="text" x-model="searchKeywords" placeholder="firmware, latency, region...">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" @click="generateAction()" :disabled="processing || !inputText.trim()">
                <span x-show="!processing">✨ Generate Response</span>
                <span x-show="processing" class="animate-pulse">⏳ Analyzing Intelligence...</span>
            </button>

            <!-- Real-time Thinking Visualizer -->
            <div class="logs-container" x-show="processing && thinkingLines.length > 0" x-cloak x-transition>
                <template x-for="line in thinkingLines" :key="line">
                    <div class="log-line">
                        <span>›</span>
                        <span x-text="line"></span>
                    </div>
                </template>
            </div>
        </main>

        <template x-if="history.length > 0">
            <div class="animate-fade" style="animation-delay: 0.2s">
                <h2 class="section-title" style="margin-bottom: 2rem; margin-top: 4rem;">
                    <span>📋</span> Latest Response
                </h2>
                
                <!-- Display only the latest item -->
                <template x-for="(item, idx) in [history[0]]" :key="item.timestamp">
                    <div class="glass-card history-card" 
                         :class="{ 'approved': item.approved }"
                         style="margin-bottom: 3rem;">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <h3 class="section-title" style="margin: 0; font-size: 1.1rem;">
                                    Newest Entry <span x-text="history.length" class="info-pill" style="background: var(--accent-primary); color: #0b0f19;"></span>
                                </h3>
                                <template x-if="item.approved">
                                    <span class="approved-badge">✅ Saved to KB</span>
                                </template>
                            </div>
                            <span class="info-pill" style="opacity: 0.7;" x-text="new Date(item.timestamp).toLocaleTimeString()"></span>
                        </div>

                        <div class="history-grid">
                            <div>
                                <label class="label-text">Original Input</label>
                                <div class="bubble bubble-original" x-text="item.original"></div>
                            </div>
                            <div>
                                <label class="label-text">AI Synthesis</label>
                                <div class="bubble bubble-rephrased" x-text="item.rephrased"></div>
                            </div>
                        </div>

                        <div class="btn-row">
                            <button class="btn btn-ghost" @click="copyText(item.rephrased)">
                                📋 Copy Text
                            </button>
                            <button class="btn btn-ghost" @click="regenerateFrom(item.original)">
                                🔄 Regenerate
                            </button>
                            <button class="btn" 
                                    :class="item.approved ? 'btn-success-ghost' : 'btn-ghost'" 
                                    @click="approveEntry(item, 0)" 
                                    :disabled="item.approved || item.approving">
                                <span x-show="!item.approving && !item.approved">👍 Approve & Save</span>
                                <span x-show="item.approving">Saving...</span>
                                <span x-show="item.approved">✅ Successfully Saved</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Archive Section for older items -->
                <template x-if="history.length > 1">
                    <div x-data="{ openArchive: false }" style="margin-top: 2rem;">
                        <div class="glass-card" style="padding: 1.25rem; cursor: pointer; border-style: dashed;" @click="openArchive = !openArchive">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div class="section-title" style="margin: 0; font-size: 1.1rem;">
                                    <span>📦</span> Response Archive
                                    <span class="info-pill" style="margin-left: 0.5rem;" x-text="(history.length - 1) + ' previous items'"></span>
                                </div>
                                <span style="font-size: 1.2rem; transition: transform 0.2s;" :style="openArchive ? 'transform: rotate(180deg)' : ''">▼</span>
                            </div>
                        </div>

                        <div x-show="openArchive" x-collapse>
                            <template x-for="(item, idx) in history.slice(1)" :key="item.timestamp">
                                <div class="glass-card history-card" 
                                     :class="{ 'collapsed': !item.expanded, 'approved': item.approved }"
                                     style="margin-bottom: 1.5rem; background: rgba(0,0,0,0.15);">
                                    
                                    <div class="history-card-header" @click="item.expanded = !item.expanded">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <h3 class="section-title" style="margin: 0; font-size: 1rem;">
                                                Entry <span x-text="history.length - 1 - idx" class="info-pill"></span>
                                            </h3>
                                            <template x-if="item.approved">
                                                <span class="approved-badge">Saved</span>
                                            </template>
                                            <template x-if="item.is_template">
                                                <span class="info-pill" style="font-size: 0.7rem;">📄 Template</span>
                                            </template>
                                            <template x-if="item.keywords">
                                                <span class="info-pill" style="font-size: 0.7rem; opacity: 0.8;" x-text="'🏷️ ' + item.keywords"></span>
                                            </template>
                                            <span x-show="!item.expanded" class="info-pill" style="opacity: 0.5; font-size: 0.8rem; font-weight: 400;" x-text="item.rephrased.substring(0, 50) + '...'"></span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <span class="info-pill" style="opacity: 0.7;" x-text="new Date(item.timestamp).toLocaleTimeString()"></span>
                                            <span style="font-size: 1.1rem; transition: transform 0.2s;" :style="item.expanded ? 'transform: rotate(180deg)' : ''">▼</span>
                                        </div>
                                    </div>

                                    <div x-show="item.expanded" x-collapse>
                                        <div class="history-grid" style="margin-top: 1.5rem;">
                                            <div>
                                                <label class="label-text">Original Input</label>
                                                <div class="bubble bubble-original" style="font-size: 0.85rem;" x-text="item.original"></div>
                                            </div>
                                            <div>
                                                <label class="label-text">AI Synthesis</label>
                                                <div class="bubble bubble-rephrased" style="font-size: 0.85rem;" x-text="item.rephrased"></div>
                                            </div>
                                        </div>

                                        <div class="btn-row">
                                            <button class="btn btn-ghost" style="padding: 0.6rem; font-size: 0.9rem;" @click="copyText(item.rephrased)">
                                                📋 Copy
                                            </button>
                                            <button class="btn btn-ghost" style="padding: 0.6rem; font-size: 0.9rem;" @click="regenerateFrom(item.original)">
                                                🔄 Regenerate
                                            </button>
                                            <button class="btn" style="padding: 0.6rem; font-size: 0.9rem;" 
                                                    :class="item.approved ? 'btn-success-ghost' : 'btn-ghost'" 
                                                    @click="approveEntry(item, idx + 1)" 
                                                    :disabled="item.approved || item.approving">
                                                <span x-show="!item.approving && !item.approved">👍 Approve</span>
                                                <span x-show="item.approving">Saving...</span>
                                                <span x-show="item.approved">✅ Saved</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- KB Management (Advanced Section) -->
        <div x-data="{ expanded: false }" class="glass-card animate-fade" style="padding: 1rem; animation-delay: 0.3s; margin-top: 4rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; padding: 0.5rem;" @click="expanded = !expanded">
                <div class="section-title" style="margin: 0;">
                    <span>📚</span> Knowledge Base Settings
                </div>
                <span x-text="expanded ? 'Hide' : 'Show'" class="info-pill"></span>
            </div>
            
            <div x-show="expanded" x-cloak x-transition.scale.origin.top>
                <div style="padding: 1.5rem 0.5rem;">
                    <div class="input-grid" style="align-items: end;">
                        <div>
                            <label class="label-text">Bulk Import (CSV)</label>
                            <p class="label-text" style="font-size: 0.7rem; opacity: 0.6; margin-bottom: 0.5rem;">Format: original, rephrased, keywords, is_template(bool)</p>
                            <input type="file" @change="kbFile = $event.target.files[0]" id="kbFileInput" style="font-size: 0.8rem; padding: 0.5rem; border-dashed: true;">
                        </div>
                        <button class="btn btn-ghost" @click="importKB()" :disabled="!kbFile || importing">
                            Import Corpus
                        </button>
                    </div>

                    <div style="border-top: 1px solid var(--card-border); margin: 2rem 0; padding-top: 2rem;">
                        <label class="label-text">Manual Data Entry</label>
                        <div class="input-grid">
                            <textarea x-model="manualOrig" placeholder="Input example..." rows="3" style="font-size: 0.9rem;"></textarea>
                            <textarea x-model="manualReph" placeholder="Optimal response..." rows="3" style="font-size: 0.9rem;"></textarea>
                        </div>
                        <div class="input-grid" style="margin-top: 1rem; align-items: center;">
                            <input type="text" x-model="manualKeywords" placeholder="Keywords (comma separated)..." style="font-size: 0.9rem;">
                            <label class="custom-checkbox">
                                <input type="checkbox" x-model="manualIsTemplate">
                                <span>Is Template?</span>
                            </label>
                        </div>
                        <button class="btn btn-ghost" style="margin-top: 1.5rem;" @click="addManual()" :disabled="!manualOrig.trim() || !manualReph.trim() || adding">
                            Add Training Pair
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div class="toast" x-show="toast.active" x-cloak x-transition:enter.duration.300ms x-transition:leave.duration.200ms>
            <span>✨</span>
            <span x-text="toast.msg"></span>
        </div>
    </div>

    <!-- Custom Logic -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
