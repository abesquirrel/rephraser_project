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
        availableModels: [
            {id: 'llama3:8b-instruct-q3_K_M', name: 'Llama3'},
            {id: 'mistral:latest', name: 'Mistral'},
            {id: 'gemma2:9b', name: 'Gemma2 9B'}
        ],
        isGenerating: false,
        auditLogs: [],
        activeTab: 'generator', // generator, history, audit
        thinkingLines: [],
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
        
        // Theme
        theme: Alpine.$persist('dark').as('rephraser_theme'),
        showGuide: false,
        
        theme: Alpine.$persist('dark').as('rephraser_theme'),
        showGuide: false,
        
        toast: { active: false, msg: '', type: 'info' },
        showSuccessModal: false,
        successMessage: '',
        
        // KB
        
        // KB
        kbFile: null,
        importing: false,
        manualOrig: '',
        manualReph: '',
        manualKeywords: '',
        manualIsTemplate: false,
        manualCategory: '', // Added manualCategory
        adding: false,

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

            // apply theme on init
            this.applyTheme();

            // Watch for model changes
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
            
            // Logic: Template Mode disables Web Search
            this.$watch('templateMode', (val) => {
                if (val) this.enableWebSearch = false;
            });
            
            this.$watch('theme', () => this.applyTheme());
        },

        toggleTheme() {
            this.theme = this.theme === 'light' ? 'dark' : 'light';
        },

        applyTheme() {
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
                // You might need to update CSS variables here if using raw CSS vars
                // But generally class='dark' on html/body is enough if CSS supports it.
                // Assuming we are swapping root vars or usage of 'dark:' classes
                document.documentElement.style.setProperty('--bg-color', '#111827');
                document.documentElement.style.setProperty('--card-bg', 'rgba(31, 41, 55, 0.7)');
                document.documentElement.style.setProperty('--input-bg', 'rgba(17, 24, 39, 0.6)'); // Dark input bg
                document.documentElement.style.setProperty('--text-main', '#f9fafb');
                document.documentElement.style.setProperty('--text-dim', '#9ca3af');
                document.documentElement.style.setProperty('--card-border', 'rgba(255, 255, 255, 0.1)');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.style.setProperty('--bg-color', '#f9fafb');
                document.documentElement.style.setProperty('--card-bg', 'rgba(255, 255, 255, 0.75)');
                document.documentElement.style.setProperty('--input-bg', '#ffffff'); // White input bg
                document.documentElement.style.setProperty('--text-main', '#1f2937');
                document.documentElement.style.setProperty('--text-dim', '#6b7280');
                document.documentElement.style.setProperty('--card-border', 'rgba(0, 0, 0, 0.06)');
            }
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
            this.toast.msg = msg;
            this.toast.type = type;
            this.toast.active = true;
            setTimeout(() => this.toast.active = false, 3000);
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
                    headers: { 'Content-Type': 'application/json' },
                    headers: { 'Content-Type': 'application/json' },
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
                    timestamp: new Date().toISOString()
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

        async predictKeywords() {
            const text = this.inputText || this.manualOrig;
            if (!text) return;
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

        // This is the new approve method for the main generator output
        async approveEntry() {
            const content = this.rephrasedContent;
            if (!content) {
                this.triggerToast('❌ No content to approve!', 'error');
                return;
            }

            this.isGenerating = true; // Use isGenerating as a general processing indicator
            try {
                const res = await fetch('/api/approve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        original_text: this.inputText,
                        rephrased_text: content,
                        keywords: this.searchKeywords,
                        is_template: this.templateMode,
                        category: this.currentCategory,
                        model_used: this.modelA
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
                    } else {
                        this.history.unshift({
                            original: this.inputText,
                            rephrased: content,
                            keywords: this.searchKeywords,
                            is_template: this.templateMode,
                            category: this.currentCategory,
                            approved: true,
                            expanded: true,
                            timestamp: new Date().toISOString()
                        });
                    }
                    this.history = [...this.history]; // Force reactivity
                    this.triggerToast('✅ Saved to Knowledge Base');
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
                        model_used: item.modelA_name
                    })
                });
                
                if (!res.ok) {
                    throw new Error(`HTTP status ${res.status}`);
                }

                const data = await res.json();
                if (data.status === 'success') {
                    this.history[idx].approved = true;
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
                    this.triggerToast('Entry Learned', 'success');
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
        }
    };
}

window.rephraserApp = rephraserApp;
