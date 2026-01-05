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
                        <label class="custom-checkbox">
                            <input type="checkbox" x-model="abMode">
                            <span>A/B Comparison</span>
                        </label>
                    </div>

                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;" x-transition>
                        <select x-model="modelA" class="form-select">
                            <option value="">Select Primary Model...</option>
                            <template x-for="m in availableModels" :key="m.id">
                                <option :value="m.id" x-text="m.name" :selected="m.id === modelA"></option>
                            </template>
                        </select>
                        <select x-model="modelB" class="form-select" x-show="abMode" x-transition>
                            <option value="">Select Model B...</option>
                            <template x-for="m in availableModels" :key="m.id">
                                <option :value="m.id" x-text="m.name" :selected="m.id === modelB"></option>
                            </template>
                        </select>
                    </div>

                    <div style="margin-top: 1rem;">
                        <label class="label-text" style="font-size: 0.75rem;">Category Context</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <select x-model="currentCategory" class="form-select">
                                <option value="">No Filter (Full KB)</option>
                                <template x-for="cat in categories" :key="cat">
                                    <option :value="cat" x-text="cat"></option>
                                </template>
                            </select>
                            <input type="text" x-model="newCategory" @keydown.enter.prevent="addCategory()" placeholder="Add..." style="width: 80px; font-size: 0.8rem; padding: 0.4rem;">
                            <button @click="addCategory()" class="btn-text-only" style="font-size: 1.2rem;">➕</button>
                        </div>
                    </div>

                    <!-- Engine Tuning -->
                    <div style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
                        <span style="font-size: 0.7rem; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Engine Tuning</span>
                        
                        <div style="margin-top: 0.8rem;">
                            <label class="label-text" style="font-size: 0.7rem; display: flex; justify-content: space-between;">
                                <span>Creativity (Temp)</span>
                                <span x-text="temperature"></span>
                            </label>
                            <input type="range" x-model="temperature" min="0" max="1" step="0.1" style="width: 100%; accent-color: var(--accent);">
                            <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); margin-top: 0.2rem;">Low = Precise (Technical) | High = Original (Creative)</div>
                        </div>

                        <div style="margin-top: 0.8rem;">
                            <label class="label-text" style="font-size: 0.7rem; display: flex; justify-content: space-between;">
                                <span>Output Depth (Tokens)</span>
                                <span x-text="maxTokens"></span>
                            </label>
                            <input type="range" x-model="maxTokens" min="100" max="2000" step="100" style="width: 100%; accent-color: var(--accent);">
                            <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); margin-top: 0.2rem;">Higher allows for longer, more detailed explanations.</div>
                        </div>

                        <div style="margin-top: 0.8rem;">
                            <label class="label-text" style="font-size: 0.7rem; display: flex; justify-content: space-between;">
                                <span>Context Hits (KB)</span>
                                <span x-text="kbCount"></span>
                            </label>
                            <input type="range" x-model="kbCount" min="1" max="10" step="1" style="width: 100%; accent-color: var(--accent);">
                            <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); margin-top: 0.2rem;">Uses more past examples to better mimic your style.</div>
                        </div>
                    </div>

                    <div x-show="enableWebSearch" x-transition.opacity style="margin-top: 1.5rem;">
                        <label class="label-text" style="font-size: 0.75rem; display: flex; justify-content: space-between;">
                            Target Keywords
                            <button @click="predictKeywords()" class="btn-text-only" :disabled="isGenerating">🪄 Predict</button>
                        </label>
                        <input type="text" x-model="searchKeywords" placeholder="firmware, latency, region...">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" @click="generateRephrase()" :disabled="isGenerating || !inputText.trim()">
                <span x-show="!isGenerating">✨ Generate Response</span>
                <span x-show="isGenerating" class="animate-pulse">⏳ Analyzing Intelligence...</span>
            </button>

            <!-- Real-time Thinking Visualizer -->
            <div class="logs-container" x-show="isGenerating && thinkingLines.length > 0" x-cloak x-transition>
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
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <select x-model="item.category" class="form-select" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; width: auto; background: rgba(59, 130, 246, 0.2); border-color: rgba(59, 130, 246, 0.3);">
                                        <option value="">No Category</option>
                                        <template x-for="cat in categories" :key="cat">
                                            <option :value="cat" x-text="cat"></option>
                                        </template>
                                    </select>
                                </div>
                                <template x-if="item.approved">
                                    <span class="approved-badge">✅ Saved to KB</span>
                                </template>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <button @click="deleteHistoryEntry(0)" class="btn-text-only" style="color: #ef4444; text-decoration: none;">🗑️ Delete</button>
                                <span class="info-pill" style="opacity: 0.7;" x-text="new Date(item.timestamp).toLocaleTimeString()"></span>
                            </div>
                        </div>

                        <div class="vertical-history-stack">
                            <div style="margin-bottom: 2rem;">
                                <label class="label-text">Original Input</label>
                                <div class="bubble bubble-original" x-text="item.original"></div>
                            </div>
                            
                            <div class="variants-row">
                                <div class="variant-col">
                                    <label class="label-text" x-text="item.rephrasedB ? 'Variant A (' + (item.modelA_name || 'Llama3') + ')' : 'AI Synthesis'"></label>
                                    
                                    <div x-show="!item.isEditing">
                                        <div class="bubble bubble-rephrased" x-text="item.rephrased"></div>
                                    </div>
                                    <div x-show="item.isEditing" x-cloak>
                                        <textarea x-model="item.rephrased" class="edit-textarea" rows="6"></textarea>
                                    </div>

                                    <div class="btn-row" style="margin-top: 1rem;">
                                        <button class="btn btn-ghost" @click="copyText(item.rephrased)">📋 Copy</button>
                                        <button class="btn btn-ghost" @click="toggleEdit(0, false)" x-text="item.isEditing ? '💾 Done' : '✏️ Edit'"></button>
                                        <button class="btn" :class="item.approved ? 'btn-success-ghost' : 'btn-ghost'" 
                                                @click="approveHistoryEntry(item, 0, false)" :disabled="item.approved">
                                            <span x-text="item.approved ? '✅ Saved' : '👍 Approve A'"></span>
                                        </button>
                                    </div>
                                </div>

                                <template x-if="item.rephrasedB">
                                    <div class="variant-col">
                                        <label class="label-text" x-text="'Variant B (' + (item.modelB_name || 'Mistral') + ')'"></label>
                                        
                                        <div x-show="!item.isEditingB">
                                            <div class="bubble bubble-rephrased" style="border-color: #3b82f6;" x-text="item.rephrasedB"></div>
                                        </div>
                                        <div x-show="item.isEditingB" x-cloak>
                                            <textarea x-model="item.rephrasedB" class="edit-textarea" style="border-color: #3b82f6;" rows="6"></textarea>
                                        </div>

                                        <div class="btn-row" style="margin-top: 1rem;">
                                            <button class="btn btn-ghost" @click="copyText(item.rephrasedB)">📋 Copy</button>
                                            <button class="btn btn-ghost" @click="toggleEdit(0, true)" x-text="item.isEditingB ? '💾 Done' : '✏️ Edit'"></button>
                                            <button class="btn btn-ghost" @click="approveHistoryEntry(item, 0, true)" :disabled="item.approved">
                                                👍 Approve B
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1.5rem;">
                             <button class="btn btn-ghost" @click="regenerateFrom(item.original)">🔄 Regenerate All</button>
                        </div>
                    </div>
                </template>

                <!-- Archive Section for older items -->
                <template x-if="history.length > 1">
                    <div x-data="{ openArchive: false }" style="margin-top: 2rem;">
                        <div style="display: flex; justify-content: flex-end; margin-bottom: 0.5rem;">
                            <button @click="toggleAllHistory()" class="btn-text-only" x-text="allExpanded ? 'Collapse All' : 'Expand All'"></button>
                        </div>
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
                                            <select x-model="item.category" class="form-select" @click.stop style="padding: 0.1rem 0.4rem; font-size: 0.7rem; width: auto; background: rgba(255,255,255,0.05);">
                                                <option value="">Cat...</option>
                                                <template x-for="cat in categories" :key="cat">
                                                    <option :value="cat" x-text="cat"></option>
                                                </template>
                                            </select>
                                            <input type="text" x-model="item.keywords" placeholder="Keywords..." @click.stop style="padding: 0.1rem 0.4rem; font-size: 0.7rem; width: 100px; background: transparent; height: auto;">
                                            <template x-if="item.is_template">
                                                <span class="info-pill" style="font-size: 0.7rem;">📄 Tpl</span>
                                            </template>
                                            <span x-show="!item.expanded" class="info-pill" style="opacity: 0.5; font-size: 0.8rem; font-weight: 400;" x-text="item.rephrased.substring(0, 50) + '...'"></span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <button @click.stop="deleteHistoryEntry(idx + 1)" class="btn-text-only" style="color: #ef4444; font-size: 0.7rem; text-decoration: none;">🗑️ Del</button>
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
                                                    @click="approveHistoryEntry(item, idx + 1)" 
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
                            <div>
                                <label class="label-text" style="font-size: 0.75rem; display: flex; justify-content: space-between;">
                                    Keywords
                                    <button @click="predictKeywords()" class="btn-text-only">🪄 Predict</button>
                                </label>
                                <input type="text" x-model="manualKeywords" placeholder="firmware, latency..." style="font-size: 0.9rem;">
                            </div>
                            <div>
                                <label class="label-text" style="font-size: 0.75rem;">Category</label>
                                <select x-model="manualCategory" class="form-select" style="font-size: 0.9rem;">
                                    <option value="">Select Category...</option>
                                    <template x-for="cat in categories" :key="cat">
                                        <option :value="cat" x-text="cat"></option>
                                    </template>
                                </select>
                            </div>
                            <label class="custom-checkbox">
                                <input type="checkbox" x-model="manualIsTemplate">
                                <span>Is Template?</span>
                            </label>
                        </div>
                        <button class="btn btn-ghost" style="margin-top: 1.5rem;" @click="addManual()" :disabled="!manualOrig.trim() || !manualReph.trim() || adding">
                            Add Training Pair
                        </button>
                    </div>

                    <div style="border-top: 1px solid var(--card-border); margin: 2rem 0; padding-top: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <label class="label-text">Audit Trail</label>
                            <button class="btn btn-ghost" @click="fetchAuditLogs()" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">🔄 Sync Logs</button>
                        </div>
                        <div class="logs-container" style="max-height: 300px; overflow-y: auto;">
                            <template x-if="auditLogs.length === 0">
                                <p class="label-text" style="text-align: center; opacity: 0.5; padding: 1rem;">No recent approvals logged.</p>
                            </template>
                            <template x-for="log in auditLogs" :key="log.id">
                                <div style="padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.85rem;">
                                    <div style="display: flex; justify-content: space-between; color: var(--accent-primary);">
                                        <strong x-text="log.action"></strong>
                                        <span x-text="new Date(log.created_at).toLocaleString()"></span>
                                    </div>
                                    <div style="opacity: 0.6; margin-top: 0.25rem;">
                                        User: <span x-text="log.user_name"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
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
