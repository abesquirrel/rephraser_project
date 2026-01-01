function rephraserApp() {
    return {
        inputText: '',
        signature: Alpine.$persist('Paul').as('rephraser_sig'),
        showThinking: true,
        templateMode: false,
        enableWebSearch: true,
        searchKeywords: '',
        processing: false,
        thinkingLines: [],
        history: Alpine.$persist([]).as('rephraser_log_v3'),
        allExpanded: false,
        
        toast: { active: false, msg: '' },
        
        // KB
        kbFile: null,
        importing: false,
        manualOrig: '',
        manualReph: '',
        manualKeywords: '',
        manualIsTemplate: false,
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

        toggleAllHistory() {
            this.allExpanded = !this.allExpanded;
            this.history.forEach(item => item.expanded = this.allExpanded);
        },

        async generateAction() {
            if (!this.inputText.trim()) return;

            this.processing = true;
            this.thinkingLines = [];
            
            const payload = {
                text: this.inputText,
                signature: this.signature,
                enable_web_search: this.enableWebSearch,
                search_keywords: this.searchKeywords,
                template_mode: this.templateMode
            };

            try {
                const response = await fetch('/api/rephrase', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    
                    // Keep the last line in the buffer as it might be incomplete
                    buffer = lines.pop(); 

                    for (const line of lines) {
                        if (!line.trim()) continue;
                        try {
                            const event = JSON.parse(line);
                            if (event.status) {
                                this.thinkingLines.push(event.status);
                                // Auto-scroll to bottom of logs
                                this.$nextTick(() => {
                                    const container = document.querySelector('.logs-container');
                                    if (container) container.scrollTop = container.scrollHeight;
                                });
                            } else if (event.data) {
                                // Collapse previous items
                                this.history.forEach((h, i) => {
                                    if (i !== 0) h.expanded = false;
                                });

                                this.history.unshift({
                                    original: this.inputText,
                                    rephrased: event.data,
                                    keywords: this.searchKeywords,
                                    is_template: this.templateMode,
                                    approved: false,
                                    expanded: true,
                                    timestamp: new Date().toISOString()
                                });
                                // Keep history manageable
                                if (this.history.length > 50) this.history.pop();
                                
                                this.processing = false;
                                this.triggerToast('Synthesis Complete');
                            } else if (event.error) {
                                this.triggerToast('Backend Error: ' + event.error);
                                this.processing = false;
                            }
                        } catch (e) {
                            console.warn('JSON Parse error on line:', line, e);
                        }
                    }
                }
                
                // Process any remaining buffer content
                if (buffer.trim()) {
                     try {
                        const event = JSON.parse(buffer);
                        if (event.data) { // Handle case where last line was complete but no newline
                             // Same logic as above, but just for data as status usually comes early
                             this.history.unshift({
                                original: this.inputText,
                                rephrased: event.data,
                                keywords: this.searchKeywords,
                                is_template: this.templateMode,
                                approved: false,
                                expanded: true,
                                timestamp: new Date().toISOString()
                            });
                            this.processing = false;
                        }
                    } catch(e) {}
                }
            } catch (e) {
                this.triggerToast('Network Exception');
                this.processing = false;
            }
        },

        copyText(text) {
            navigator.clipboard.writeText(text);
            this.triggerToast('Copied to Clipboard');
        },

        regenerateFrom(text) {
            this.inputText = text;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => this.generateAction(), 400);
        },

        async approveEntry(item, idx) {
            console.log('🔍 Approve button clicked! idx:', idx);
            console.log('Payload:', { 
                original_text: item.original, 
                rephrased_text: item.rephrased,
                keywords: item.keywords,
                is_template: item.is_template
            });
            
            if (!item.original || !item.rephrased) {
                console.error('Missing data for approval!');
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
                        rephrased_text: item.rephrased,
                        keywords: item.keywords,
                        is_template: item.is_template
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
                }
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
                        is_template: this.manualIsTemplate
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.triggerToast('Entry Learned');
                    this.manualOrig = ''; this.manualReph = '';
                    this.manualKeywords = ''; this.manualIsTemplate = false;
                }
            } finally { this.adding = false; }
        }
    }
}
