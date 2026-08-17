@extends('layouts.admin')

@section('content')
<script>
    window.remoteControlApp = function (initialMachineId) {
        return {
            machines: @js($machines),
            searchQuery: '',
            selectedMachine: null,
            commands: [],
            viewMode: initialMachineId ? 'control' : (
                ['search', 'page', 'date_range', 'command_type', 'status'].some(p => new URLSearchParams(window.location.search).has(p))
                    ? 'history' : 'history'
            ),
            // 預設為 history，篩選條件存在時也維持 history
            history: @js($history),
            loading: false,
            tabLoading: null,
            submitting: false,

            // App Config & Meta
            appConfig: {
                storeUrl: @js(route('admin.remote.store-command')),
                indexUrl: @js(route('admin.remote.index')),
                csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },

            // Localized Strings
            translations: @js([
                'Please select a slot' => __('Please select a slot'),
                'Search cargo lane' => __('Search cargo lane'),
                'Stock:' => __('Stock:'),
                'Loading...' => __('Loading...'),
                'No active cargo lanes found' => __('No active cargo lanes found'),
                'Empty' => __('Empty'),
                'Machine Reboot' => __('Machine Reboot'),
                'Machine Force Reboot' => __('Machine Force Reboot'),
                'Card Reader Reboot' => __('Card Reader Reboot'),
                'Remote Settlement' => __('Remote Settlement'),
                'Lock Page Lock' => __('Lock Page Lock'),
                'Lock Page Unlock' => __('Lock Page Unlock'),
                'Fan On' => __('Fan On'),
                'Fan Off' => __('Fan Off'),
                'Fan Auto' => __('Fan Auto'),
                'Ambient Temperature' => __('Ambient Temperature'),
                'Ambient Temperature Upper Limit' => __('Ambient Temperature Upper Limit'),
                'Remote Change' => __('Remote Change'),
                'Remote Dispense' => __('Remote Dispense'),
                'Adjust Stock & Expiry' => __('Adjust Stock & Expiry'),
                'Sync Ads' => __('Sync Ads'),
                'Sync Products' => __('Sync Products'),
                'Sync Settings' => __('Sync Settings'),
                'Update App' => __('Update App'),
                'Pending' => __('Pending'),
                'Sent' => __('Sent'),
                'Success' => __('Success'),
                'Failed' => __('Failed'),
                'Superseded' => __('Superseded'),
                'Timeout' => __('Timeout'),
                'System' => __('System'),
                'Superseded by new adjustment' => __('Superseded by new adjustment'),
                'Superseded by new command' => __('Superseded by new command'),
                'Superseded by new command (Timeout)' => __('Superseded by new command (Timeout)'),
                'Slot' => __('Slot'),
                'Stock' => __('Stock'),
                'Expiry' => __('Expiry'),
                'Batch' => __('Batch'),
                'Amount' => __('Amount'),
                'Command error:' => __('Command error:'),
                'Command has been queued successfully.' => __('Command has been queued successfully.'),
                'Remote Settlement' => __('Remote Settlement'),
                'Just now' => __('Just now'),
                'mins ago' => __('mins ago'),
                'hours ago' => __('hours ago'),
            ]),

            // Form States
            lockStatus: false,
            changeAmount: 100,
            selectedSlot: '',
            note: '',

            async init() {
                // Watch for machine data changes to rebuild slot select
                this.$watch('selectedMachine', (val) => {
                    if (val && val.slots) {
                        this.$nextTick(() => this.updateSlotSelect());
                    }
                });

                // 首次載入時綁定分頁連結
                this.$nextTick(() => {
                    this.bindPaginationLinks(this.$refs.historyContent, 'history');
                    this.bindPaginationLinks(this.$refs.listContent, 'list');
                });

                // 同步頂部進度條 (Removed for tab/pagination to reduce visual noise as requested)
                /*
                this.$watch('tabLoading', (val) => {
                    const bar = document.getElementById('top-loading-bar');
                    if (bar) {
                        if (val) bar.classList.add('loading');
                        else bar.classList.remove('loading');
                    }
                });
                */

                if (initialMachineId) {
                    const machine = this.machines.data.find(m => m.id == initialMachineId);
                    if (machine) {
                        await this.selectMachine(machine);
                    }
                }
            },

            // 攔截分頁連結與下拉切換 (與庫存模組一致的強健版本)
            bindPaginationLinks(container, tab) {
                if (!container) return;
                
                // 處理連結
                container.querySelectorAll('a[href]').forEach(a => {
                    const href = a.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                    
                    try {
                        const url = new URL(href, window.location.origin);
                        const pageKey = (tab === 'list') ? 'machine_page' : 'history_page';
                        
                        // 確保只有分頁連結被攔截
                        if (!url.searchParams.has(pageKey) || a.closest('td.px-6')) return;
                        
                        a.addEventListener('click', (e) => {
                            if (a.title) return; // 略過有 title 的連結 (可能是其他功能)
                            e.preventDefault();
                            const page = url.searchParams.get(pageKey) || 1;
                            const perPage = url.searchParams.get('per_page') || '';
                            let extra = `&${pageKey}=${page}`;
                            if (perPage) extra += `&per_page=${perPage}`;
                            
                            const newUrl = new URL(this.appConfig.indexUrl);
                            newUrl.searchParams.set('tab', tab);
                            newUrl.searchParams.set('_ajax', '1');
                            extra.split('&').filter(Boolean).forEach(p => {
                                const [k, v] = p.split('=');
                                newUrl.searchParams.set(k, v);
                            });
                            
                            this.fetchTabData(tab, newUrl.toString());
                        });
                    } catch (err) { }
                });

                // 處理下拉切換 (每頁筆數或跳頁)
                container.querySelectorAll('select[onchange]').forEach(sel => {
                    const origOnchange = sel.getAttribute('onchange');
                    sel.removeAttribute('onchange');
                    sel.addEventListener('change', () => {
                        const val = sel.value;
                        const pageKey = (tab === 'list') ? 'machine_page' : 'history_page';
                        try {
                            if (val.startsWith('http') || val.startsWith('/')) {
                                const url = new URL(val, window.location.origin);
                                const page = url.searchParams.get(pageKey) || 1;
                                const perPage = url.searchParams.get('per_page') || '';
                                let extra = `&${pageKey}=${page}`;
                                if (perPage) extra += `&per_page=${perPage}`;
                                
                                const newUrl = new URL(this.appConfig.indexUrl);
                                newUrl.searchParams.set('tab', tab);
                                newUrl.searchParams.set('_ajax', '1');
                                extra.split('&').filter(Boolean).forEach(p => {
                                    const [k, v] = p.split('=');
                                    newUrl.searchParams.set(k, v);
                                });
                                this.fetchTabData(tab, newUrl.toString());
                            } else if (origOnchange && origOnchange.includes('per_page')) {
                                const newUrl = new URL(this.appConfig.indexUrl);
                                newUrl.searchParams.set('tab', tab);
                                newUrl.searchParams.set('_ajax', '1');
                                newUrl.searchParams.set('per_page', val);
                                this.fetchTabData(tab, newUrl.toString());
                            }
                        } catch (err) { }
                    });
                });
            },

            async searchInTab(tab, clear = false) {
                const form = event?.target?.closest('form');
                const url = new URL(this.appConfig.indexUrl);
                url.searchParams.set('tab', tab);
                url.searchParams.set('_ajax', '1');

                if (!clear && form) {
                    const formData = new FormData(form);
                    for (let [key, value] of formData.entries()) {
                        if (value) url.searchParams.set(key, value);
                    }
                }

                await this.fetchTabData(tab, url.toString());
            },

            async fetchTabData(tab, url) {
                this.tabLoading = tab;
                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();
                    if (data.success) {
                        const ref = (tab === 'history') ? this.$refs.historyContent : this.$refs.listContent;
                        if (ref) {
                            ref.innerHTML = data.html;
                            
                            this.$nextTick(() => {
                                Alpine.initTree(ref);
                                this.bindPaginationLinks(ref, tab);
                                if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                                    setTimeout(() => window.HSStaticMethods.autoInit(), 100);
                                }
                            });
                        }

                        // Update browser URL
                        const browserUrl = new URL(url);
                        browserUrl.searchParams.delete('_ajax');
                        window.history.pushState({}, '', browserUrl.toString());
                    }
                } catch (e) {
                    console.error('Fetch error:', e);
                } finally {
                    this.tabLoading = null;
                }
            },

            updateSlotSelect() {
                const wrapper = document.getElementById('slot-select-wrapper');
                if (!wrapper) return;

                // Clear previous and reset
                const oldSelect = wrapper.querySelector('select');
                if (oldSelect) {
                    try {
                        const instance = window.HSSelect.getInstance(oldSelect);
                        if (instance) instance.destroy();
                    } catch (e) { }
                }
                wrapper.innerHTML = '';

                // If loading, show a skeleton or simple text
                if (this.loading) {
                    wrapper.innerHTML = `<div class="py-5 px-6 rounded-xl bg-slate-50/50 dark:bg-slate-950/30 border border-slate-100 dark:border-slate-800 text-slate-400 text-sm font-bold animate-pulse">${this.translations['Loading...']}</div>`;
                    return;
                }

                if (!this.selectedMachine || !this.selectedMachine.slots || this.selectedMachine.slots.length === 0) {
                    wrapper.innerHTML = `
                    <div class="p-6 rounded-[1.5rem] bg-rose-500/5 text-rose-500 text-xs font-black uppercase tracking-[0.1em] text-center border border-rose-500/10">
                        ${this.translations['No active cargo lanes found']}
                    </div>
                `;
                    return;
                }

                const selectEl = document.createElement('select');
                selectEl.className = 'hidden';
                selectEl.id = 'dynamic-slot-select-' + Date.now();

                const config = {
                    "placeholder": this.translations['Please select a slot'] + "...",
                    "hasSearch": true,
                    "searchPlaceholder": this.translations['Search cargo lane'] + "...",
                    "isHidePlaceholder": false,
                    "searchClasses": "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
                    "searchWrapperClasses": "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                    "toggleClasses": "hs-select-toggle luxury-select-toggle",
                    "dropdownClasses": "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[100] animate-luxury-in",
                    "optionClasses": "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
                    "optionTemplate": '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>'
                };
                selectEl.setAttribute('data-hs-select', JSON.stringify(config));

                const placeholderOpt = document.createElement('option');
                placeholderOpt.value = '';
                placeholderOpt.textContent = this.translations['Please select a slot'] + "...";
                placeholderOpt.dataset.title = this.translations['Please select a slot'] + "...";
                selectEl.appendChild(placeholderOpt);

                const sortedSlots = [...this.selectedMachine.slots].sort((a, b) => {
                    const aNo = parseInt(a.slot_no);
                    const bNo = parseInt(b.slot_no);
                    return isNaN(aNo) || isNaN(bNo) ? a.slot_no.localeCompare(b.slot_no) : aNo - bNo;
                });

                sortedSlots.forEach(slot => {
                    const opt = document.createElement('option');
                    opt.value = slot.slot_no;
                    const productName = slot.product ? slot.product.name : this.translations['Empty'];
                    const label = `[${slot.slot_no}] ${productName} (${this.translations['Stock:']} ${slot.stock})`;
                    opt.textContent = label;
                    opt.dataset.title = label;
                    if (slot.slot_no === this.selectedSlot) opt.selected = true;
                    selectEl.appendChild(opt);
                });

                wrapper.appendChild(selectEl);
                selectEl.addEventListener('change', (e) => { this.selectedSlot = e.target.value; });

                if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                    window.HSStaticMethods.autoInit(['select']);
                }
            },

            confirmModal: {
                show: false,
                type: '',
                params: {}
            },

            async selectMachine(machine) {
                this.selectedMachine = machine;
                this.viewMode = 'control';
                this.loading = true;
                this.commands = [];

                // Update URL without refresh
                const url = new URL(window.location);
                url.searchParams.set('machine_id', machine.id);
                window.history.pushState({}, '', url);

                try {
                    const res = await fetch(`/admin/remote?machine_id=${machine.id}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    this.commands = data.commands || [];
                    if (data.machine) {
                        this.selectedMachine = data.machine;
                    }
                } catch (e) {
                    console.error('Fetch error:', e);
                } finally {
                    this.loading = false;
                    this.$nextTick(() => this.updateSlotSelect());
                }
            },

            backToList() {
                this.viewMode = 'list';
                this.selectedMachine = null;
                const url = new URL(window.location);
                url.searchParams.delete('machine_id');
                window.history.pushState({}, '', url);
            },

            backToHistory() {
                this.viewMode = 'history';
                this.selectedMachine = null;
                const url = new URL(window.location);
                url.searchParams.delete('machine_id');
                window.history.pushState({}, '', url);
            },

            sendCommand(type, params = {}) {
                this.note = ''; // Reset note for new command
                this.confirmModal.type = type;
                this.confirmModal.params = params;
                this.confirmModal.show = true;
            },

            async executeCommand() {
                const type = this.confirmModal.type;
                const params = this.confirmModal.params;
                this.confirmModal.show = false;
                this.submitting = true;

                try {
                    const formData = new FormData();
                    formData.append('machine_id', this.selectedMachine?.id);
                    formData.append('command_type', type);
                    formData.append('note', this.note);

                    if (params.amount) formData.append('amount', params.amount);
                    if (params.slot_no) formData.append('slot_no', params.slot_no);

                    const res = await fetch(this.appConfig.storeUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.appConfig.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        window.location.href = this.appConfig.indexUrl;
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: this.translations['Command error:'] + ' ' + e.message,
                            type: 'error'
                        }
                    }));
                    console.error('Command error:', e);
                } finally {
                    this.submitting = false;
                }
            },

            formatTime(dateStr) {
                if (!dateStr) return '--';
                const date = new Date(dateStr);
                const now = new Date();
                const diff = Math.floor((now - date) / 1000); // seconds

                if (diff < 60) return this.translations['Just now'];
                if (diff < 3600) return Math.floor(diff / 60) + ' ' + this.translations['mins ago'];
                if (diff < 7200) return '1 ' + this.translations['hours ago'];

                // 超過 2 小時顯示完整日期與時間 (HH:mm)，與庫存頁面格式一致
                return date.getFullYear() + '-' + 
                       String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(date.getDate()).padStart(2, '0') + ' ' + 
                       date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
            },

            getCommandBadgeClass(status) {
                switch (status) {
                    case 'pending': return 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20';
                    case 'sent': return 'bg-cyan-100 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-400 border-cyan-200 dark:border-cyan-500/20';
                    case 'success': return 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20';
                    case 'failed': return 'bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200 dark:border-rose-500/20';
                    case 'timeout': return 'bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200 dark:border-rose-500/20';
                    case 'superseded': return 'bg-slate-100 text-slate-500 dark:bg-slate-500/10 dark:text-slate-400 border-slate-200 dark:border-slate-500/20 opacity-80';
                    default: return 'bg-slate-100 text-slate-600 border-slate-200';
                }
            },

            getCommandName(type) {
                const names = {
                    'reboot': this.translations['Machine Reboot'],
                    'reboot_force': this.translations['Machine Force Reboot'],
                    'reboot_card': this.translations['Card Reader Reboot'],
                    'checkout': this.translations['Remote Settlement'],
                    'lock': this.translations['Lock Page Lock'],
                    'unlock': this.translations['Lock Page Unlock'],
                    'fanon': this.translations['Fan On'],
                    'fanoff': this.translations['Fan Off'],
                    'fanauto': this.translations['Fan Auto'],
                    'power_on': this.translations['Power On'] || '電源開啟',
                    'power_off': this.translations['Power Off'] || '電源關閉',
                    'power_toggle': this.translations['Power Toggle'] || '電源切換',
                    'power_status': this.translations['Power Status'] || '狀態查詢',
                    'ambient_temp_limit': this.translations['Ambient Temperature Upper Limit'],
                    'change': this.translations['Remote Change'],
                    'dispense': this.translations['Remote Dispense'],
                    'reload_stock': this.translations['Adjust Stock & Expiry'],
                    'update_ads': this.translations['Sync Ads'],
                    'update_products': this.translations['Sync Products'],
                    'update_settings': this.translations['Sync Settings'],
                    'update_app': this.translations['Update App']
                };
                return names[type] || type;
            },

            getCommandStatus(status) {
                const statuses = {
                    'pending': this.translations['Pending'],
                    'sent': this.translations['Sent'],
                    'success': this.translations['Success'],
                    'failed': this.translations['Failed'],
                    'superseded': this.translations['Superseded'],
                    'timeout': this.translations['Timeout']
                };
                return statuses[status] || status;
            },

            getOperatorName(user) {
                return user ? user.name : this.translations['System'];
            },

            translateNote(note) {
                if (!note) return '';
                const translations = {
                    'Superseded by new adjustment': this.translations['Superseded by new adjustment'],
                    'Superseded by new command': this.translations['Superseded by new command'],
                    'Superseded by new command (Timeout)': this.translations['Superseded by new command (Timeout)']
                };
                return translations[note] || note;
            },

            getPayloadDetails(item) {
                if (item.command_type === 'reload_stock' && item.payload) {
                    const p = item.payload;
                    let details = `${this.translations['Slot']} ${p.slot_no}: `;

                    if (p.old.stock !== p.new.stock) {
                        details += `${this.translations['Stock']} ${p.old.stock} → ${p.new.stock}`;
                    }

                    if (p.old.expiry_date !== p.new.expiry_date) {
                        if (p.old.stock !== p.new.stock) details += ', ';
                        details += `${this.translations['Expiry']} ${p.old.expiry_date || 'N/A'} → ${p.new.expiry_date || 'N/A'}`;
                    }

                    if (p.old.batch_no !== p.new.batch_no) {
                        if (p.old.stock !== p.new.stock || p.old.expiry_date !== p.new.expiry_date) details += ', ';
                        details += `${this.translations['Batch']} ${p.old.batch_no || 'N/A'} → ${p.new.batch_no || 'N/A'}`;
                    }

                    return details;
                }

                if (item.command_type === 'change' && item.payload) {
                    return `${this.translations['Amount']}: ${item.payload.amount}`;
                }

                if (item.command_type === 'dispense' && item.payload) {
                    let details = `${this.translations['Slot']} ${item.payload.slot_no}`;
                    if (item.payload.old_stock !== undefined && item.payload.new_stock !== undefined) {
                        details += `: ${this.translations['Stock']} ${item.payload.old_stock} → ${item.payload.new_stock}`;
                    }
                    return details;
                }

                if (item.command_type === 'ambient_temp_limit' && item.payload) {
                    const temp = item.payload.temperature;
                    return `${this.translations['Ambient Temperature Upper Limit']}: ${temp !== null && temp !== undefined ? temp + '°C' : this.translations['Empty']}`;
                }

                return '';
            }
        };
    };
</script>

<div class="space-y-2 pb-20" x-data="remoteControlApp({{ Js::from($selectedMachine ? $selectedMachine->id : '') }})">

    <!-- Header -->
    <x-page-header 
        :title="__($title ?? 'Remote Command Center')"
        :subtitle="__($subtitle ?? 'Execute maintenance and operational commands remotely')"
    >
        <x-slot:back>
            <template x-if="viewMode === 'control'">
                <button @click="backToList()"
                    class="p-2 sm:p-2.5 rounded-xl bg-white dark:bg-slate-800 text-slate-500 border border-slate-200/60 dark:border-slate-700/60 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all active:scale-95 shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </button>
            </template>
        </x-slot:back>
    </x-page-header>

    <!-- Tab Navigation (Only visible when not in specific machine control) -->
    <template x-if="viewMode !== 'control'">
        <x-tab-nav model="viewMode">
            <x-tab-nav-item value="history" :label="__('Operation Records')" model="viewMode" />
            <x-tab-nav-item value="list" :label="__('New Command')" model="viewMode" />
        </x-tab-nav>
    </template>

    <div class="mt-6">

        <!-- History View: Operation Records -->
        <div x-show="viewMode === 'history'" x-cloak>
            <div class="space-y-6 animate-luxury-in">
                <div class="luxury-card rounded-3xl p-8 overflow-hidden relative">
                    <div x-ref="historyContent">
                        @include('admin.remote.partials.tab-history-index')
                    </div>
                </div>
            </div>
        </div>


        <!-- Master View: Machine List -->
        <div x-show="viewMode === 'list'" x-cloak>
            <div class="space-y-6 animate-luxury-in">
                <div class="luxury-card rounded-3xl p-8 overflow-hidden relative">
                    <div x-ref="listContent">
                        @include('admin.remote.partials.tab-machines-index')
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail View: Remote Control Dashboard -->
        <div x-show="viewMode === 'control'" x-cloak>
            <div class="space-y-6 animate-luxury-in">

                <!-- Dashboard Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <!-- Left: Control Actions (Spans 2 columns) -->
                    <div class="lg:col-span-2 space-y-8">

                        <!-- Machine Status Card -->
                        <div
                            class="luxury-card rounded-[2.5rem] p-8 border border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between">
                            <div class="flex items-center gap-6">
                                <div
                                    class="w-16 h-16 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-500 border border-cyan-500/20">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-black text-slate-800 dark:text-white leading-tight"
                                        x-text="selectedMachine?.name"></h2>
                                    <p class="text-xs font-mono font-bold text-slate-400 mt-1 uppercase tracking-widest"
                                        x-text="selectedMachine?.serial_no"></p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span
                                    class="px-4 py-2 rounded-full bg-emerald-500/10 text-emerald-600 text-xs font-black uppercase tracking-widest border border-emerald-500/20 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ __('Connected') }}
                                </span>
                            </div>
                        </div>

                        <!-- Row 2: System Control -->
                        <div
                            class="luxury-card rounded-[2.5rem] p-8 border border-slate-200/60 dark:border-slate-800/60">
                            <h3
                                class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-wider mb-8 flex items-center gap-3">
                                <span class="w-2 h-6 bg-cyan-500 rounded-full"></span>
                                {{ __('Machine Information') }}
                            </h3>

                            <div class="space-y-8">
                                <!-- Power Controls (電源控制) -->
                                    <div class="flex items-center gap-2 px-1">
                                        <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Power Controls') }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                        <!-- Power On -->
                                        <button @click="sendCommand('power_on')"
                                            class="p-6 rounded-3xl border border-emerald-200 dark:border-emerald-500/30 flex items-center gap-5 hover:border-emerald-500/60 dark:hover:border-emerald-400/70 hover:bg-emerald-500/5 dark:hover:bg-emerald-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-500 border border-emerald-500/20 dark:border-emerald-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider group-hover:text-emerald-500 transition-colors">{{ __('Power On') }}</div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ __('Turn on relay / power output') }}</div>
                                            </div>
                                        </button>

                                        <!-- Power Off -->
                                        <button @click="sendCommand('power_off')"
                                            class="p-6 rounded-3xl border border-rose-200 dark:border-rose-500/30 flex items-center gap-5 hover:border-rose-500/60 dark:hover:border-rose-400/70 hover:bg-rose-500/5 dark:hover:bg-rose-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-500 dark:text-rose-400 group-hover:scale-110 transition-transform duration-500 border border-rose-500/20 dark:border-rose-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider group-hover:text-rose-500 transition-colors">{{ __('Power Off') }}</div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ __('Turn off relay / power output') }}</div>
                                            </div>
                                        </button>

                                        <!-- Power Toggle -->
                                        <button @click="sendCommand('power_toggle')"
                                            class="p-6 rounded-3xl border border-indigo-200 dark:border-indigo-500/30 flex items-center gap-5 hover:border-indigo-500/60 dark:hover:border-indigo-400/70 hover:bg-indigo-500/5 dark:hover:bg-indigo-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-500 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-500 border border-indigo-500/20 dark:border-indigo-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-wider group-hover:text-indigo-500 transition-colors">{{ __('Power Toggle') }}</div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ __('Toggle current relay state') }}</div>
                                            </div>
                                        </button>
                                    </div>

                                    <!-- Maintenance Operations -->
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 ml-1">
                                        <div class="w-1 h-3 bg-slate-300 dark:bg-slate-700 rounded-full"></div>
                                        <span
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{
                                            __('Maintenance Operations') }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <!-- Reboot System -->
                                        <button @click="sendCommand('reboot')"
                                            class="p-6 rounded-3xl border border-slate-100 dark:border-slate-800 flex items-center gap-5 hover:border-cyan-500/50 dark:hover:border-cyan-400/60 hover:bg-cyan-500/5 dark:hover:bg-cyan-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-500 dark:text-cyan-400 group-hover:scale-110 transition-transform duration-500 border border-cyan-500/20 dark:border-cyan-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div
                                                    class="text-sm font-black text-slate-800 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                                    {{ __('Machine Reboot') }}</div>
                                            </div>
                                        </button>

                                        <!-- Force Reboot (ignores busy / stuck dialog) -->
                                        <button @click="sendCommand('reboot_force')"
                                            class="p-6 rounded-3xl border border-amber-200 dark:border-amber-500/30 flex items-center gap-5 hover:border-amber-500/60 dark:hover:border-amber-400/70 hover:bg-amber-500/5 dark:hover:bg-amber-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 dark:text-amber-400 group-hover:scale-110 transition-transform duration-500 border border-amber-500/20 dark:border-amber-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div
                                                    class="text-sm font-black text-slate-800 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                                    {{ __('Machine Force Reboot') }}</div>
                                                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                    {{ __('Ignore busy state (use when stuck)') }}</div>
                                            </div>
                                        </button>

                                        <!-- Card Reader Reboot -->
                                        <button @click="sendCommand('reboot_card')"
                                            class="p-6 rounded-3xl border border-slate-100 dark:border-slate-800 flex items-center gap-5 hover:border-cyan-500/50 dark:hover:border-cyan-400/60 hover:bg-cyan-500/5 dark:hover:bg-cyan-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-500 dark:text-cyan-400 group-hover:scale-110 transition-transform duration-500 border border-cyan-500/20 dark:border-cyan-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3-3v8a3 3 0 003 3z" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div
                                                    class="text-sm font-black text-slate-800 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                                    {{ __('Card Reader Reboot') }}</div>
                                            </div>
                                        </button>

                                        <!-- Remote Settlement -->
                                        <button @click="sendCommand('checkout')"
                                            class="p-6 rounded-3xl border border-slate-100 dark:border-slate-800 flex items-center gap-5 hover:border-emerald-500/50 dark:hover:border-emerald-400/60 hover:bg-emerald-500/5 dark:hover:bg-emerald-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-500 border border-emerald-500/20 dark:border-emerald-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div
                                                    class="text-sm font-black text-slate-800 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                                    {{ __('Remote Settlement') }}</div>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Fan Controls -->
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 ml-1">
                                        <div class="w-1 h-3 bg-slate-300 dark:bg-slate-700 rounded-full"></div>
                                        <span
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{
                                            __('Fan Controls') }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <!-- Fan On -->
                                        <button @click="sendCommand('fanon')"
                                            class="p-6 rounded-3xl border border-slate-100 dark:border-slate-800 flex items-center gap-5 hover:border-cyan-500/50 dark:hover:border-cyan-400/60 hover:bg-cyan-500/5 dark:hover:bg-cyan-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-500 dark:text-cyan-400 group-hover:scale-110 transition-transform duration-500 border border-cyan-500/20 dark:border-cyan-400/20">
                                                <svg class="w-6 h-6 animate-[spin_10s_linear_infinite]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div
                                                    class="text-sm font-black text-slate-800 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                                    {{ __('Fan On') }}</div>
                                            </div>
                                        </button>

                                        <!-- Fan Off -->
                                        <button @click="sendCommand('fanoff')"
                                            class="p-6 rounded-3xl border border-slate-100 dark:border-slate-800 flex items-center gap-5 hover:border-cyan-500/50 dark:hover:border-cyan-400/60 hover:bg-cyan-500/5 dark:hover:bg-cyan-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-500 dark:text-cyan-400 group-hover:scale-110 transition-transform duration-500 border border-cyan-500/20 dark:border-cyan-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div
                                                    class="text-sm font-black text-slate-800 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                                    {{ __('Fan Off') }}</div>
                                            </div>
                                        </button>

                                        <!-- Fan Auto -->
                                        <button @click="sendCommand('fanauto')"
                                            class="p-6 rounded-3xl border border-slate-100 dark:border-slate-800 flex items-center gap-5 hover:border-cyan-500/50 dark:hover:border-cyan-400/60 hover:bg-cyan-500/5 dark:hover:bg-cyan-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-500 dark:text-cyan-400 group-hover:scale-110 transition-transform duration-500 border border-cyan-500/20 dark:border-cyan-400/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div
                                                    class="text-sm font-black text-slate-800 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">
                                                    {{ __('Fan Auto') }}</div>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Security Controls -->
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 ml-1">
                                        <div class="w-1 h-3 bg-slate-300 dark:bg-slate-700 rounded-full"></div>
                                        <span
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{
                                            __('Security Controls') }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Lock -->
                                        <button @click="sendCommand('lock')"
                                            class="p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 flex items-center justify-between hover:border-rose-500/50 dark:hover:border-rose-400/60 hover:bg-rose-500/5 dark:hover:bg-rose-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div class="flex items-center gap-5">
                                                <div
                                                    class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-500 dark:text-rose-400 group-hover:scale-110 transition-transform duration-500 border border-rose-500/20 dark:border-rose-400/20">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                </div>
                                                <div class="text-left">
                                                    <div
                                                        class="text-sm font-black text-slate-800 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                                                        {{ __('Lock Page Lock') }}</div>
                                                </div>
                                            </div>
                                            <div
                                                class="px-4 py-2 rounded-xl bg-rose-500 text-white text-[10px] font-black uppercase tracking-widest shadow-lg shadow-rose-500/20 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0 font-sans">
                                                {{ __('Lock Now') }}</div>
                                        </button>

                                        <!-- Unlock -->
                                        <button @click="sendCommand('unlock')"
                                            class="p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 flex items-center justify-between hover:border-emerald-500/50 dark:hover:border-emerald-400/60 hover:bg-emerald-500/5 dark:hover:bg-emerald-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm">
                                            <div class="flex items-center gap-5">
                                                <div
                                                    class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-500 border border-emerald-500/20 dark:border-emerald-400/20">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div class="text-left">
                                                    <div
                                                        class="text-sm font-black text-slate-800 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                                        {{ __('Lock Page Unlock') }}</div>
                                                </div>
                                            </div>
                                            <div
                                                class="px-4 py-2 rounded-xl bg-emerald-500 text-white text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0 font-sans">
                                                {{ __('Unlock Now') }}</div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parametric Actions -->
                        <!-- Row 3: Parametric Actions -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                            <!-- Remote Change -->
                            <div
                                class="luxury-card rounded-[2.5rem] p-8 border border-slate-200/60 dark:border-slate-800/60">
                                <div class="flex items-center justify-between mb-8">
                                    <h3
                                        class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-3">
                                        <span class="w-2 h-6 bg-amber-500 rounded-full"></span>
                                        {{ __('Remote Change') }}
                                    </h3>
                                    <div
                                        class="text-[40px] font-black text-slate-200 dark:text-slate-800 leading-none select-none tracking-tighter">
                                        {{ __('CASH') }}</div>
                                </div>
                                <div class="space-y-6">
                                    <div class="relative group">
                                        <input type="number" x-model="changeAmount"
                                            class="luxury-input w-full bg-slate-50/50 dark:bg-slate-950/40 border-slate-200 dark:border-slate-800/80 hover:border-amber-500/30 dark:hover:border-amber-400/40 pl-20 text-4xl font-display font-black text-slate-800 dark:text-white focus:ring-0 py-6 placeholder:text-slate-400 transition-all">
                                        <div
                                            class="absolute inset-y-0 left-6 flex items-center pointer-events-none z-10 transition-transform group-focus-within:translate-x-1">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500 dark:text-amber-400 border border-amber-500/20 dark:border-amber-400/20 group-focus-within:scale-110 group-hover:scale-105 transition-transform duration-300">
                                                <span class="text-xl font-black">$</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-4 gap-3">
                                        <template x-for="amt in [10, 50, 100, 500]">
                                            <button @click="changeAmount = amt"
                                                class="py-3 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 text-[11px] font-black uppercase tracking-widest transition-all bg-white/50 dark:bg-slate-900/50 text-slate-600 dark:text-slate-400 shadow-sm hover:border-amber-500/50 dark:hover:border-amber-400/60 hover:text-amber-500 dark:hover:text-amber-400 hover:bg-amber-500/10 dark:hover:bg-amber-400/5 active:scale-95"
                                                x-text="amt"></button>
                                        </template>
                                    </div>
                                    <button @click="sendCommand('change', { amount: changeAmount })"
                                        :disabled="submitting"
                                        class="btn-luxury-primary w-full py-5 rounded-[1.5rem] text-sm shadow-lg shadow-cyan-500/20 group">
                                        <span class="relative z-10 flex items-center justify-center gap-2">
                                            {{ __('Execute Remote Change') }}
                                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Remote Dispense -->
                            <div
                                class="luxury-card rounded-[2.5rem] p-8 border border-slate-200/60 dark:border-slate-800/60">
                                <div class="flex items-center justify-between mb-8">
                                    <h3
                                        class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-3">
                                        <span class="w-2 h-6 bg-violet-500 rounded-full"></span>
                                        {{ __('Remote Dispense') }}
                                    </h3>
                                    <div
                                        class="text-[40px] font-black text-slate-200 dark:text-slate-800 leading-none select-none tracking-tighter">
                                        {{ __('ITEM') }}</div>
                                </div>
                                <div class="space-y-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] ml-1">{{ __('Select Target Slot') }}</label>
                                        <div id="slot-select-wrapper" class="relative min-h-[60px]">
                                            <!-- Content Injected Dynamically by Alpine.js -->
                                        </div>
                                    </div>

                                    <!-- Dispense Button (Card Style - Mirroring Row 2) -->
                                    <button @click="sendCommand('dispense', { slot_no: selectedSlot })"
                                        :disabled="submitting || !selectedSlot"
                                        class="w-full p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 flex items-center justify-between hover:border-violet-500/50 dark:hover:border-violet-400/60 hover:bg-violet-500/5 dark:hover:bg-violet-400/5 group transition-all bg-white/50 dark:bg-slate-900/40 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                        <div class="flex items-center gap-5">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-violet-500/10 flex items-center justify-center text-violet-500 dark:text-violet-400 group-hover:scale-110 transition-transform duration-500 border border-violet-500/20 dark:border-violet-400/20 group-disabled:opacity-60">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div
                                                    class="text-sm font-black text-slate-800 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors group-disabled:text-slate-500">
                                                    {{ __('Remote Dispense') }}</div>
                                            </div>
                                        </div>
                                        <div
                                            class="px-4 py-2 rounded-xl bg-violet-500 text-white text-[10px] font-black uppercase tracking-widest shadow-lg shadow-violet-500/20 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0 font-sans group-disabled:hidden">
                                            {{ __('Trigger') }}
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Command History (Sidebar) -->
                    <div class="space-y-4 mt-2">
                        <h3
                            class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-3">
                            <span class="w-1.5 h-6 bg-slate-300 dark:bg-slate-700 rounded-full"></span>
                            {{ __('Recent Commands') }}
                        </h3>

                        <div class="space-y-3.5 max-h-[1000px] overflow-y-auto pr-2 custom-scrollbar">
                            <template x-for="cmd in commands" :key="cmd.id">
                                <div
                                    class="luxury-card p-4 px-5 rounded-2xl border border-slate-100 dark:border-slate-800 transition-all hover:bg-slate-50 dark:hover:bg-slate-900/40 relative group">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-800 dark:text-white"
                                                x-text="getCommandName(cmd.command_type)"></span>
                                            <div class="flex items-center gap-2 mt-1.5">
                                                <div class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500"
                                                    x-text="getOperatorName(cmd.user).substring(0,1)"></div>
                                                <span class="text-xs font-bold text-slate-400"
                                                    x-text="getOperatorName(cmd.user)"></span>
                                            </div>
                                        </div>
                                        <span :class="getCommandBadgeClass(cmd.status)"
                                            class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border"
                                            x-text="getCommandStatus(cmd.status)"></span>
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-400 flex items-center gap-2 mb-3">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span x-text="new Date(cmd.created_at).toLocaleString()"></span>
                                    </div>
                                    <template x-if="getPayloadDetails(cmd)">
                                        <div class="px-3 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-sm font-bold text-cyan-600 dark:text-cyan-400 break-words leading-relaxed"
                                            :class="cmd.status === 'failed' ? 'line-through opacity-60' : ''"
                                            x-text="getPayloadDetails(cmd)">
                                        </div>
                                    </template>
                                    <template x-if="cmd.note">
                                        <div class="mt-2 text-xs font-bold text-slate-400 italic"
                                            x-text="'\"' + cmd.note + ' \"'"></div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="commands.length === 0">
                                <div class="py-20 text-center flex flex-col items-center opacity-30">
                                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="text-[10px] font-black uppercase tracking-widest">{{ __('No command history') }}</div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Custom Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="confirmModal.show" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <!-- Background Backdrop -->
                <div x-show="confirmModal.show" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="confirmModal.show = false"></div>

                <!-- Modal Content -->
                <div x-show="confirmModal.show" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-[2.5rem] bg-white dark:bg-slate-900 p-8 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 dark:border-slate-800">

                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 border border-amber-500/20">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase">{{ __('Command Confirmation') }}</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ __('Please confirm the details below') }}</p>
                        </div>
                    </div>

                    <div
                        class="space-y-4 bg-slate-50 dark:bg-slate-950/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800/50 mb-8">
                        <div class="flex justify-between items-center px-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Command Type') }}</span>
                            <span class="text-sm font-black text-slate-800 dark:text-slate-200"
                                x-text="getCommandName(confirmModal.type)"></span>
                        </div>
                        <div class="flex justify-between items-center px-1 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                            <span class="text-[10px] font-black text-cyan-500 uppercase tracking-widest">{{ __('Target Machine') }}</span>
                            <span class="text-sm font-black text-slate-800 dark:text-slate-200"
                                x-text="selectedMachine ? selectedMachine.name : ''"></span>
                        </div>
                        <div class="px-1 pt-4">
                            <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] ml-1">{{ __('Operation Note') }}</label>
                            <textarea x-model="note"
                                class="luxury-input w-full min-h-[100px] text-sm py-3 px-4 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus:border-cyan-500/50"
                                placeholder="{{ __('Reason for this command...') }}"></textarea>
                        </div>
                        <template x-if="confirmModal.params.amount">
                            <div
                                class="flex justify-between items-center pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                                <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest">{{
                                    __('Amount') }}</span>
                                <span class="text-lg font-black text-slate-800 dark:text-slate-200"
                                    x-text="'$' + confirmModal.params.amount"></span>
                            </div>
                        </template>
                        <template x-if="confirmModal.params.slot_no">
                            <div
                                class="flex justify-between items-center pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                                <span class="text-[10px] font-black text-violet-500 uppercase tracking-widest">{{ __('Slot No') }}</span>
                                <span class="text-sm font-black text-slate-800 dark:text-slate-200"
                                    x-text="confirmModal.params.slot_no"></span>
                            </div>
                        </template>
                    </div>

                    <div class="flex gap-4">
                        <button @click="confirmModal.show = false"
                            class="flex-1 px-6 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="executeCommand()"
                            class="flex-1 px-6 py-4 rounded-2xl bg-cyan-600 text-white text-xs font-black uppercase tracking-widest hover:bg-cyan-500 shadow-lg shadow-cyan-500/20 active:scale-[0.98] transition-all">
                            {{ __('Execute') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>

<style>
    /* Custom Scrollbar for Luxury UI */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e133;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #cbd5e166;
    }

    /* Hide default number spinners */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
        appearance: none;
    }
</style>
@endsection