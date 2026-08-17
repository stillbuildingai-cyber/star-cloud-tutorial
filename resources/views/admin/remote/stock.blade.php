@extends('layouts.admin')

@section('content')
<script>
    window.stockApp = function (initialMachineId) {
        return {
            machines: @json($machines),
            searchQuery: '',
            selectedMachine: null,
            slots: [],
            viewMode: initialMachineId ? 'detail' : 'history',
            displayMode: 'table',
            history: @js($history),
            loading: false,
            updating: false,
            tabLoading: false,

            // Modal State
            showEditModal: false,
            selectedSlot: null,
            formData: {
                stock: 0,
                expiry_date: '',
                batch_no: ''
            },

            // 機器搜尋狀態
            machineSearch: '{{ request('machine_search') }}',
            historySearch: '{{ request('search') }}',
            historyStartDate: '{{ request('start_date', now()->startOfDay()->format('Y-m-d H:i')) }}',
            historyEndDate: '{{ request('end_date', now()->endOfDay()->format('Y-m-d H:i')) }}',
            historyStatus: '{{ request('status') }}',

            async init() {
                if (initialMachineId) {
                    const machine = this.machines.data.find(m => m.id == initialMachineId);
                    if (machine) {
                        await this.selectMachine(machine);
                    }
                }
                // 首次載入時綁定分頁連結
                this.$nextTick(() => {
                    if (this.$refs.historyContent) this.bindPaginationLinks(this.$refs.historyContent, 'history');
                    if (this.$refs.machinesContent) this.bindPaginationLinks(this.$refs.machinesContent, 'machines');
                });
                // 觸發進度條 (Removed for tab/pagination to reduce visual noise as requested)
                /*
                this.$watch('tabLoading', (val) => {
                    const bar = document.getElementById('top-loading-bar');
                    if (bar) {
                        if (val) bar.classList.add('loading');
                        else bar.classList.remove('loading');
                    }
                });
                */
            },

            /**
             * AJAX 抓取資料並更新 DOM
             */
            async fetchUrl(tab, url) {
                this.tabLoading = true;
                try {
                    const ajaxUrl = new URL(url, window.location.origin);
                    ajaxUrl.searchParams.set('_ajax', '1');
                    ajaxUrl.searchParams.set('tab', tab);

                    const res = await fetch(ajaxUrl.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    if (!res.ok) throw new Error('Network response was not ok');

                    const html = await res.text();
                    const ref = (tab === 'machines') ? this.$refs.machinesContent : this.$refs.historyContent;
                    
                    if (ref) {
                        ref.innerHTML = html;
                        
                        // 同步更新 URL (移除 _ajax 參數)
                        const visibleUrl = new URL(url, window.location.origin);
                        visibleUrl.searchParams.delete('_ajax');
                        history.pushState({}, '', visibleUrl.toString());

                        // 重新綁定分頁與初始化 UI
                        this.$nextTick(() => {
                            Alpine.initTree(ref);
                            this.bindPaginationLinks(ref, tab);
                            if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
                                window.HSStaticMethods.autoInit();
                            }
                        });
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                } finally {
                    this.tabLoading = false;
                }
            },

            async searchInTab(tab = 'history') {
                const params = new URLSearchParams();
                
                if (tab === 'machines') {
                    if (this.machineSearch) params.set('machine_search', this.machineSearch);
                } else {
                    if (this.historySearch) params.set('search', this.historySearch);
                    if (this.historyStartDate) params.set('start_date', this.historyStartDate);
                    if (this.historyEndDate) params.set('end_date', this.historyEndDate);
                    if (this.historyStatus) params.set('status', this.historyStatus);
                    // 如果有其他參數可以在此加入
                }
                
                const url = `{{ route('admin.remote.stock') }}?${params.toString()}`;
                await this.fetchUrl(tab, url);
            },

            // 監聽分頁事件
            bindPaginationLinks(container, tab) {
                if (!container) return;

                // 監聽容器上的 ajax:navigate 事件，這是 luxury 分頁模板發出的
                container.addEventListener('ajax:navigate', (e) => {
                    e.preventDefault();
                    if (e.detail && e.detail.url) {
                        this.fetchUrl(tab, e.detail.url);
                    }
                }, { once: true }); // 確保只綁定一次

                // 兼容處理：攔截 a[href] 點擊
                container.querySelectorAll('a[href]').forEach(a => {
                    const href = a.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                    
                    try {
                        const url = new URL(href, window.location.origin);
                        const pageKey = (tab === 'machines') ? 'machine_page' : 'history_page';
                        
                        // 只處理分頁連結
                        if (url.searchParams.has(pageKey) && !a.closest('td.px-6')) {
                            a.addEventListener('click', (e) => {
                                if (a.title || a.hasAttribute('onclick')) return;
                                e.preventDefault();
                                this.fetchUrl(tab, href);
                            });
                        }
                    } catch (e) {}
                });
            },

            async selectMachine(machine) {
                this.selectedMachine = machine;
                this.viewMode = 'detail';
                this.loading = true;
                this.slots = [];

                // Update URL without refresh
                const url = new URL(window.location);
                url.searchParams.set('machine_id', machine.id);
                window.history.pushState({}, '', url);

                try {
                    const res = await fetch(`/admin/machines/${machine.id}/slots-ajax`);
                    const data = await res.json();
                    if (data.success) {
                        this.slots = data.slots;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } catch (e) {
                    console.error('Fetch slots error:', e);
                } finally {
                    this.loading = false;
                }
            },

            backToList() {
                this.viewMode = 'list';
                this.selectedMachine = null;
                this.selectedSlot = null;
                this.slots = [];

                // Clear machine_id from URL
                const url = new URL(window.location);
                url.searchParams.delete('machine_id');
                window.history.pushState({}, '', url);
            },

            backToHistory() {
                this.viewMode = 'history';
                this.selectedMachine = null;
                this.selectedSlot = null;
                this.slots = [];

                const url = new URL(window.location);
                url.searchParams.delete('machine_id');
                window.history.pushState({}, '', url);
            },

            openEdit(slot) {
                if (slot.is_pending) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: '{{ __("This slot is syncing with the machine. Please wait.") }}',
                            type: 'warning'
                        }
                    }));
                    return;
                }
                this.selectedSlot = slot;
                this.formData = {
                    stock: slot.stock || 0,
                    expiry_date: slot.expiry_date ? slot.expiry_date.split('T')[0] : '',
                    batch_no: slot.batch_no || ''
                };
                this.showEditModal = true;
            },

            async saveChanges() {
                this.updating = true;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch(`/admin/machines/${this.selectedMachine.id}/slots/expiry`, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf 
                        },
                        body: JSON.stringify({
                            slot_no: this.selectedSlot.slot_no,
                            ...this.formData
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showEditModal = false;
                        window.location.href = "{{ route('admin.remote.stock') }}";
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: '{{ __("Save error:") }} ' + e.message,
                            type: 'error'
                        }
                    }));
                    console.error('Save error:', e);
                } finally {
                    this.updating = false;
                }
            },

            async toggleLock() {
                this.updating = true;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch(`/admin/machines/${this.selectedMachine.id}/slots/lock`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            slot_no: this.selectedSlot.slot_no,
                            is_locked: !this.selectedSlot.is_locked
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showEditModal = false;
                        window.location.href = "{{ route('admin.remote.stock') }}";
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: '{{ __("Save error:") }} ' + e.message,
                            type: 'error'
                        }
                    }));
                    console.error('Lock toggle error:', e);
                } finally {
                    this.updating = false;
                }
            },

            getSlotColorClass(slot) {
                const today = new Date().toISOString().split('T')[0];
                if (slot.expiry_date && slot.expiry_date < today) {
                    return 'bg-rose-50/60 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/30';
                }
                if (!slot.product || parseInt(slot.stock) === 0) {
                    return 'bg-slate-50/50 dark:bg-slate-800/50 text-slate-400 border-slate-200/60 dark:border-slate-700/50';
                }
                if (slot.max_stock > 0 && parseInt(slot.stock) <= (parseInt(slot.max_stock) * 0.2)) {
                    return 'bg-amber-50/60 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/30';
                }
                const maxStock = parseInt(slot.max_stock) || 10;
                if (parseInt(slot.stock) > maxStock / 2) {
                    return 'bg-cyan-50/60 dark:bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-200 dark:border-cyan-500/30';
                }
                return 'bg-emerald-50/60 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30';
            },

            getSlotCardClass(slot) {
                const base = 'luxury-card p-6 rounded-[2rem] backdrop-blur-xl group hover:shadow-2xl transition-all duration-500 relative ';
                const today = new Date().toISOString().split('T')[0];
                if (slot.expiry_date && slot.expiry_date < today) return base + 'bg-rose-500/[0.06] border-rose-500/20 dark:bg-rose-500/10 dark:border-rose-500/30 hover:shadow-rose-500/10';
                if (!slot.product || parseInt(slot.stock) === 0) return base + 'bg-slate-500/[0.06] border-slate-500/20 dark:bg-slate-500/10 dark:border-slate-500/30 hover:shadow-slate-500/10';
                if (slot.max_stock > 0 && parseInt(slot.stock) <= (parseInt(slot.max_stock) * 0.2)) return base + 'bg-amber-500/[0.06] border-amber-500/20 dark:bg-amber-500/10 dark:border-amber-500/30 hover:shadow-amber-500/10';
                const maxStock = parseInt(slot.max_stock) || 10;
                if (parseInt(slot.stock) > maxStock / 2) return base + 'bg-cyan-500/[0.06] border-cyan-500/20 dark:bg-cyan-500/10 dark:border-cyan-500/30 hover:shadow-cyan-500/10';
                return base + 'bg-emerald-500/[0.04] border-emerald-500/10 dark:bg-emerald-500/[0.08] dark:border-emerald-500/25 hover:shadow-emerald-500/10';
            },

            getSlotProgressClass(slot) {
                const today = new Date().toISOString().split('T')[0];
                if (slot.expiry_date && slot.expiry_date < today) return 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.4)]';
                if (!slot.product || parseInt(slot.stock) === 0) return 'bg-slate-400';
                if (slot.max_stock > 0 && parseInt(slot.stock) <= (parseInt(slot.max_stock) * 0.2)) return 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.4)]';
                const maxStock = parseInt(slot.max_stock) || 10;
                if (parseInt(slot.stock) > maxStock / 2) return 'bg-cyan-500 shadow-[0_0_8px_rgba(6,182,212,0.4)]';
                return 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]';
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
                    'reboot': {{ Js::from(__('Machine Reboot')) }},
                    'reboot_force': {{ Js::from(__('Machine Force Reboot')) }},
                    'reboot_card': {{ Js::from(__('Card Reader Reboot')) }},
                    'checkout': {{ Js::from(__('Remote Settlement')) }},
                    'lock': {{ Js::from(__('Lock Page Lock')) }},
                    'unlock': {{ Js::from(__('Lock Page Unlock')) }},
                    'change': {{ Js::from(__('Remote Change')) }},
                    'dispense': {{ Js::from(__('Remote Dispense')) }},
                    'reload_stock': {{ Js::from(__('Adjust Stock & Expiry')) }}
                };
                return names[type] || type;
            },

            getCommandStatus(status) {
                const statuses = {
                    'pending': {{ Js::from(__('Pending')) }},
                    'sent': {{ Js::from(__('Sent')) }},
                    'success': {{ Js::from(__('Success')) }},
                    'failed': {{ Js::from(__('Failed')) }},
                    'timeout': {{ Js::from(__('Timeout')) }},
                    'superseded': {{ Js::from(__('Superseded')) }}
                };
                return statuses[status] || status;
            },

            getOperatorName(user) {
                return user ? user.name : {{ Js::from(__('System')) }};
            },

            getPayloadDetails(item) {
                if (item.command_type === 'reload_stock' && item.payload) {
                    const p = item.payload;
                    let details = `{{ __('Slot') }} ${p.slot_no}: `;

                    if (p.old.stock !== p.new.stock) {
                        details += `{{ __('Stock') }} ${p.old.stock} → ${p.new.stock}`;
                    }

                    if (p.old.expiry_date !== p.new.expiry_date) {
                        if (p.old.stock !== p.new.stock) details += ', ';
                        details += `{{ __('Expiry') }} ${p.old.expiry_date || 'N/A'} → ${p.new.expiry_date || 'N/A'}`;
                    }

                    if (p.old.batch_no !== p.new.batch_no) {
                        if (p.old.stock !== p.new.stock || p.old.expiry_date !== p.new.expiry_date) details += ', ';
                        details += `{{ __('Batch') }} ${p.old.batch_no || 'N/A'} → ${p.new.batch_no || 'N/A'}`;
                    }

                    // 鎖定/解鎖（暫停販售）切換：old/new 僅 is_locked 不同時，避免顯示空白徽章
                    if ((p.old.is_locked ?? false) !== (p.new.is_locked ?? false)) {
                        if (details !== `{{ __('Slot') }} ${p.slot_no}: `) details += ', ';
                        details += p.new.is_locked
                            ? `{{ __('Lock (Pause Sale)') }}`
                            : `{{ __('Unlock') }}`;
                    }

                    return details;
                }
                return '';
            },

            formatTime(dateStr) {
                if (!dateStr) return '--';
                const date = new Date(dateStr);
                const now = new Date();
                const diffSeconds = Math.floor((now - date) / 1000);

                if (diffSeconds < 0) return date.toISOString().split('T')[0];
                if (diffSeconds < 60) return "{{ __('Just now') }}";

                const diffMinutes = Math.floor(diffSeconds / 60);
                if (diffMinutes < 60) return diffMinutes + " {{ __('mins ago') }}";

                const diffHours = Math.floor(diffMinutes / 60);
                if (diffHours < 24) return diffHours + " {{ __('hours ago') }}";

                return date.toISOString().split('T')[0] + ' ' + date.toTimeString().split(' ')[0].substring(0, 5);
            },

            translateNote(note) {
                if (!note) return '';
                const translations = {
                    'Superseded by new adjustment': {{ Js::from(__('Superseded by new adjustment')) }},
                    'Superseded by new command': {{ Js::from(__('Superseded by new command')) }}
                };
                return translations[note] || note;
            }
        };
    };
</script>

<div class="space-y-2 pb-20" x-data="stockApp('{{ $selectedMachine ? $selectedMachine->id : '' }}')"
    @keydown.escape.window="showEditModal = false">

    <!-- Header -->
    <x-page-header :title="__($title ?? 'Stock & Expiry Management')"
        :subtitle="__($subtitle ?? 'Manage inventory and monitor expiry dates across all machines')">
        <x-slot:back>
            <template x-if="viewMode === 'detail'">
                <button @click="backToList()"
                    class="p-2 sm:p-2.5 rounded-xl bg-white dark:bg-slate-800 text-slate-500 border border-slate-200/60 dark:border-slate-700/60 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all active:scale-95 shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </button>
            </template>
        </x-slot:back>
    </x-page-header>

    <!-- Tab Navigation (Only visible when not in specific machine detail) -->
    <template x-if="viewMode !== 'detail'">
        <x-tab-nav model="viewMode">
            <x-tab-nav-item value="history" :label="__('Operation Records')" model="viewMode" />
            <x-tab-nav-item value="list" :label="__('Adjust Stock & Expiry')" model="viewMode" />
        </x-tab-nav>
    </template>

    <div class="mt-6">

        <!-- History View: Operation Records -->
        <div x-show="viewMode === 'history'" x-cloak>
            <div class="space-y-6 animate-luxury-in">
                <div class="luxury-card rounded-3xl p-8 overflow-hidden relative">
                    <!-- Spinner Overlay -->
                    <div x-show="tabLoading" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 z-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] flex flex-col items-center justify-center"
                        x-cloak>
                        <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                            <div
                                class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin">
                            </div>
                            <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin"
                                style="animation-duration: 3s; direction: reverse;"></div>
                            <div class="relative w-8 h-8 flex items-center justify-center">
                                <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                        </div>
                        <p
                            class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
                            {{ __('Loading Data') }}...</p>
                    </div>

                    <!-- AJAX 可替換區域 -->
                    <div
                        :class="tabLoading ? 'opacity-30 pointer-events-none transition-opacity duration-300' : 'transition-opacity duration-300'">
                        <div x-ref="historyContent">
                            @include('admin.remote.partials.tab-history', ['history' => $history])
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Master View: Machine List -->
        <div x-show="viewMode === 'list'" x-cloak>
            <div class="space-y-6 animate-luxury-in">
                <div class="luxury-card rounded-3xl p-8 overflow-hidden relative">
                    <!-- AJAX Spinner (Machine List) -->
                    <div x-show="tabLoading" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 z-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] flex flex-col items-center justify-center"
                        x-cloak>
                        <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                            <div
                                class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin">
                            </div>
                            <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin"
                                style="animation-duration: 3s; direction: reverse;"></div>
                            <div class="relative w-8 h-8 flex items-center justify-center">
                                <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                        </div>
                        <p
                            class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
                            {{ __('Loading Data') }}...</p>
                    </div>

                    <!-- Filters Area -->
                    <div class="flex items-center justify-between mb-8">
                        <form @submit.prevent="searchInTab('machines')" class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            <input type="text" x-model="machineSearch" placeholder="{{ __('Search machines...') }}"
                                class="luxury-input py-2.5 pl-12 pr-6 block w-72">
                        </form>
                    </div>

                    <div
                        :class="tabLoading ? 'opacity-30 pointer-events-none transition-opacity duration-300' : 'transition-opacity duration-300'">
                        <div x-ref="machinesContent">
                            @include('admin.remote.partials.tab-machines', ['machines' => $machines])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail View: Cabinet Management -->
        <div x-show="viewMode === 'detail'" x-cloak>
            <div class="space-y-8 animate-luxury-in">

                <!-- Machine Header Info -->
                <div
                    class="luxury-card rounded-[2.5rem] p-8 md:p-10 flex flex-col md:flex-row md:items-center justify-between gap-8 border border-slate-200/60 dark:border-slate-800/60">
                    <div class="flex items-center gap-8">
                        <div
                            class="w-24 h-24 rounded-3xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 overflow-hidden shadow-inner">
                            <template x-if="selectedMachine?.image_urls && selectedMachine?.image_urls[0]">
                                <img :src="selectedMachine.image_urls[0]" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!selectedMachine?.image_urls || !selectedMachine?.image_urls[0]">
                                <svg class="w-12 h-12 stroke-[1.2]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </template>
                        </div>
                        <div>
                            <h1 x-text="selectedMachine?.name"
                                class="text-4xl font-black text-slate-800 dark:text-white tracking-tighter leading-tight">
                            </h1>
                            <div class="flex items-center gap-4 mt-3">
                                <span x-text="selectedMachine?.serial_no"
                                    class="px-3 py-1 rounded-lg bg-cyan-500/10 text-cyan-500 text-xs font-mono font-bold uppercase tracking-widest border border-cyan-500/20"></span>
                                <div
                                    class="flex items-center gap-2 text-slate-400 uppercase tracking-widest text-[10px] font-black">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span x-text="selectedMachine?.location || '{{ __('No Location') }}'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-5">
                        <div
                            class="px-7 py-4 rounded-[1.75rem] bg-slate-50 dark:bg-slate-800/50 flex flex-col items-center min-w-[120px] border border-slate-100 dark:border-slate-800/50">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{
                                __('Total Slots') }}</span>
                            <span class="text-3xl font-black text-slate-700 dark:text-slate-200"
                                x-text="slots.length"></span>
                        </div>
                        <div
                            class="px-7 py-4 rounded-[1.75rem] bg-rose-500/5 border border-rose-500/10 flex flex-col items-center min-w-[120px]">
                            <span class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-1">{{ __('Low Stock') }}</span>
                            <span class="text-3xl font-black text-rose-600"
                                x-text="slots.filter(s => s != null && s.max_stock > 0 && s.stock <= (s.max_stock * 0.2)).length"></span>
                        </div>
                    </div>
                </div>

                <!-- Cabinet Visualization Grid -->
                <div class="space-y-6">
                    <!-- Cabinet Control & Display Toggle -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-4">
                        <div class="flex items-center gap-6">
                            <button @click="displayMode = 'table'"
                                :class="displayMode === 'table' ? 'text-cyan-500 bg-cyan-500/10' : 'text-slate-400 hover:text-slate-600'"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-black text-sm uppercase tracking-widest">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                {{ __('Table View') }}
                            </button>
                            <button @click="displayMode = 'grid'"
                                :class="displayMode === 'grid' ? 'text-cyan-500 bg-cyan-500/10' : 'text-slate-400 hover:text-slate-600'"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl transition-all font-black text-sm uppercase tracking-widest">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                {{ __('Grid View') }}
                            </button>
                        </div>

                        <div class="flex items-center gap-8">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3.5 h-3.5 rounded-full bg-rose-500 shadow-lg shadow-rose-500/30"></span>
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">{{
                                    __('Expired') }}</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="w-3.5 h-3.5 rounded-full bg-amber-500 shadow-lg shadow-amber-500/30"></span>
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">{{
                                    __('Low Stock') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Grid View Container -->
                    <div x-show="displayMode === 'grid'" class="animate-luxury-in" x-cloak>
                        <div
                            class="luxury-card rounded-[2.5rem] p-8 border border-slate-200/50 dark:border-slate-800/50 bg-white/30 dark:bg-slate-900/40 backdrop-blur-xl relative overflow-hidden min-h-[500px]">
                            <!-- Loading Overlay -->
                            <div x-show="loading"
                                class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md z-20 flex items-center justify-center transition-all duration-500">
                                <div class="flex flex-col items-center gap-6">
                                    <div
                                        class="w-16 h-16 border-4 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin">
                                    </div>
                                    <span
                                        class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.3em] ml-2 animate-pulse">{{
                                        __('Loading Cabinet...') }}</span>
                                </div>
                            </div>

                            <!-- Background Decorative Grid -->
                            <div class="absolute inset-0 opacity-[0.05] pointer-events-none"
                                style="background-image: radial-gradient(#00d2ff 1.2px, transparent 1.2px); background-size: 40px 40px;">
                            </div>

                            <!-- Slots Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 relative z-10"
                                x-show="!loading">
                                <template x-for="slot in slots" :key="slot.id">
                                    <div @click="openEdit(slot)"
                                        :class="[getSlotColorClass(slot), slot.is_pending ? 'opacity-50 cursor-not-allowed grayscale-[0.5]' : 'cursor-pointer hover:scale-[1.08] hover:-translate-y-3 hover:shadow-2xl active:scale-[0.98]']"
                                        class="min-h-[300px] rounded-[2.5rem] p-5 flex flex-col items-center justify-center border-2 transition-all duration-500 group relative">

                                        <!-- Pending Overlay -->
                                        <template x-if="slot.is_pending">
                                            <div
                                                class="absolute inset-0 z-30 flex items-center justify-center bg-white/10 dark:bg-slate-900/10 backdrop-blur-[1px] rounded-[2.5rem]">
                                                <div class="flex flex-col items-center gap-2">
                                                    <div
                                                        class="w-8 h-8 border-2 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin">
                                                    </div>
                                                    <span
                                                        class="text-[8px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-widest">{{
                                                        __('Syncing') }}</span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Slot Header (Pinned to top) -->
                                        <div class="absolute top-4 left-5 right-5 flex justify-between items-center z-20">
                                            <div
                                                class="px-2.5 py-1 rounded-xl bg-slate-900/10 dark:bg-white/10 backdrop-blur-md border border-slate-900/5 dark:border-white/10 flex-shrink-0">
                                                <span
                                                    class="text-xs font-black uppercase tracking-tighter text-slate-800 dark:text-white"
                                                    x-text="slot.slot_no"></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <template x-if="slot.is_locked">
                                                    <div
                                                        class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-rose-500 text-white text-[9px] font-black uppercase tracking-widest shadow-lg shadow-rose-500/30 whitespace-nowrap select-none">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                        {{ __('Sale Paused') }}
                                                    </div>
                                                </template>
                                                <template x-if="slot.max_stock > 0 && slot.stock <= (slot.max_stock * 0.2)">
                                                    <div
                                                        class="px-2.5 py-1.5 rounded-xl bg-rose-500 text-white text-[9px] font-black uppercase tracking-widest shadow-lg shadow-rose-500/30 animate-pulse whitespace-nowrap select-none">
                                                        {{ __('Low') }}
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Product Image -->
                                        <div class="relative w-20 h-20 mb-4 mt-1">
                                            <div
                                                class="absolute inset-0 rounded-[2rem] bg-white/20 dark:bg-slate-900/40 backdrop-blur-xl border border-white/30 dark:border-white/5 shadow-inner group-hover:scale-105 transition-transform duration-500 overflow-hidden">
                                                <template x-if="slot.product && slot.product.image_url">
                                                    <img :src="slot.product.image_url"
                                                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                                </template>
                                                <template x-if="!slot.product">
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-8 h-8 opacity-20" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                        </svg>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Slot Info -->
                                        <div class="text-center w-full space-y-3">
                                            <template x-if="slot.product">
                                                <div>
                                                    <div class="text-base font-black truncate w-full opacity-90 tracking-tight"
                                                        x-text="slot.product.name"></div>
                                                    <template x-if="slot.product.id">
                                                        <div class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">{{ __('ID') }}: <span x-text="slot.product.id"></span></div>
                                                    </template>
                                                </div>
                                            </template>

                                            <div class="space-y-3">
                                                <!-- Stock Level -->
                                                <div class="flex flex-col items-center">
                                                    <div class="flex items-baseline gap-1">
                                                        <span class="text-2xl font-black tracking-tighter leading-none"
                                                            x-text="slot.stock"></span>
                                                        <span class="text-xs font-black opacity-30">/</span>
                                                        <span class="text-sm font-bold opacity-50"
                                                            x-text="slot.max_stock || 10"></span>
                                                    </div>
                                                </div>

                                                <!-- Expiry Date -->
                                                <div class="flex flex-col items-center">
                                                    <span
                                                        class="text-base font-black tracking-tight leading-none opacity-80"
                                                        x-text="slot.expiry_date ? slot.expiry_date.replace(/-/g, '/') : '----/--/--'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Table View Container -->
                    <div x-show="displayMode === 'table'" class="animate-luxury-in relative min-h-[500px]" x-cloak>
                        <!-- Loading Overlay -->
                        <div x-show="loading"
                            class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md z-20 flex items-center justify-center transition-all duration-500 rounded-[2.5rem]">
                            <div class="flex flex-col items-center gap-6">
                                <div
                                    class="w-16 h-16 border-4 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin">
                                </div>
                                <span
                                    class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.3em] ml-2 animate-pulse">{{
                                    __('Loading Cabinet...') }}</span>
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden xl:block luxury-card rounded-[2.5rem] overflow-hidden border-slate-200/50 dark:border-slate-800/50 shadow-xl hover:translate-y-0 transition-transform duration-300" x-show="!loading">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900/50">
                                        <th class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Slot') }}</th>
                                        <th class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Product') }}</th>
                                        <th class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em]">{{ __('Barcode') }}</th>
                                        <th class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em] text-center">{{ __('Stock Level') }} / {{ __('Max Stock') }}</th>
                                        <th class="px-8 py-5 text-sm font-black text-slate-400 uppercase tracking-[0.2em] text-right">{{ __('Expiry') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/40">
                                    <template x-for="slot in slots" :key="slot.id">
                                        <tr @click="openEdit(slot)"
                                            :class="slot.is_pending ? 'opacity-50 cursor-not-allowed grayscale-[0.5]' : 'cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-800/30'"
                                            class="transition-colors group">
                                            <td class="px-8 py-6">
                                                <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-sm font-black text-slate-600 dark:text-slate-300"
                                                    x-text="slot.slot_no"></span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 flex items-center justify-center overflow-hidden">
                                                        <template x-if="slot.product?.image_url">
                                                            <img :src="slot.product.image_url" class="w-full h-full object-cover">
                                                        </template>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-base font-black text-slate-800 dark:text-slate-200"
                                                                x-text="slot.product?.name || '{{ __('Empty Slot') }}'"></span>
                                                            <template x-if="slot.is_locked">
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-500 text-[10px] font-black uppercase tracking-wider whitespace-nowrap">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                                    {{ __('Locked (Sale Paused)') }}
                                                                </span>
                                                            </template>
                                                        </div>
                                                        <template x-if="slot.product?.id">
                                                            <div class="mt-1">
                                                                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 font-mono tracking-widest uppercase">{{ __('ID') }}: <span x-text="slot.product.id"></span></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6">
                                                <span class="text-sm font-mono font-bold text-slate-400"
                                                    x-text="slot.product?.barcode || '--'"></span>
                                            </td>
                                            <td class="px-8 py-6 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <span class="text-lg font-black"
                                                        :class="(slot.max_stock > 0 && slot.stock <= (slot.max_stock * 0.2)) ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200'"
                                                        x-text="slot.stock"></span>
                                                    <span class="text-xs font-bold text-slate-300">/</span>
                                                    <span class="text-sm font-bold text-slate-400" x-text="slot.max_stock"></span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6 text-right">
                                                <span class="text-sm font-mono font-bold text-slate-500"
                                                    x-text="slot.expiry_date ? slot.expiry_date : '----/--/--'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View for Slots -->
                        <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6" x-show="!loading">
                            <template x-for="slot in slots" :key="slot.id">
                                <div @click="openEdit(slot)"
                                    :class="[getSlotCardClass(slot), slot.is_pending ? 'opacity-50 cursor-not-allowed grayscale-[0.5]' : 'cursor-pointer hover:scale-[1.02] active:scale-[0.98]']"
                                    class="transition-all duration-300 group">
                                    <div class="flex items-start justify-between gap-4 mb-6">
                                        <div class="flex items-center gap-4 min-w-0">
                                            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                                                <template x-if="slot.product?.image_url">
                                                    <img :src="slot.product.image_url" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!slot.product?.image_url">
                                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </template>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-500 dark:text-slate-400 tracking-tighter" x-text="slot.slot_no"></span>
                                                    <h3 class="text-base font-black text-slate-800 dark:text-slate-100 truncate group-hover:text-cyan-600 transition-colors tracking-tight" x-text="slot.product?.name || '{{ __('Empty Slot') }}'"></h3>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-x-2 mt-1">
                                                    <template x-if="slot.product?.id">
                                                        <span class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('ID') }}: <span x-text="slot.product.id"></span></span>
                                                    </template>
                                                    <span class="text-[10px] text-slate-300 dark:text-slate-700">|</span>
                                                    <span class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate" x-text="slot.product?.barcode || '--'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <template x-if="slot.is_locked">
                                                <span class="inline-flex items-center gap-1 text-[10px] font-black px-2.5 py-1.5 rounded-lg bg-rose-500/10 text-rose-500 uppercase tracking-wider whitespace-nowrap">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                    {{ __('Sale Paused') }}
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 mb-2 border-t border-slate-100 dark:border-slate-800/50 pt-4">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Stock Level') }}</p>
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-lg font-black" :class="(slot.max_stock > 0 && slot.stock <= (slot.max_stock * 0.2)) ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200'" x-text="slot.stock"></span>
                                                <span class="text-[10px] font-bold text-slate-300">/</span>
                                                <span class="text-xs font-bold text-slate-400" x-text="slot.max_stock"></span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Expiry') }}</p>
                                            <p class="text-xs font-bold text-slate-500" x-text="slot.expiry_date || '--'"></p>
                                        </div>
                                    </div>

                                    <!-- Stock Progress Bar in card -->
                                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200/50 dark:border-slate-700/50 shadow-inner">
                                        <div :class="getSlotProgressClass(slot)"
                                            class="h-full transition-all duration-1000 ease-out rounded-full"
                                            :style="'width: ' + Math.min(100, Math.round((slot.stock / (slot.max_stock || 10)) * 100)) + '%'">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integrated Edit Modal -->
        <div x-show="showEditModal" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                    @click="showEditModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full overflow-visible animate-luxury-in"
                    @click.away="showEditModal = false">

                    <!-- Modal Header -->
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3
                                class="text-3xl font-black text-slate-800 dark:text-white font-display tracking-tight leading-none">
                                {{ __('Edit Slot') }} <span x-text="selectedSlot?.slot_no || ''"
                                    class="text-cyan-500"></span>
                            </h3>
                            <template x-if="selectedSlot && selectedSlot.product">
                                <p class="text-base font-black text-slate-400 uppercase tracking-widest mt-3 ml-0.5">
                                    <span x-text="selectedSlot?.product?.name"></span>
                                    <template x-if="selectedSlot?.product?.id">
                                        <span class="font-mono text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1.5">(ID: <span x-text="selectedSlot.product.id"></span>)</span>
                                    </template>
                                </p>
                            </template>
                        </div>
                        <button @click="showEditModal = false"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form Controls -->
                    <div class="space-y-6">
                        <!-- Stock Count Widget -->
                        <div class="space-y-2">
                            <label class="text-base font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                __('Stock Quantity') }}</label>
                            <div
                                class="flex items-center gap-4 bg-slate-50 dark:bg-slate-900/50 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                                <div class="flex-1">
                                    <input type="number" x-model.number="formData.stock" min="0"
                                        :max="selectedSlot ? selectedSlot.max_stock : 99"
                                        class="w-full bg-transparent border-none p-0 text-5xl font-black text-slate-800 dark:text-white focus:ring-0 placeholder-slate-200">
                                    <div class="text-sm font-black text-slate-400 mt-2 uppercase tracking-wider pl-0.5">
                                        {{ __('Max Capacity:') }} <span class="text-slate-600 dark:text-slate-300"
                                            x-text="selectedSlot?.max_stock || 0"></span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="formData.stock = 0"
                                        class="px-5 py-3 rounded-lg bg-white dark:bg-slate-800 text-slate-400 hover:text-rose-500 border border-slate-200 dark:border-slate-700 transition-all text-sm font-black uppercase tracking-widest active:scale-95 shadow-sm">
                                        {{ __('Clear') }}
                                    </button>
                                    <button @click="formData.stock = selectedSlot?.max_stock || 0"
                                        class="px-5 py-3 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-500 hover:text-white border border-cyan-500/20 transition-all text-sm font-black uppercase tracking-widest active:scale-95 shadow-sm">
                                        {{ __('Max') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Expiry Date -->
                            <div class="space-y-2">
                                <label class="text-sm font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Expiry Date') }}</label>
                                <input type="date" x-model="formData.expiry_date" class="luxury-input w-full py-4 px-5">
                            </div>

                            <!-- Batch Number -->
                            <div class="space-y-2">
                                <label class="text-sm font-black text-slate-500 uppercase tracking-widest pl-1">{{
                                    __('Batch Number') }}</label>
                                <input type="text" x-model="formData.batch_no" placeholder="B2026-XXXX"
                                    class="luxury-input w-full py-4 px-5">
                            </div>
                        </div>

                        <!-- Slot Lock (暫停販售)：遠端鎖定/解鎖此貨道，與機台現場雙向同步 -->
                        <div
                            class="mt-6 flex items-center justify-between rounded-2xl border border-slate-100 dark:border-slate-800/50 px-5 py-4">
                            <div>
                                <div class="text-sm font-black text-slate-500 uppercase tracking-widest">{{
                                    __('Sale Status') }}</div>
                                <div class="mt-1 text-xs font-bold"
                                    :class="selectedSlot?.is_locked ? 'text-rose-500' : 'text-emerald-500'"
                                    x-text="selectedSlot?.is_locked ? '{{ __('Locked (Sale Paused)') }}' : '{{ __('On Sale') }}'">
                                </div>
                            </div>
                            <button type="button" @click="toggleLock()" :disabled="updating"
                                class="px-6 py-3 rounded-xl font-bold transition disabled:opacity-50"
                                :class="selectedSlot?.is_locked ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20' : 'bg-rose-500/10 text-rose-600 hover:bg-rose-500/20'"
                                x-text="selectedSlot?.is_locked ? '{{ __('Unlock') }}' : '{{ __('Lock (Pause Sale)') }}'">
                            </button>
                        </div>

                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-x-4 mt-10 pt-8 border-t border-slate-100 dark:border-slate-800/50">
                        <button type="button" @click="showEditModal = false" class="btn-luxury-ghost px-8">{{
                            __('Cancel') }}</button>
                        <button type="button" @click="saveChanges()" :disabled="updating"
                            class="btn-luxury-primary px-12 min-w-[160px]">
                            <div x-show="updating"
                                class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin mr-3">
                            </div>
                            <span x-text="updating ? '{{ __('Saving...') }}' : '{{ __('Confirm Changes') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
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

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

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
    </style>
    @endsection