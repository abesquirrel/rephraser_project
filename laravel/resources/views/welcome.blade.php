<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masha: AI Rephraser</title>
    @vite(['resources/css/app.css'])
    <script src="https://unpkg.com/@alpinejs/persist@3.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/alpinejs@3.x/dist/cdn.min.js" defer></script>
    <script>
        // Initialize Alpine.js data before DOM loads
        document.addEventListener('alpine:init', () => {
            Alpine.data('rephraserApp', () => ({
                // State
                inputText: '',
                history: Alpine.$persist([]).as('rephraser_history'),
                currentTheme: Alpine.$persist('light').as('rephraser_theme'),
                modelA: Alpine.$persist('gemini-2.5-flash').as('rephraser_model'),
                selectedRoleName: '',
                
                // Settings
                signature: Alpine.$persist('Paul').as('rephraser_sig'),
                temperature: Alpine.$persist(0.5).as('rephraser_temp'),
                maxTokens: Alpine.$persist(600).as('rephraser_tokens'),
                enableWebSearch: Alpine.$persist(true).as('rephraser_web_search'),
                templateMode: Alpine.$persist(false).as('rephraser_template_mode'),
                
                // UI State
                isGenerating: false,
                status: '',
                showConfigModal: false,
                toast: { active: false, msg: '', type: 'info' },
                
                // Data
                availableModels: Alpine.$persist([
                    {id: 'gemini-2.5-flash', name: 'Gemini 2.5 Flash'},
                    {id: 'gemini-2.5-flash-lite', name: 'Gemini Lite'},
                    {id: 'open-mistral-nemo', name: 'Mistral Nemo'},
                    {id: 'mistral-small-latest', name: 'Mistral Small'},
                    {id: 'mistral-tiny', name: 'Mistral Tiny'},
                    {id: 'nemo', name: 'Nemo'},
                    {id: 'mini', name: 'Mini'}
                ]).as('rephraser_models'),
                promptRoles: [],
                kbStats: {},
                
                // Initialize
                async init() {
                    await this.fetchRoles();
                    await this.fetchKbStats();
                    document.documentElement.classList.toggle('dark', this.currentTheme === 'dark');
                },
                
                toggleTheme() {
                    this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
                    document.documentElement.classList.toggle('dark', this.currentTheme === 'dark');
                },
                
                formatOutput(text) {
                    if (!text) return text;
                    return text.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>').replace(/^/, '<p>').replace(/$/, '</p>');
                },
                
                showToast(msg, type = 'info') {
                    this.toast = { active: true, msg, type };
                    setTimeout(() => this.toast.active = false, 3000);
                },
                
                async copyToClipboard(text) {
                    try {
                        await navigator.clipboard.writeText(text);
                        this.showToast('Copied!', 'success');
                    } catch (e) {
                        this.showToast('Failed to copy', 'error');
                    }
                },
                
                async fetchRoles() {
                    try {
                        const res = await fetch('/api/roles');
                        this.promptRoles = await res.json();
                        if (this.promptRoles.length > 0 && !this.selectedRoleName) {
                            const defaultRole = this.promptRoles.find(r => r.is_default);
                            this.selectedRoleName = defaultRole ? defaultRole.name : this.promptRoles[0].name;
                        }
                    } catch (e) {
                        console.error('Failed to fetch roles:', e);
                    }
                },
                
                async fetchKbStats() {
                    try {
                        const res = await fetch('/api/kb-stats');
                        this.kbStats = await res.json();
                    } catch (e) {
                        console.error('Failed to fetch KB stats:', e);
                    }
                },
                
                async generateRephrase() {
                    if (!this.inputText.trim()) {
                        this.showToast('Please enter text', 'error');
                        return;
                    }
                    
                    this.isGenerating = true;
                    this.status = 'Starting...';
                    
                    try {
                        const res = await fetch('/api/rephrase', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                text: this.inputText,
                                model: this.modelA,
                                signature: this.signature,
                                temperature: this.temperature,
                                max_tokens: this.maxTokens,
                                enable_web_search: this.enableWebSearch,
                                template_mode: this.templateMode,
                                role: this.selectedRoleName || null
                            })
                        });
                        
                        if (!res.ok) throw new Error('Generation failed');
                        
                        const data = await res.json();
                        
                        this.history.unshift({
                            original: this.inputText,
                            rephrased: data.data || 'No response',
                            model: this.modelA,
                            timestamp: new Date().toISOString()
                        });
                        
                        if (this.history.length > 20) this.history = this.history.slice(0, 20);
                        
                        this.showToast('Generated!', 'success');
                        
                    } catch (e) {
                        console.error('Generation error:', e);
                        this.showToast('Failed: ' + e.message, 'error');
                    } finally {
                        this.isGenerating = false;
                    }
                },
                
                clearHistory() {
                    if (confirm('Clear all history?')) {
                        this.history = [];
                        this.showToast('History cleared', 'success');
                    }
                },
                
                async approveEntry(item) {
                    try {
                        await fetch('/api/approve', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                original: item.original,
                                rephrased: item.rephrased,
                                model: item.model,
                                role: this.selectedRoleName
                            })
                        });
                        this.showToast('Saved to KB!', 'success');
                    } catch (e) {
                        this.showToast('Failed to save', 'error');
                    }
                }
            }));
        });
    </script>
</head>

<body x-data="rephraserApp" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-6xl mx-auto px-4 py-8" :class="currentTheme">
        
        <!-- Header -->
        <header class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Masha</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">AI Support Rephraser</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg x-show="currentTheme === 'dark'" class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 00-1 1v1z"/>
                    </svg>
                    <svg x-show="currentTheme !== 'dark'" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>
                <button @click="showConfigModal = true" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c.94-1.543-.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Input Section -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <textarea x-model="inputText" 
                              placeholder="Paste your support notes here..."
                              class="w-full min-h-[250px] p-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent outline-none transition-all resize-y font-mono text-sm"
                              spellcheck="false"></textarea>
                    
                    <!-- Quick Controls -->
                    <div class="flex flex-wrap gap-3 mt-6">
                        <!-- Model Selector -->
                        <div class="relative">
                            <select x-model="modelA" class="pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm w-60 appearance-none">
                                <option value="">Select Model...</option>
                                <template x-for="m in availableModels" :key="m.id">
                                    <option :value="m.id" x-text="m.name"></option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Role Selector -->
                        <div class="relative">
                            <select x-model="selectedRoleName" class="pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm w-48 appearance-none">
                                <option value="">Select Role...</option>
                                <template x-for="role in promptRoles" :key="role.id">
                                    <option :value="role.name" x-text="role.name"></option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Toggles -->
                        <label class="flex items-center gap-2 cursor-pointer px-4 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 text-sm">
                            <input type="checkbox" x-model="templateMode" class="sr-only">
                            <div class="relative w-8 h-4 bg-gray-300 dark:bg-gray-500 rounded-full" :class="templateMode ? 'bg-sky-500' : ''">
                                <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full transition-transform shadow" :class="templateMode ? 'translate-x-4' : ''"></div>
                            </div>
                            <span :class="templateMode ? 'text-sky-600' : 'text-gray-600'">Template</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer px-4 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 text-sm">
                            <input type="checkbox" x-model="enableWebSearch" class="sr-only">
                            <div class="relative w-8 h-4 bg-gray-300 dark:bg-gray-500 rounded-full" :class="enableWebSearch ? 'bg-indigo-500' : ''">
                                <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full transition-transform shadow" :class="enableWebSearch ? 'translate-x-4' : ''"></div>
                            </div>
                            <span :class="enableWebSearch ? 'text-indigo-600' : 'text-gray-600'">Research</span>
                        </label>
                        
                        <button @click="generateRephrase()" :disabled="isGenerating || !inputText.trim()"
                                class="px-5 py-2 bg-sky-500 hover:bg-sky-600 disabled:bg-sky-300 text-white font-semibold rounded-lg transition-all shadow-lg shadow-sky-500/30 disabled:shadow-none disabled:cursor-not-allowed ml-auto">
                            <template x-if="!isGenerating">Generate</template>
                            <template x-if="isGenerating">Generating...</template>
                        </button>
                    </div>
                </div>

                <!-- Output Section -->
                <template x-if="history.length > 0">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="font-semibold text-gray-900 dark:text-white">Result</h2>
                            <div class="flex gap-3 text-sm">
                                <button @click="copyToClipboard(history[0].rephrased)" class="text-gray-500 hover:text-sky-500 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Copy
                                </button>
                                <button @click="approveEntry(history[0])" class="text-gray-500 hover:text-emerald-500 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Save
                                </button>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Original</p>
                                <pre class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap font-mono" x-text="history[0].original"></pre>
                            </div>
                            <div class="bg-sky-50/50 dark:bg-sky-900/20 rounded-lg p-4 border border-sky-200/50 dark:border-sky-700/50">
                                <p class="text-xs font-semibold text-sky-600 uppercase tracking-wider mb-2">Rephrased</p>
                                <div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200" x-html="formatOutput(history[0].rephrased)"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Sidebar -->
            <aside class="space-y-4">
                <!-- About Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Transform raw support notes into professional responses using AI.</p>
                    <div class="flex gap-2">
                        <span class="px-2 py-1 bg-sky-100 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 rounded-full text-xs">AI</span>
                        <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full text-xs">PII Safe</span>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Stats</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <p class="text-xl font-bold text-sky-500" x-text="history.length">0</p>
                            <p class="text-xs text-gray-500">Today</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xl font-bold text-indigo-500" x-text="kbStats.total || '0'">0</p>
                            <p class="text-xs text-gray-500">KB Items</p>
                        </div>
                    </div>
                </div>

                <!-- Recent History -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Recent</p>
                        <button @click="clearHistory()" class="text-xs text-red-500 hover:text-red-600">Clear</button>
                    </div>
                    <div class="space-y-2 max-h-[250px] overflow-y-auto">
                        <template x-for="item in history.slice(0, 5)" :key="item.timestamp">
                            <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700/80 transition-colors cursor-pointer"
                                 @click="inputText = item.original">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5" x-text="new Date(item.timestamp).toLocaleTimeString()"></p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 truncate" x-text="item.original.substring(0, 80)"></p>
                            </div>
                        </template>
                        <template x-if="history.length === 0">
                            <p class="text-xs text-gray-500 text-center py-3">No history yet</p>
                        </template>
                    </div>
                </div>
            </aside>
        </main>

        <!-- Loading Overlay -->
        <div x-show="isGenerating" x-cloak class="fixed inset-0 z-50 bg-black/20 backdrop-blur-sm flex items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-2xl max-w-sm mx-4">
                <div class="flex items-center gap-4">
                    <div class="relative w-10 h-10">
                        <div class="absolute inset-0 bg-sky-500/20 rounded-full animate-ping"></div>
                        <svg class="w-6 h-6 text-sky-500 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Generating...</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="status"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div x-show="toast.active" x-cloak x-transition class="fixed bottom-6 right-6 z-50">
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg" 
                 :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 
                        toast.type === 'error' ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' :
                        'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="toast.type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    <path x-show="toast.type !== 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm" x-text="toast.msg"></span>
            </div>
        </div>

        <!-- Settings Modal -->
        <div x-show="showConfigModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Settings</h2>
                    <button @click="showConfigModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Your Name</label>
                        <input x-model="signature" placeholder="Paul" 
                               class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Temperature</label>
                            <input type="range" x-model="temperature" min="0" max="1" step="0.1" class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer">
                            <p class="text-center text-xs font-mono mt-1" x-text="temperature.toFixed(1)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Max Tokens</label>
                            <input type="range" x-model="maxTokens" min="50" max="2000" step="50" class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer">
                            <p class="text-center text-xs font-mono mt-1" x-text="maxTokens"></p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                        <button @click="clearHistory()" class="text-sm text-red-500 hover:text-red-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Clear History
                        </button>
                        <button @click="showConfigModal = false" class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-lg text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('rephraserApp', () => ({
                // State
                inputText: '',
                history: Alpine.$persist([]).as('rephraser_history'),
                currentTheme: Alpine.$persist('light').as('rephraser_theme'),
                modelA: Alpine.$persist('gemini-2.5-flash').as('rephraser_model'),
                selectedRoleName: '',
                
                // Settings
                signature: Alpine.$persist('Paul').as('rephraser_sig'),
                temperature: Alpine.$persist(0.5).as('rephraser_temp'),
                maxTokens: Alpine.$persist(600).as('rephraser_tokens'),
                enableWebSearch: Alpine.$persist(true).as('rephraser_web_search'),
                templateMode: Alpine.$persist(false).as('rephraser_template_mode'),
                
                // UI State
                isGenerating: false,
                status: '',
                showConfigModal: false,
                toast: { active: false, msg: '', type: 'info' },
                
                // Data
                availableModels: Alpine.$persist([
                    {id: 'gemini-2.5-flash', name: 'Gemini 2.5 Flash'},
                    {id: 'gemini-2.5-flash-lite', name: 'Gemini Lite'},
                    {id: 'open-mistral-nemo', name: 'Mistral Nemo'},
                    {id: 'mistral-small-latest', name: 'Mistral Small'},
                    {id: 'mistral-tiny', name: 'Mistral Tiny'},
                    {id: 'nemo', name: 'Nemo'},
                    {id: 'mini', name: 'Mini'}
                ]).as('rephraser_models'),
                promptRoles: [],
                kbStats: {},
                
                // Initialize
                async init() {
                    await this.fetchRoles();
                    await this.fetchKbStats();
                    document.documentElement.classList.toggle('dark', this.currentTheme === 'dark');
                },
                
                toggleTheme() {
                    this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
                    document.documentElement.classList.toggle('dark', this.currentTheme === 'dark');
                },
                
                formatOutput(text) {
                    if (!text) return text;
                    return text.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>').replace(/^/, '<p>').replace(/$/, '</p>');
                },
                
                showToast(msg, type = 'info') {
                    this.toast = { active: true, msg, type };
                    setTimeout(() => this.toast.active = false, 3000);
                },
                
                async copyToClipboard(text) {
                    try {
                        await navigator.clipboard.writeText(text);
                        this.showToast('Copied!', 'success');
                    } catch (e) {
                        this.showToast('Failed to copy', 'error');
                    }
                },
                
                async fetchRoles() {
                    try {
                        const res = await fetch('/api/roles');
                        this.promptRoles = await res.json();
                        if (this.promptRoles.length > 0 && !this.selectedRoleName) {
                            const defaultRole = this.promptRoles.find(r => r.is_default);
                            this.selectedRoleName = defaultRole ? defaultRole.name : this.promptRoles[0].name;
                        }
                    } catch (e) {
                        console.error('Failed to fetch roles:', e);
                    }
                },
                
                async fetchKbStats() {
                    try {
                        const res = await fetch('/api/kb-stats');
                        this.kbStats = await res.json();
                    } catch (e) {
                        console.error('Failed to fetch KB stats:', e);
                    }
                },
                
                async generateRephrase() {
                    if (!this.inputText.trim()) {
                        this.showToast('Please enter text', 'error');
                        return;
                    }
                    
                    this.isGenerating = true;
                    this.status = 'Starting...';
                    
                    try {
                        const res = await fetch('/api/rephrase', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                text: this.inputText,
                                model: this.modelA,
                                signature: this.signature,
                                temperature: this.temperature,
                                max_tokens: this.maxTokens,
                                enable_web_search: this.enableWebSearch,
                                template_mode: this.templateMode,
                                role: this.selectedRoleName || null
                            })
                        });
                        
                        if (!res.ok) throw new Error('Generation failed');
                        
                        const data = await res.json();
                        
                        this.history.unshift({
                            original: this.inputText,
                            rephrased: data.data || 'No response',
                            model: this.modelA,
                            timestamp: new Date().toISOString()
                        });
                        
                        if (this.history.length > 20) this.history = this.history.slice(0, 20);
                        
                        this.showToast('Generated!', 'success');
                        
                    } catch (e) {
                        console.error('Generation error:', e);
                        this.showToast('Failed: ' + e.message, 'error');
                    } finally {
                        this.isGenerating = false;
                    }
                },
                
                clearHistory() {
                    if (confirm('Clear all history?')) {
                        this.history = [];
                        this.showToast('History cleared', 'success');
                    }
                },
                
                async approveEntry(item) {
                    try {
                        await fetch('/api/approve', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                original: item.original,
                                rephrased: item.rephrased,
                                model: item.model,
                                role: this.selectedRoleName
                            })
                        });
                        this.showToast('Saved to KB!', 'success');
                    } catch (e) {
                        this.showToast('Failed to save', 'error');
                    }
                }
            }));
        });
    </script>
</body>
</html>
