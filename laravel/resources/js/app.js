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
        activeHelpTab: 'guide', // guide, stats
        
        toast: { active: false, msg: '', type: 'info' },
        showSuccessModal: false,
        showConfigModal: false,
        successMessage: '',
        
        // KB
        kbFile: null,
        importing: false,
        isOptimizing: false,
        
        // Config Tab
        configTab: 'general',
        promptRoles: [],
        currRole: null, 
        selectedRoleName: '', // Selected role for generation

        
        // Analytics
        sessionId: Alpine.$persist(null).as('rephraser_session_id'),

        async logAction(actionType, details = {}) {
            if (!this.sessionId) return;
            try {
                await fetch('/api/log-action', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        action_type: actionType,
                        action_details: details
                    })
                });
            } catch (e) {
                console.error("Failed to log action:", e);
            }
        },

        async regenerateResponse(item) {
            // 1. Log negative feedback
            await this.logAction('regenerate_negative', {
                original_text: item.original,
                previous_response: item.rephrased,
                model: item.modelA
            });

            // 2. Set input and regenerate
            this.inputText = item.original;
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Trigger generation
            this.generateRephrase();
        },

        async startTracking() {
             if (!this.sessionId || this.sessionId === 'null') {
                 if (window.crypto && crypto.randomUUID) {
                     this.sessionId = crypto.randomUUID();
                 } else {
                     this.sessionId = 'sess_' + Math.random().toString(36).substr(2, 9);
                 }
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
             if (!name) return 'Unknown Model';
             return name.replace(':latest', '').replace(':8b-instruct-q3_K_M', '').replace('-instruct-q3_K_M', '');
        },

        stripMarkdown(text) {
            if (!text) return '';
            return text
                .replace(/(\*\*|__)/g, '')
                .replace(/(\*|_)/g, '')
                .replace(/^#+\s*/gm, '')
                .replace(/```.*?```/gs, '')
                .replace(/`/g, '')
                .trim();
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
            
            // Initial fetch of models & Roles
            this.fetchOllamaModels();
            // 6. Migrate History for missing fields
            this.history.forEach(h => {
                if (typeof h.approved === 'undefined') h.approved = false;
                if (typeof h.id === 'undefined') h.id = null;
            });
            this.fetchKbStats(); 
            this.fetchRoles();
        },
        
        // --- Role Management Methods ---
        async fetchRoles() {
            try {
                const res = await fetch('/api/roles');
                if(res.ok) {
                    this.promptRoles = await res.json();
                    // Auto-select the default role
                    const def = this.promptRoles.find(r => r.is_default);
                    if(def && !this.selectedRoleName) {
                        this.selectedRoleName = def.name;
                    }
                }
            } catch(e) {
                console.error("Failed to fetch roles", e);
            }
        },

        createNewRole() {
            this.currRole = {
                id: null,
                name: 'New Custom Role',
                identity: 'You are {signature}. Analytical Assistant.',
                protocol: '### PROTOCOL\n1. Analyze request.\n2. Provide clear response.',
                format: '{signature} reports:\n\n(Content)',
                is_default: false
            };
        },

        selectRole(role) {
            // Deep copy to allow editing without immediate state reflection if cancelled (though we are binding directly now for simplicity)
            this.currRole = { ...role };
        },

        async saveRole() {
            if(!this.currRole) return;
            try {
                const res = await fetch('/api/roles', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(this.currRole)
                });
                const data = await res.json();
                if(data.status === 'success') {
                    this.triggerToast('Role Saved Successfully', 'success');
                    await this.fetchRoles(); // Refresh list
                    // Re-select to get updated ID if new
                    this.currRole = { ...data.role };
                } else {
                    this.triggerToast('Error saving role: ' + (data.message || 'Unknown'), 'error');
                }
            } catch(e) {
                this.triggerToast('Network Error saving role', 'error');
            }
        },

        async deleteRole(id) {
            if(!confirm('Are you sure you want to delete this role?')) return;
            try {
                const res = await fetch(`/api/roles/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await res.json();
                if(data.status === 'success') {
                    this.triggerToast('Role Deleted', 'info');
                    this.currRole = null;
                    await this.fetchRoles();
                } else {
                    this.triggerToast('Error: ' + data.message, 'error');
                }
            } catch(e) {
                this.triggerToast('Network Error deleting role', 'error');
            }
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



        getModelCapabilities(modelName) {
            if (!modelName) return { maxTokens: 600, kbCount: 2, temperature: 0.4 };
            const name = modelName.toLowerCase();
            
            // Tier 1: Powerful (Usually 7B+ parameters)
            if (name.includes('llama3') || name.includes('gemma:9b') || name.includes('gemma2:9b') || 
                name.includes('mistral') || name.includes('qwen:7b') || name.includes('mixtral') || name.includes('command-r')) {
                return { maxTokens: 2048, kbCount: 5, temperature: 0.7 };
            }
            
            // Tier 2: Lightweight
            if (name.includes('phi3') || name.includes('gemma:2b') || name.includes('gemma2:2b') || 
                name.includes('qwen:1.5b') || name.includes('tinyllama') || name.includes('phi')) {
                return { maxTokens: 1024, kbCount: 3, temperature: 0.5 };
            }
            
            // Tier 3: Default/Safe fallback
            return { maxTokens: 600, kbCount: 2, temperature: 0.4 };
        },

        syncModelSettings() {
            if (!this.modelA) return;
            
            const caps = this.getModelCapabilities(this.modelA);
            const settings = this.modelSettings[this.modelA] || {};
            
            // Apply settings, but clamp maxTokens and kbCount to model capabilities
            this.temperature = settings.temperature ?? caps.temperature;
            this.maxTokens = Math.min(settings.maxTokens ?? caps.maxTokens, caps.maxTokens);
            this.kbCount = Math.min(settings.kbCount ?? caps.kbCount, caps.kbCount);
            this.topP = settings.topP ?? 0.9;
            this.frequencyPenalty = settings.frequencyPenalty ?? 0.0;
            this.presencePenalty = settings.presencePenalty ?? 0.0;
            
            // Update saved state if clamping occurred or if it was empty
            this.saveModelSetting('maxTokens', this.maxTokens);
            this.saveModelSetting('kbCount', this.kbCount);
        },

        saveModelSetting(key, value) {
            if (!this.modelA) return;
            if (!this.modelSettings[this.modelA]) {
                const caps = this.getModelCapabilities(this.modelA);
                this.modelSettings[this.modelA] = { 
                    temperature: caps.temperature, 
                    maxTokens: caps.maxTokens, 
                    kbCount: caps.kbCount,
                    topP: 0.9,
                    frequencyPenalty: 0.0,
                    presencePenalty: 0.0
                };
            }
            this.modelSettings[this.modelA][key] = value;
            this.modelSettings = { ...this.modelSettings }; // Force persist trigger
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
                        negative_prompt: this.negativePrompt,
                        role: this.selectedRoleName // Pass selected role
                    })
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                // Link stream to history item if provided
                const historyItem = this.history[0];

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Keep the last line in the buffer as it might be incomplete

                    for (const line of lines) {
                        const trimmedLine = line.trim();
                        if (!trimmedLine || trimmedLine.startsWith('<') || trimmedLine.startsWith('<!')) continue;
                        
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
                            if (parsed.token) {
                                // Append token in real-time
                                this.rephrasedContent += parsed.token;
                                if (historyItem) {
                                    historyItem.rephrased = this.rephrasedContent;
                                }
                            }
                            if (parsed.data) {
                                // Final full data (ensure sync and strip markdown)
                                this.rephrasedContent = this.stripMarkdown(parsed.data);
                                if (historyItem) {
                                    historyItem.rephrased = this.rephrasedContent;
                                }
                            }
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

            // Collapse previous items
            this.history.forEach((h) => h.expanded = false);

            // Add stub to history immediately
            this.history.unshift({
                original: textToProcess,
                rephrased: '...', // Visual indicator that it's starting
                keywords: this.searchKeywords,
                is_template: this.templateMode,
                category: this.currentCategory,
                approved: false,
                expanded: true,
                modelA_name: this.formatModelName(this.modelA),
                isEditing: false, 
                timestamp: new Date().toISOString(),
                duration: 0,
                config: {
                    temperature: this.temperature,
                    maxTokens: this.maxTokens,
                }
            });

            try {
                await runStream(this.modelA, 'rephrasedContent');

                // Finalize history entry
                if (this.history[0]) {
                    this.history[0].duration = performance.now() - startTime;
                    this.history[0].rephrased = this.rephrasedContent;
                }

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
            this.status = 'Refreshing stats...';
            try {
                const response = await fetch('/api/kb-stats');
                if (response.ok) {
                    this.kbStats = await response.json();
                    this.triggerToast('Knowledge Base Stats Updated', 'success');
                }
            } catch (e) {
                console.error('Failed to fetch KB stats', e);
            } finally {
                this.status = 'Done';
            }
        },

        async optimizeIndex() {
            if(!confirm('This will update the DB cache for all entries and rebuild the in-memory index. It acts as a "hard refresh". Continue?')) return;
            this.isOptimizing = true;
            this.status = 'Optimizing Index...';
            try {
                const res = await fetch('/api/trigger-rebuild', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if(res.ok) {
                    this.showModalAlert('Index Optimized Successfully. All entries have been refreshed.');
                } else {
                    this.triggerToast('Optimization Failed', 'error');
                }
            } catch(e) {
                console.error('Optimization error:', e);
                this.triggerToast('Network error during optimization', 'error');
            } finally {
                this.isOptimizing = false;
                this.status = 'Done';
            }
        },

        // --- Prune Feature State ---
        showPruneModal: false,
        pruneCandidates: [],
        pruneThreshold: 5,
        pruneDays: 30, // Default to 30 days old to be safe
        sortPruneBy: 'hits',
        sortPruneOrder: 'asc',
        selectedPruneIds: [],
        
        sortPrune(key) {
            if (this.sortPruneBy === key) {
                this.sortPruneOrder = this.sortPruneOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortPruneBy = key;
                this.sortPruneOrder = 'asc';
            }
            
            this.pruneCandidates.sort((a, b) => {
                let valA, valB;
                if (key === 'age') {
                    valA = new Date() - new Date(a.created_at);
                    valB = new Date() - new Date(b.created_at);
                } else {
                    valA = a[key];
                    valB = b[key];
                }
                
                if (this.sortPruneOrder === 'asc') return valA - valB;
                return valB - valA;
            });
        },
        
        async openPruneModal() {
            this.showGuide = false; // Close Guide modal first
            this.showPruneModal = true;
            await this.fetchPruneCandidates();
        },

        async fetchPruneCandidates() {
            this.status = 'Scanning usage data...';
            try {
                // Query string
                const params = new URLSearchParams({
                    threshold_hits: this.pruneThreshold,
                    days_old: this.pruneDays
                });
                
                const res = await fetch(`/api/prune-candidates?${params}`);
                if(res.ok) {
                    this.pruneCandidates = await res.json();
                    this.sortPrune(this.sortPruneBy); // Apply current sort
                    // Auto-select all by default? No, let user select. Or maybe specific ones.
                    // Let's just default to empty selection for safety.
                    this.selectedPruneIds = [];
                } else {
                    this.triggerToast('Failed to scan candidates', 'error');
                }
            } catch(e) {
                console.error(e);
                this.triggerToast('Network Error', 'error');
            } finally {
                this.status = 'Done';
            }
        },

        togglePruneSelection(id) {
            if(this.selectedPruneIds.includes(id)) {
                this.selectedPruneIds = this.selectedPruneIds.filter(i => i !== id);
            } else {
                this.selectedPruneIds.push(id);
            }
        },
        
        toggleAllPrune() {
            if(this.selectedPruneIds.length === this.pruneCandidates.length) {
                this.selectedPruneIds = [];
            } else {
                this.selectedPruneIds = this.pruneCandidates.map(c => c.id);
            }
        },

        async keepEntry(id) {
            try {
                const res = await fetch('/api/keep-entry', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: JSON.stringify({ id })
                });
                if(res.ok) {
                    this.triggerToast('Entry marked as Safe', 'success');
                    // Remove from list locally
                    this.pruneCandidates = this.pruneCandidates.filter(c => c.id !== id);
                    this.selectedPruneIds = this.selectedPruneIds.filter(i => i !== id);
                }
            } catch(e) {
                this.triggerToast('Failed to update entry', 'error');
            }
        },

        async confirmPrune() {
            if(this.selectedPruneIds.length === 0) {
                 this.triggerToast('No entries selected for deletion', 'warning');
                 return;
            }
            if(!confirm(`Are you sure you want to PERMANENTLY DELETE ${this.selectedPruneIds.length} entries?`)) return;

             this.status = 'Pruning...';
             try {
                 const res = await fetch('/api/cleanup-kb', { 
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: JSON.stringify({ ids: this.selectedPruneIds })
                 });
                const data = await res.json();
                if(res.ok) {
                    this.showModalAlert(`Deleted ${data.deleted || this.selectedPruneIds.length} entries successfully.`);
                    this.pruneCandidates = this.pruneCandidates.filter(c => !this.selectedPruneIds.includes(c.id));
                    this.selectedPruneIds = [];
                    
                    // Helper: if empty, maybe close modal?
                    if (this.pruneCandidates.length === 0) {
                        this.showPruneModal = false;
                    }
                    
                    this.fetchKbStats(); 
                } else {
                    this.triggerToast('Cleanup Failed: ' + (data.error || 'Unknown'), 'error');
                }
             } catch(e) {
                 this.triggerToast('Network Error', 'error');
             } finally {
                 this.status = 'Done';
             }
        },

        // --- KB Edit Feature ---
        showEditKbModal: false,
        editionKbEntry: {
            id: null,
            original_text: '',
            rephrased_text: '',
            keywords: '',
            category: '',
            role: '',
            is_template: false,
            model_used: ''
        },

        openEditKbModal(entry) {
            this.editionKbEntry = {
                id: entry.id,
                original_text: entry.original_text || '',
                rephrased_text: entry.rephrased_text || '',
                keywords: entry.keywords || '',
                category: entry.category || '',
                role: entry.role || 'Tech Support',
                is_template: !!entry.is_template,
                model_used: entry.model_used || ''
            };
            this.showEditKbModal = true;
        },

        async saveKbEdit() {
            this.status = 'Saving changes...';
            try {
                const res = await fetch('/api/approve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.editionKbEntry)
                });
                
                if (res.ok) {
                    this.showModalAlert('Entry Updated Successfully');
                    this.showEditKbModal = false;
                    if (this.showPruneModal) await this.fetchPruneCandidates();
                    await this.fetchKbStats();
                }
 else {
                    const data = await res.json();
                    this.triggerToast('Failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (e) {
                console.error(e);
                this.triggerToast('Error saving changes', 'error');
            } finally {
                this.status = '';
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
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.triggerToast('Copied to Clipboard');
                }).catch(() => {
                    this.triggerToast('Failed to copy', 'error');
                });
            } else {
                // Fallback for non-secure contexts (HTTP)
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";  // Avoid scrolling to bottom
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    this.triggerToast('Copied to Clipboard');
                } catch (err) {
                    console.error('Fallback: Oops, unable to copy', err);
                    this.triggerToast('Failed to copy', 'error');
                }
                document.body.removeChild(textArea);
            }
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
                        role: this.selectedRoleName || 'Tech Support', // Add role
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
                        id: item.id || undefined,
                        original_text: item.original,
                        rephrased_text: item.rephrased,
                        keywords: item.keywords || '',
                        is_template: item.is_template || false,
                        category: item.category || '',
                        role: this.selectedRoleName || 'Tech Support', // Add role
                        model_used: item.modelA_name || '', // Changed from item.model to item.modelA_name to match original
                        latency_ms: item.duration ? Math.round(item.duration) : null,
                        temperature: item.config?.temperature ?? null,
                        max_tokens: item.config?.maxTokens ?? null,
                        top_p: item.config?.topP ?? null,
                        frequency_penalty: item.config?.frequencyPenalty ?? null,
                        presence_penalty: item.config?.presencePenalty ?? null
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
                    this.showModalAlert('Corpus Ingested Successfully');
                    this.kbFile = null;
                    document.getElementById('bulkImport').value = '';
                }
 else {
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
                    } else {
                        this.triggerToast('Model Roster Synchronized', 'success');
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
