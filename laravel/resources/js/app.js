import './bootstrap';

function rephraserApp() {
    return {
        inputText: '',
        rephrasedContent: '',
        status: '',
        signature: Alpine.$persist('Paul').as('rephraser_sig'), // Kept persist for signature
        enableWebSearch: true,
        templateMode: false,
        searchKeywords: '',
        currentCategory: '', // Tier 2
        newCategory: '', 
        categories: ['General', 'Technical', 'Billing', 'Sales', 'Feedback'],
        modelA: 'llama3:8b-instruct-q3_K_M',
        availableModels: Alpine.$persist([
            {id: 'llama3:8b-instruct-q3_K_M', name: 'Llama3 (Default)'}
        ]).as('rephraser_enabled_models'),
        ollamaModels: [], // Raw list from API
        isGenerating: false,
        auditLogs: [],
        activeTab: 'generator', // generator, history, audit
        thinkingLines: [],
        isRefreshingModels: false,
        isRefreshingArchive: false,
        history: Alpine.$persist([]).as('rephraser_log_v3'),
        allExpanded: false,
        showThinking: Alpine.$persist(true).as('rephraser_show_thinking'),
        
        // Tuning
        temperature: 0.5,
        maxTokens: 600,
        kbCount: 3,
        topP: 0.9,
        frequencyPenalty: 0.0,
        presencePenalty: 0.0,
        autoTokens: Alpine.$persist(true).as('rephraser_auto_tokens'),
        negativePrompt: Alpine.$persist('').as('rephraser_negative_prompt'),
        modelSettings: Alpine.$persist({}).as('rephraser_model_settings'),
        
        // Authentication
        currentUser: null,
        showLogin: false,
        showRegister: false,
        loginForm: { login: '', password: '' },
        registerForm: { name: '', username: '', email: '', password: '' },
        
        // UI State
        showGuide: false,
        
        toast: { active: false, msg: '', type: 'info' },
        showSuccessModal: false,
        showConfigModal: false,
        successMessage: '',
        
        // KB
        
        // KB
        kbFile: null,
        importing: false,
        
        // Analytics
        sessionId: Alpine.$persist(null).as('rephraser_session_id'),

        async startTracking() {
             if (!this.sessionId) {
                 this.sessionId = crypto.randomUUID();
             }
             
             try {
                 await fetch('/api/session/start', {
                     method: 'POST',
                     headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                     },
                     body: JSON.stringify({
                         session_id: this.sessionId,
                         user_signature: this.currentUser?.signature || this.signature,
                         theme: 'dark' // Fixed for now
                     })
                 });
             } catch (e) {
                 console.error('Session tracking failed', e);
             }
        },
        kbStats: { total_entries: 0, last_updated: null, category_breakdown: [] }, // Added kbStats
        manualOrig: '',
        manualReph: '',
        manualKeywords: '',
        manualIsTemplate: false,
        manualCategory: '', // Added manualCategory
        adding: false,
        isPredictingKeywords: false,

        // Archive State
        viewModal: false,
        itemToView: null,
        viewModal: false,
        itemToView: null,
        archiveFilter: 'all', // 'all', 'saved', 'unsaved'
        currentPage: 1,
        itemsPerPage: 6,

        get paginatedHistory() {
            // Slice(1) to skip the latest response which is shown in the main view
            let archive = this.history.slice(1);
            
            // Apply filtering
            if (this.archiveFilter === 'saved') {
                archive = archive.filter(item => item.approved);
            } else if (this.archiveFilter === 'unsaved') {
                archive = archive.filter(item => !item.approved);
            }

            const start = (this.currentPage - 1) * this.itemsPerPage;
            return archive.slice(start, start + this.itemsPerPage);
        },

        get totalFilteredCount() {
            let archive = this.history.slice(1);
            if (this.archiveFilter === 'saved') return archive.filter(i => i.approved).length;
            if (this.archiveFilter === 'unsaved') return archive.filter(i => !i.approved).length;
            return archive.length;
        },

        get totalPages() {
            return Math.ceil(this.totalFilteredCount / this.itemsPerPage) || 1;
        },

        get preferredModel() {
            if (this.history.length === 0) return 'None';
            const counts = {};
            for (const item of this.history) {
                const model = item.modelA_name || 'Unknown';
                counts[model] = (counts[model] || 0) + 1;
            }
            const topModel = Object.keys(counts).reduce((a, b) => counts[a] > counts[b] ? a : b);
            // Clean up model name
            return topModel.replace(':latest', '').replace(':8b-instruct-q3_K_M', '');
        },

        get avgLatency() {
            const timedItems = this.history.filter(h => h.duration);
            if (timedItems.length === 0) return '0.0s';
            const avg = timedItems.reduce((acc, curr) => acc + curr.duration, 0) / timedItems.length;
            return (avg / 1000).toFixed(1) + 's';
        },

        // Helper to format model name display
        formatModelName(name) {
             return name.replace(':latest', '').replace(':8b-instruct-q3_K_M', '');
        },

        get friendlyStatus() {
            const lastStatus = this.thinkingLines.length > 0 ? this.thinkingLines[this.thinkingLines.length - 1] : this.status;
            // Guard clause if status or lastStatus is undefined
            if (!lastStatus) return 'Ready';
            
            const lower = lastStatus.toLowerCase();
            
            if (lower.includes('connect')) return 'Handshaking with AI...';
            if (lower.includes('predict')) return 'Understanding context...';
            if (lower.includes('search')) return 'Consulting knowledge base...';
            if (lower.includes('think')) return 'Analyzing best approach...';
            if (lower.includes('generat')) return 'Drafting your response...';
            if (lower.includes('queue')) return 'Waiting for availability...';
            return 'Working on it...';
        },

        init() {
            // Ensure data types are correct (safety check for persist)
            if (!Array.isArray(this.history)) this.history = [];
            
            // Performance Clamping for M3 Air (16GB)
            // Limit context window leverage to prevent swap/lag
            if (this.kbCount > 5) this.kbCount = 5;
            if (this.maxTokens > 2048) this.maxTokens = 2048;
            if (typeof this.modelSettings !== 'object' || this.modelSettings === null) this.modelSettings = {};

            // Sanitize history state on load
            if (this.history && this.history.length > 0) {
                this.history.forEach(item => {
                    item.approving = false; // Reset stuck loading states
                });
            }
            // Load settings for initial model
            this.syncModelSettings();

            // Set dark mode by default (no toggle)
            document.documentElement.classList.add('dark');

            // Check if user is authenticated
            this.checkAuth().then(() => {
                 this.startTracking();
            });
            this.$watch('modelA', () => {
                if (!this.modelA) return;
                this.syncModelSettings();
            });
            this.$watch('temperature', (val) => this.saveModelSetting('temperature', val));
            this.$watch('maxTokens', (val) => this.saveModelSetting('maxTokens', val));
            this.$watch('kbCount', (val) => this.saveModelSetting('kbCount', val));
            this.$watch('topP', (val) => this.saveModelSetting('topP', val));
            this.$watch('frequencyPenalty', (val) => this.saveModelSetting('frequencyPenalty', val));
            this.$watch('presencePenalty', (val) => this.saveModelSetting('presencePenalty', val));

            // Template Mode disables Web Search
            this.$watch('templateMode', (val) => {
                if (val) this.enableWebSearch = false;
            });
            this.$watch('enableWebSearch', (val) => {
                if (val) this.templateMode = false;
            });

            // Scroll Lock for Modal
            this.$watch('showConfigModal', (val) => {
                document.body.style.overflow = val ? 'hidden' : '';
            });
            this.$watch('showGuide', (val) => {
                document.body.style.overflow = val ? 'hidden' : '';
            });
            
            // Initial fetch of models
            this.fetchOllamaModels();
            this.fetchKbStats(); // Fetch stats on load
        },

        // Authentication Methods
        async register() {
            try {
                const response = await fetch('/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.registerForm)
                });

                const data = await response.json();

                if (response.ok) {
                    this.currentUser = data.user;
                    this.signature = data.user.signature; // Auto-set signature from username
                    this.showRegister = false;
                    this.triggerToast('Account created successfully!', 'success');
                    this.registerForm = { name: '', username: '', email: '', password: '' };
                } else {
                    const errors = Object.values(data.errors || {}).flat().join(', ');
                    this.triggerToast(errors || 'Registration failed', 'error');
                }
            } catch (error) {
                this.triggerToast('Registration error: ' + error.message, 'error');
            }
        },

        async login() {
            try {
                const response = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.loginForm)
                });

                const data = await response.json();

                if (response.ok) {
                    this.currentUser = data.user;
                    this.signature = data.user.signature; // Sync signature
                    this.showLogin = false;
                    this.triggerToast('Welcome back, ' + data.user.name + '!', 'success');
                    this.loginForm = { login: '', password: '' };
                } else {
                    this.triggerToast(data.errors?.login?.[0] || 'Login failed', 'error');
                }
            } catch (error) {
                this.triggerToast('Login error: ' + error.message, 'error');
            }
        },

        async logout() {
            try {
                await fetch('/logout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                this.currentUser = null;
                this.triggerToast('Logged out successfully', 'info');
            } catch (error) {
                this.triggerToast('Logout error: ' + error.message, 'error');
            }
        },

        async checkAuth() {
            try {
                const response = await fetch('/user', {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.currentUser = data.user;
                    if (data.user.signature) {
                        this.signature = data.user.signature;
                    }
                }
            } catch (error) {
                // User not authenticated, that's fine
                this.currentUser = null;
            }
        },

        // Dynamic Model Descriptions
        modelDescriptions: {
            'llama3': 'A versatile and robust model by Meta, excellent for general instruction following and logic.',
            'gemma2': 'Google\'s lightweight yet powerful open model, great for creative writing and reasoning.',
            'mistral': 'Efficient and high-performance model, strong at coding and concise answers.',
            'qwen': 'Strong coding and math capabilities, very precise.',
            'default': 'A capable large language model suited for this task.'
        },

        getModelDescription(modelName) {
            const name = modelName.toLowerCase();
            if (name.includes('llama')) return this.modelDescriptions['llama3'];
            if (name.includes('gemma')) return this.modelDescriptions['gemma2'];
            if (name.includes('mistral')) return this.modelDescriptions['mistral'];
            if (name.includes('qwen')) return this.modelDescriptions['qwen'];
            return this.modelDescriptions['default'];
        },

        get modelStats() {
            // Group History by Model
            const stats = {};
            
            // 1. Calculate Approvals per Model
            this.history.forEach(item => {
                if (item.approved && item.modelA_name) {
                    const model = item.modelA_name; // Full name
                    if (!stats[model]) stats[model] = 0;
                    stats[model]++;
                }
            });

            // 2. Format as Array for Display
            // Sort by count desc
            return Object.entries(stats)
                .map(([name, count]) => ({ name, count }))
                .sort((a, b) => b.count - a.count);
        },

        // Helper to format model name display
        formatModelName(name) {
             return name.replace(':latest', '').replace(':8b-instruct-q3_K_M', '');
        },



        syncModelSettings() {
            const settings = this.modelSettings[this.modelA];
            if (settings) {
                this.temperature = settings.temperature ?? 0.5;
                this.maxTokens = settings.maxTokens ?? 600;
                this.kbCount = settings.kbCount ?? 3;
                this.topP = settings.topP ?? 0.9;
                this.frequencyPenalty = settings.frequencyPenalty ?? 0.0;
                this.presencePenalty = settings.presencePenalty ?? 0.0;
            } else {
                // Initialize with defaults if never set
                this.temperature = 0.5;
                this.maxTokens = 600;
                this.kbCount = 3;
            }
        },

        saveModelSetting(key, value) {
            if (!this.modelA) return;
            if (!this.modelSettings[this.modelA]) {
                this.modelSettings[this.modelA] = { 
                    temperature: 0.5, 
                    maxTokens: 600, 
                    kbCount: 3,
                    topP: 0.9,
                    frequencyPenalty: 0.0,
                    presencePenalty: 0.0
                };
            }
            this.modelSettings[this.modelA][key] = value;
            this.modelSettings = { ...this.modelSettings }; // Force persist trigger
            // Persistence is handled by $persist on modelSettings
        },

        applyPreset(type) {
            if (type === 'technical') {
                this.temperature = 0.2;
                this.maxTokens = 800;
                this.kbCount = 8;
            } else if (type === 'creative') {
                this.temperature = 0.8;
                this.maxTokens = 1200;
                this.kbCount = 3;
            } else if (type === 'tldr') {
                this.temperature = 0.4;
                this.maxTokens = 300;
                this.kbCount = 2;
            }
            this.triggerToast(`Applied ${type.toUpperCase()} Preset`);
        },

        getHealthColor() {
            if (this.maxTokens <= 800) return '#4ade80'; // Green
            if (this.maxTokens <= 1500) return '#facc15'; // Yellow
            return '#f87171'; // Red
        },

        triggerToast(msg, type = 'info') {
            this.toast = {
                active: true,
                msg: msg,
                type: type
            };
            setTimeout(() => {
                if (this.toast.msg === msg) {
                    this.toast.active = false;
                }
            }, 3000);
        },

        showModalAlert(msg) {
            this.successMessage = msg;
            this.showSuccessModal = true;
            setTimeout(() => {
                this.showSuccessModal = false;
            }, 2500); // Show for 2.5 seconds
        },

        decodeEntities(text) {
            if (!text) return '';
            const textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            return textarea.value;
        },

        toggleAllHistory() {
            this.allExpanded = !this.allExpanded;
            this.history.forEach(item => item.expanded = this.allExpanded);
        },

        async generateRephrase() { // Renamed from generateAction
            const textToProcess = this.inputText;
            if (!textToProcess) return;
            const startTime = performance.now();
            this.isGenerating = true;
            this.rephrasedContent = '';
            this.thinkingLines = []; // Reset thinking lines
            this.status = 'Connecting...';

            // Auto-Scaling Logic
            let finalTokens = this.maxTokens;
            if (this.autoTokens) {
                const words = textToProcess.trim().split(/\s+/).length;
                finalTokens = Math.max(200, Math.min(2000, words * 15)); // Approx 15 tokens per word of input for safety
            }

            // Function to handle a single stream
            const runStream = async (model, targetKey) => {
                const response = await fetch('/api/rephrase', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-Session-ID': this.sessionId,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        text: textToProcess,
                        signature: this.signature,
                        enable_web_search: this.enableWebSearch,
                        search_keywords: this.searchKeywords,
                        template_mode: this.templateMode,
                        category: this.currentCategory,
                        model: model,
                        temperature: this.temperature,
                        max_tokens: finalTokens,
                        kb_count: this.kbCount,
                        negative_prompt: this.negativePrompt
                    })
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Keep the last line in the buffer as it might be incomplete

                    for (const line of lines) {
                        if (!line.trim()) continue;
                        try {
                            const parsed = JSON.parse(line);
                            if (parsed.status) {
                                this.thinkingLines.push(parsed.status);
                                // Auto-scroll to bottom of logs
                                this.$nextTick(() => {
                                    const container = document.querySelector('.logs-container');
                                    if (container) container.scrollTop = container.scrollHeight;
                                });
                            }
                            if (parsed.data) this[targetKey] = parsed.data;
                        } catch (e) {
                            console.warn('JSON Parse error on line:', line, e);
                        }
                    }
                }
                // Process any remaining buffer content
                if (buffer.trim()) {
                    try {
                        const parsed = JSON.parse(buffer);
                        if (parsed.data) this[targetKey] = parsed.data;
                    } catch(e) {}
                }
            };

            try {
                await runStream(this.modelA, 'rephrasedContent');

                // Collapse previous items and add to history after generation
                this.history.forEach((h, i) => {
                    if (i !== 0) h.expanded = false;
                });

                this.history.unshift({
                    original: textToProcess,
                    rephrased: this.decodeEntities(this.rephrasedContent), // Store model A's output as primary
                    keywords: this.searchKeywords,
                    is_template: this.templateMode,
                    category: this.currentCategory,
                    approved: false,
                    expanded: true,
                    modelA_name: this.modelA || 'llama3:8b-instruct-q3_K_M',
                    isEditing: false, 
                    timestamp: new Date().toISOString(),
                    duration: performance.now() - startTime,
                    // Snapshot config for accurate data collection
                    config: {
                        temperature: this.temperature,
                        maxTokens: this.maxTokens,
                        topP: this.topP,
                        frequencyPenalty: this.frequencyPenalty,
                        presencePenalty: this.presencePenalty
                    }
                });
                // Keep history manageable
                if (this.history.length > 50) this.history.pop();
                this.triggerToast('Synthesis Complete', 'success');
                this.history = [...this.history]; // Force reactivity
                
                // Auto-clear input and exclusions on success
                this.inputText = '';
                this.negativePrompt = '';

                // Auto-scroll to results
                this.$nextTick(() => {
                    const results = document.getElementById('results-area');
                    if (results) results.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });

            } catch (error) {
                this.status = 'Error occurred.';
                this.triggerToast('Network Exception or API Error', 'error');
                console.error('Generation error:', error);
            } finally {
                this.isGenerating = false;
                this.status = 'Done.';
            }
        },

        async fetchKbStats() {
            try {
                const response = await fetch('/api/kb-stats');
                if (response.ok) {
                    this.kbStats = await response.json();
                }
            } catch (e) {
                console.error('Failed to fetch KB stats', e);
            }
        },

        async predictKeywords() {
            const text = this.inputText || this.manualOrig;
            if (!text) return;
            this.isPredictingKeywords = true;
            this.status = 'Predicting keywords...';
            try {
                const response = await fetch('/api/suggest-keywords', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ text })
                });
                const data = await response.json();
                if (this.inputText) this.searchKeywords = data.keywords;
                else this.manualKeywords = data.keywords;
                this.status = 'Keywords updated.';
                this.triggerToast('Keywords Predicted', 'success');
            } catch (e) {
                this.status = 'Keyword prediction failed.';
                this.triggerToast('Keyword Prediction Failed', 'error');
                console.error('Keyword prediction error:', e);
            } finally {
                this.isPredictingKeywords = false;
            }
        },

        copyText(text) {
            navigator.clipboard.writeText(text);
            this.triggerToast('Copied to Clipboard');
        },

        clearUnsaved() {
            if (!confirm('Are you sure you want to delete all UNSAVED responses from the archive? This cannot be undone.')) return;
            
            // Keep the latest response (index 0) and any approved items
            const latest = this.history[0];
            // Ensure we use the latest item for approval context if it matches content
            // (If user edits content, we might still want the original config, or maybe not. 
            //  For now, we assume implicit approval of the latest generation context).
            const saved = this.history.slice(1).filter(item => item.approved);
            
            // Reconstruct history
            this.history = latest ? [latest, ...saved] : [...saved];
            
            this.currentPage = 1;
            this.triggerToast('Unsaved items cleared');
        },

        regenerateFrom(text) {
            this.inputText = text;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => this.generateRephrase(), 400);
        },

        refreshArchive() {
            this.isRefreshingArchive = true;
            this.history = [...this.history];
            setTimeout(() => {
                this.isRefreshingArchive = false;
                this.triggerToast('Archive Refreshed');
            }, 800);
        },

        // This is the new approve method for the main generator output
        async approveEntry() {
            const content = this.rephrasedContent;
            if (!content) {
                this.triggerToast('❌ No content to approve!', 'error');
                return;
            }

            this.isGenerating = true; // Use isGenerating as a general processing indicator
            
            // Fix: Define latest to avoid ReferenceError
            // Use itemToView if we are in the modal (viewing/editing context), otherwise default to the latest history item
            const latest = (this.viewModal && this.itemToView) ? this.itemToView : this.history[0];
            
            try {
                const res = await fetch('/api/approve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.itemToView?.id || undefined, // Allow updating existing item via View Modal context
                        original_text: this.inputText,
                        rephrased_text: content,
                        keywords: this.searchKeywords,
                        is_template: this.templateMode,
                        category: this.currentCategory,
                        model_used: this.modelA,
                        // Performance Data
                        latency_ms: isNaN(latest?.duration) ? null : Math.round(latest.duration),
                        temperature: latest?.config?.temperature ?? this.temperature,
                        max_tokens: latest?.config?.maxTokens ?? this.maxTokens,
                        top_p: latest?.config?.topP ?? this.topP,
                        frequency_penalty: latest?.config?.frequencyPenalty ?? this.frequencyPenalty,
                        presence_penalty: latest?.config?.presencePenalty ?? this.presencePenalty
                    })
                });
                
                if (!res.ok) {
                    throw new Error(`HTTP status ${res.status}`);
                }

                const data = await res.json();
                if (data.status === 'success') {
                    // Update the history item if it exists, or add a new one
                    const existingEntry = this.history.find(item => item.original === this.inputText && item.rephrased === content);
                    if (existingEntry) {
                        existingEntry.approved = true;
                        existingEntry.id = data.id; // Save ID for future updates
                    } else {
                        this.history.unshift({
                            original: this.inputText,
                            rephrased: content,
                            keywords: this.searchKeywords,
                            is_template: this.templateMode,
                            category: this.currentCategory,
                            approved: true,
                            id: data.id,
                            expanded: true,
                            timestamp: new Date().toISOString()
                        });
                    }
                    this.history = [...this.history]; // Force reactivity
                    this.showModalAlert('Response saved to Knowledge Base');
                } else {
                    this.triggerToast('❌ Save Failed: ' + (data.error || 'Unknown'));
                }
            } catch (e) {
                console.error('Approval error:', e);
                this.triggerToast('❌ Network Error: ' + e.message);
            } finally {
                this.isGenerating = false;
            }
        },

        toggleEdit(idx) {
            this.history[idx].isEditing = !this.history[idx].isEditing;
            this.history = [...this.history];
        },

        // This is the original approveEntry, modified to handle history items
        async approveHistoryEntry(item, idx) {
            const content = item.rephrased;
            console.log('🔍 Approve history clicked! idx:', idx);
            
            if (!item.original || !content) {
                this.triggerToast('❌ Error: Missing Data');
                return;
            }

            // Set approving state on the actual history item
            this.history[idx].approving = true;
            this.history = [...this.history]; // Force reactivity
            
            try {
                // Use /api/ proxy
                const res = await fetch('/api/approve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        original_text: item.original,
                        rephrased_text: content,
                        keywords: item.keywords,
                        is_template: item.is_template,
                        category: item.category,
                        model_used: item.modelA_name,
                        id: item.id || undefined, // Support updating existing
                        // Performance Data from item snapshot
                        latency_ms: isNaN(item.duration) ? null : Math.round(item.duration),
                        temperature: item.config?.temperature,
                        max_tokens: item.config?.maxTokens,
                        top_p: item.config?.topP,
                        frequency_penalty: item.config?.frequencyPenalty,
                        presence_penalty: item.config?.presencePenalty
                    })
                });
                
                if (!res.ok) {
                    throw new Error(`HTTP status ${res.status}`);
                }

                const data = await res.json();
                if (data.status === 'success') {
                    this.history[idx].approved = true;
                    this.history[idx].id = data.id; // Save ID for updates
                    this.history = [...this.history]; // Force reactivity
                    // Use new Modal for approval
                    this.showModalAlert('Response saved to Knowledge Base');
                    // Also trigger toast for good measure/logs
                    // this.triggerToast('✅ Saved to Knowledge Base');
                } else {
                    this.triggerToast('❌ Save Failed: ' + (data.error || 'Unknown'));
                }
            } catch (e) {
                console.error('Approval error:', e);
                this.triggerToast('❌ Network Error: ' + e.message);
            } finally {
                this.history[idx].approving = false;
                this.history = [...this.history];
            }
        },

        async fetchAuditLogs() {
            try {
                const r = await fetch('/api/audit-logs');
                if (!r.ok) throw new Error(`HTTP status ${r.status}`);
                this.auditLogs = await r.json();
                this.triggerToast('Audit Logs Loaded', 'info');
            } catch (e) {
                this.triggerToast('❌ Failed to load audit logs: ' + e.message, 'error');
                console.error('Error fetching audit logs:', e);
            }
        },

        deleteHistoryEntry(idx) {
            this.history.splice(idx, 1);
            this.history = [...this.history];
            this.triggerToast('Item Removed', 'info');
        },

        addCategory() {
            if (!this.newCategory.trim()) return;
            if (!this.categories.includes(this.newCategory)) {
                this.categories.push(this.newCategory);
                this.currentCategory = this.newCategory;
            }
            this.newCategory = '';
            this.triggerToast('Category Added', 'success');
        },

        async importKB() {
            if (!this.kbFile) return;
            this.importing = true;
            const fd = new FormData();
            fd.append('file', this.kbFile);

            try {
                const res = await fetch('/api/upload_kb', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    this.triggerToast('Corpus Ingested', 'success');
                    this.kbFile = null;
                    document.getElementById('kbFileInput').value = '';
                } else {
                    this.triggerToast('❌ Import Failed: ' + (data.error || 'Unknown'), 'error');
                }
            } catch (e) {
                this.triggerToast('❌ Network Error during import', 'error');
                console.error('Import error:', e);
            } finally { this.importing = false; }
        },

        async addManual() {
            this.adding = true;
            try {
                const res = await fetch('/api/upload_kb', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        original_text: this.manualOrig,
                        rephrased_text: this.manualReph,
                        keywords: this.manualKeywords,
                        is_template: this.manualIsTemplate,
                        category: this.manualCategory // Added category
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.showModalAlert('Entry learned and added to Knowledge Base');
                    // Add to history for immediate feedback
                    this.history.unshift({
                        original: this.manualOrig,
                        rephrased: this.manualReph,
                        keywords: this.manualKeywords,
                        is_template: this.manualIsTemplate,
                        category: this.manualCategory,
                        approved: true, // Manually added entries are considered approved
                        expanded: true,
                        timestamp: new Date().toISOString()
                    });
                    // Keep history manageable
                    if (this.history.length > 50) this.history.pop();

                    this.manualOrig = ''; this.manualReph = '';
                    this.manualKeywords = ''; this.manualIsTemplate = false;
                    this.manualCategory = ''; // Clear manual category
                } else {
                    this.triggerToast('❌ Add Failed: ' + (data.error || 'Unknown'));
                }
            } catch (e) {
                this.triggerToast('❌ Network Error during manual add');
                console.error('Manual add error:', e);
            } finally { this.adding = false; }
        },

        async fetchOllamaModels() {
            this.isRefreshingModels = true;
            try {
                const res = await fetch('/api/models');
                const data = await res.json();
                if (data.models) {
                    this.ollamaModels = data.models;
                    // Auto-add any available models if roster is empty (fallback)
                    if (this.availableModels.length === 0 && this.ollamaModels.length > 0) {
                        this.ollamaModels.forEach(m => this.toggleModelImport(m));
                    }
                    if (this.ollamaModels.length === 0) {
                        this.triggerToast('No models found in Ollama', 'info');
                    }
                } else if (data.error) {
                     this.triggerToast('Ollama Error: ' + data.error, 'error');
                }
            } catch (e) {
                console.error('Failed to fetch models:', e);
                this.triggerToast('Connection Error: Check Console', 'error');
            } finally {
                this.isRefreshingModels = false;
            }
        },

        toggleModelImport(modelName) {
            const exists = this.availableModels.find(m => m.id === modelName);
            if (exists) {
                // Remove
                this.availableModels = this.availableModels.filter(m => m.id !== modelName);
                this.triggerToast(`Removed ${modelName}`);
                // unexpected state safety
                if(this.modelA === modelName && this.availableModels.length > 0) {
                    this.modelA = this.availableModels[0].id;
                }
            } else {
                // Add
                this.availableModels.push({ id: modelName, name: modelName });
                this.triggerToast(`Imported ${modelName}`);
            }
        },
        
        isModelImported(modelName) {
            return this.availableModels.some(m => m.id === modelName);
        }
    };
}

window.rephraserApp = rephraserApp;
