function rephraserApp() {
    return {
        inputText: '',
        rephrasedContent: '',
        rephrasedContentB: '', // For A/B testing
        status: '',
        signature: Alpine.$persist('Paul').as('rephraser_sig'), // Kept persist for signature
        enableWebSearch: true,
        templateMode: false,
        searchKeywords: '',
        currentCategory: '', // Tier 2
        newCategory: '', 
        categories: ['General', 'Technical', 'Billing', 'Sales', 'Feedback'],
        modelA: 'llama3:8b-instruct-q3_K_M',
        modelB: 'mistral:latest',
        availableModels: [
            {id: 'llama3:8b-instruct-q3_K_M', name: 'Llama3'},
            {id: 'mistral:latest', name: 'Mistral'},
            {id: 'gemma2:9b', name: 'Gemma2 9B'}
        ],
        abMode: false,
        isGenerating: false,
        auditLogs: [],
        activeTab: 'generator', // generator, history, audit
        thinkingLines: [],
        history: Alpine.$persist([]).as('rephraser_log_v3'),
        allExpanded: false,
        
        // Tuning
        temperature: Alpine.$persist(0.5).as('rephraser_temp'),
        maxTokens: Alpine.$persist(600).as('rephraser_tokens'),
        kbCount: Alpine.$persist(3).as('rephraser_kb_count'),
        
        toast: { active: false, msg: '' },
        
        // KB
        kbFile: null,
        importing: false,
        manualOrig: '',
        manualReph: '',
        manualKeywords: '',
        manualIsTemplate: false,
        manualCategory: '', // Added manualCategory
        adding: false,

        init() {
            // Sanitize history state on load
            if (this.history && this.history.length > 0) {
                this.history.forEach(item => {
                    item.approving = false; // Reset stuck loading states
                });
            }
        },

        triggerToast(msg) {
            this.toast.msg = msg;
            this.toast.active = true;
            setTimeout(() => this.toast.active = false, 3000);
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
            if (!this.inputText) return;
            this.isGenerating = true;
            this.rephrasedContent = '';
            this.rephrasedContentB = '';
            this.thinkingLines = []; // Reset thinking lines
            this.status = 'Connecting...';

            // Function to handle a single stream
            const runStream = async (model, targetKey) => {
                const response = await fetch('/api/rephrase', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        text: this.inputText,
                        signature: this.signature,
                        enable_web_search: this.enableWebSearch,
                        search_keywords: this.searchKeywords,
                        template_mode: this.templateMode,
                        category: this.currentCategory,
                        model: model,
                        temperature: this.temperature,
                        max_tokens: this.maxTokens,
                        kb_count: this.kbCount
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
                if (this.abMode) {
                    this.status = 'Dual Generation Mode...';
                    await Promise.all([
                        runStream(this.modelA, 'rephrasedContent'),
                        runStream(this.modelB, 'rephrasedContentB')
                    ]);
                } else {
                    await runStream(this.modelA, 'rephrasedContent');
                }

                // Collapse previous items and add to history after generation
                this.history.forEach((h, i) => {
                    if (i !== 0) h.expanded = false;
                });

                this.history.unshift({
                    original: this.inputText,
                    rephrased: this.decodeEntities(this.rephrasedContent), // Store model A's output as primary
                    rephrasedB: this.rephrasedContentB ? this.decodeEntities(this.rephrasedContentB) : null, // Store model B's output
                    keywords: this.searchKeywords,
                    is_template: this.templateMode,
                    category: this.currentCategory,
                    approved: false,
                    expanded: true,
                    modelA_name: this.modelA || 'llama3:8b-instruct-q3_K_M',
                    modelB_name: this.modelB || 'mistral:latest',
                    isEditing: false, 
                    isEditingB: false,
                    timestamp: new Date().toISOString()
                });
                // Keep history manageable
                if (this.history.length > 50) this.history.pop();
                this.triggerToast('Synthesis Complete');
                this.history = [...this.history]; // Force reactivity

            } catch (error) {
                this.status = 'Error occurred.';
                this.triggerToast('Network Exception or API Error');
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
                this.triggerToast('Keywords Predicted');
            } catch (e) {
                this.status = 'Keyword prediction failed.';
                this.triggerToast('Keyword Prediction Failed');
                console.error('Keyword prediction error:', e);
            }
        },

        copyText(text) {
            navigator.clipboard.writeText(text);
            this.triggerToast('Copied to Clipboard');
        },

        regenerateFrom(text) {
            this.inputText = text;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => this.generateRephrase(), 400);
        },

        // This is the new approve method for the main generator output
        async approveEntry(isAlt = false) {
            const content = isAlt ? this.rephrasedContentB : this.rephrasedContent;
            if (!content) {
                this.triggerToast('❌ No content to approve!');
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
                        category: this.currentCategory
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

        toggleEdit(idx, isAlt = false) {
            const key = isAlt ? 'isEditingB' : 'isEditing';
            this.history[idx][key] = !this.history[idx][key];
            this.history = [...this.history];
        },

        // This is the original approveEntry, modified to handle history items
        async approveHistoryEntry(item, idx, isAlt = false) {
            const content = isAlt ? item.rephrasedB : item.rephrased;
            console.log('🔍 Approve history clicked! idx:', idx, 'isAlt:', isAlt);
            
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
                        model_used: isAlt ? item.modelB_name : item.modelA_name
                    })
                });
                
                if (!res.ok) {
                    throw new Error(`HTTP status ${res.status}`);
                }

                const data = await res.json();
                if (data.status === 'success') {
                    this.history[idx].approved = true;
                    this.history = [...this.history]; // Force reactivity
                    this.triggerToast('✅ Saved to Knowledge Base');
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
                this.triggerToast('Audit Logs Loaded');
            } catch (e) {
                this.triggerToast('❌ Failed to load audit logs: ' + e.message);
                console.error('Error fetching audit logs:', e);
            }
        },

        deleteHistoryEntry(idx) {
            this.history.splice(idx, 1);
            this.history = [...this.history];
            this.triggerToast('Item Removed');
        },

        addCategory() {
            if (!this.newCategory.trim()) return;
            if (!this.categories.includes(this.newCategory)) {
                this.categories.push(this.newCategory);
                this.currentCategory = this.newCategory;
            }
            this.newCategory = '';
            this.triggerToast('Category Added');
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
                    this.triggerToast('Corpus Ingested');
                    this.kbFile = null;
                    document.getElementById('kbFileInput').value = '';
                } else {
                    this.triggerToast('❌ Import Failed: ' + (data.error || 'Unknown'));
                }
            } catch (e) {
                this.triggerToast('❌ Network Error during import');
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
                    this.triggerToast('Entry Learned');
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
    }
}
