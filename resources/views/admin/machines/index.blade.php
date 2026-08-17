@extends('layouts.admin')


@section('content')
<script>
    window.machineApp = function () {
        return {
            showLogPanel: false,
            showEditModal: false,
            showInventoryPanel: false,
            showResolveConfirm: false,
            editMachineId: '',
            editMachineName: '',
            editAmbientTempSetting: '',
            editAmbientTempMonitoringEnabled: false,
            activeTab: 'status',
            currentMachineId: '',
            isLoadingTable: false,
            async fetchPage(url) {
                if (!url || this.isLoadingTable) return;
                this.isLoadingTable = true;
                try {
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const html = await res.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.querySelector('#ajax-content-container');
                    if (newContent) {
                        document.querySelector('#ajax-content-container').innerHTML = newContent.innerHTML;
                        window.history.pushState({}, '', url);

                        // Re-initialize Preline UI components
                        if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
                            window.HSStaticMethods.autoInit();
                        }
                    }
                } catch (e) {
                    console.error('AJAX Load Failed:', e);
                } finally {
                    this.isLoadingTable = false;
                }
            },
            currentMachineSn: '',
            currentMachineName: '',
            currentMachineAmbientTempEnabled: false,
            currentMachineCardTerminalEnabled: false,
            logs: [],
            loading: false,
            inventoryLoading: false,
            startDate: '',
            endDate: '',
            selectedLevel: '',
            tab: 'list',
            viewMode: 'fleet',
            selectedMachine: null,
            slots: [],
            inventorySlots: [],
            currentPage: 1,
            lastPage: 1,
            totalLogs: 0,

            temperatureChart: null,
            ambientTemperatureChart: null,

            init() {
                const now = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                const formatDate = (date, time) => `${date.getFullYear()}/${pad(date.getMonth() + 1)}/${pad(date.getDate())} ${time}`;

                this.startDate = formatDate(now, '00:00');
                this.endDate = formatDate(now, '23:59');
                this.$watch('activeTab', (val) => {
                    this.fetchLogs(1);
                    if (val === 'status') {
                        this.fetchTemperatureData();
                    } else if (val === 'ambient_temp') {
                        this.fetchAmbientTemperatureData();
                    }
                });

                // 監聽日期變動，同步更新圖表 (僅在當前為狀態分頁時)
                this.$watch('startDate', () => { 
                    if(this.activeTab === 'status') this.fetchTemperatureData(); 
                    if(this.activeTab === 'ambient_temp') this.fetchAmbientTemperatureData(); 
                });
                this.$watch('endDate', () => { 
                    if(this.activeTab === 'status') this.fetchTemperatureData(); 
                    if(this.activeTab === 'ambient_temp') this.fetchAmbientTemperatureData(); 
                });
            },

            async openLogPanel(id, sn, name, ambientTempEnabled = false, cardTerminalEnabled = false) {
                this.currentMachineId = id;
                this.currentMachineSn = sn;
                this.currentMachineName = name;
                this.currentMachineAmbientTempEnabled = ambientTempEnabled === true || ambientTempEnabled === 1 || ambientTempEnabled === '1' || ambientTempEnabled === 'true';
                this.currentMachineCardTerminalEnabled = cardTerminalEnabled === true || cardTerminalEnabled === 1 || cardTerminalEnabled === '1' || cardTerminalEnabled === 'true';
                // 若刷卡機分頁不可見且當前正停在該分頁，退回機台狀態分頁
                if (!this.currentMachineCardTerminalEnabled && this.activeTab === 'card_terminal') {
                    this.activeTab = 'status';
                }
                this.slots = [];
                this.showLogPanel = true;
                this.activeTab = 'status';
                await this.fetchLogs();
                await this.fetchTemperatureData();
            },

            async fetchTemperatureData() {
                if (this.activeTab !== 'status') return;
                try {
                    let url = `/admin/machines/${this.currentMachineId}/temperature-ajax?`;
                    if (this.startDate) url += '&start_date=' + this.startDate;
                    if (this.endDate) url += '&end_date=' + this.endDate;

                    const res = await fetch(url);
                    const data = await res.json();
                    if (data.success) {
                        this.initTemperatureChart(data.data);
                    }
                } catch (e) { console.error('fetchTemperatureData error:', e); }
            },

            async fetchAmbientTemperatureData() {
                if (this.activeTab !== 'ambient_temp') return;
                try {
                    let url = `/admin/machines/${this.currentMachineId}/ambient-temperature-ajax?`;
                    if (this.startDate) url += '&start_date=' + this.startDate;
                    if (this.endDate) url += '&end_date=' + this.endDate;

                    const res = await fetch(url);
                    const data = await res.json();
                    if (data.success) {
                        this.initAmbientTemperatureChart(data.data);
                    }
                } catch (e) { console.error('fetchAmbientTemperatureData error:', e); }
            },

            initAmbientTemperatureChart(chartData) {
                this.$nextTick(() => {
                    const chartEl = document.querySelector("#ambient-temperature-chart");
                    if (!chartEl) return;

                    const isDark = document.documentElement.classList.contains('dark');

                    const options = {
                        series: [{
                            name: "{{ __('Ambient Temperature') }}",
                            data: chartData
                        }],
                        chart: {
                            id: 'ambient-temp-chart',
                            type: 'area',
                            height: 200,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            animations: { enabled: false },
                            background: 'transparent',
                            accessibility: { enabled: false }
                        },
                        colors: ['#3b82f6'],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.45,
                                opacityTo: 0.05,
                                stops: [20, 100]
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        grid: {
                            borderColor: isDark ? '#1e293b' : '#f1f5f9',
                            strokeDashArray: 4,
                            padding: { left: 10, right: 10 }
                        },
                        xaxis: {
                            type: 'datetime',
                            labels: {
                                show: true,
                                style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
                                datetimeUTC: false,
                                format: 'HH:mm',
                                hideOverlappingLabels: true,
                            },
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                            tooltip: { enabled: false },
                            tickAmount: 6
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
                                formatter: (val) => val + '°C'
                            }
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            x: { 
                                show: true,
                                format: 'yyyy/MM/dd HH:mm:ss'
                            },
                            y: {
                                title: {
                                    formatter: (seriesName) => seriesName + ': '
                                }
                            }
                        }
                    };

                    if (this.ambientTemperatureChart) {
                        this.ambientTemperatureChart.updateOptions(options);
                    } else {
                        this.ambientTemperatureChart = new ApexCharts(chartEl, options);
                        this.ambientTemperatureChart.render();
                    }
                });
            },

            initTemperatureChart(chartData) {
                this.$nextTick(() => {
                    const chartEl = document.querySelector("#temperature-chart");
                    if (!chartEl) return;

                    const isDark = document.documentElement.classList.contains('dark');

                    const options = {
                        series: [{
                            name: "{{ __('Temperature') }}",
                            data: chartData
                        }],
                        chart: {
                            id: 'temp-chart',
                            type: 'area',
                            height: 200,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            animations: { enabled: false }, // 關閉動畫以減少渲染負擔
                            background: 'transparent',
                            accessibility: { enabled: false } // 隱藏無障礙輔助文字
                        },
                        colors: ['#06b6d4'],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.45,
                                opacityTo: 0.05,
                                stops: [20, 100]
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        grid: {
                            borderColor: isDark ? '#1e293b' : '#f1f5f9',
                            strokeDashArray: 4,
                            padding: { left: 10, right: 10 }
                        },
                        xaxis: {
                            type: 'datetime',
                            labels: {
                                show: true,
                                style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
                                datetimeUTC: false,
                                format: 'HH:mm',
                                hideOverlappingLabels: true,
                            },
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                            tooltip: { enabled: false },
                            tickAmount: 6 // 強制限制標籤數量避免擁擠
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
                                formatter: (val) => val + '°C'
                            }
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            x: { 
                                show: true,
                                format: 'yyyy/MM/dd HH:mm:ss'
                            },
                            y: {
                                title: {
                                    formatter: (seriesName) => seriesName + ': '
                                }
                            }
                        }
                    };

                    if (this.temperatureChart) {
                        this.temperatureChart.updateOptions(options);
                    } else {
                        this.temperatureChart = new ApexCharts(chartEl, options);
                        this.temperatureChart.render();
                    }
                });
            },

            openEditModal(id, name, ambientTempSetting = '', ambientTempMonitoringEnabled = false) {
                this.editMachineId = id;
                this.editMachineName = name;
                this.editAmbientTempSetting = ambientTempSetting;
                this.editAmbientTempMonitoringEnabled = ambientTempMonitoringEnabled === true || ambientTempMonitoringEnabled === 1 || ambientTempMonitoringEnabled === '1' || ambientTempMonitoringEnabled === 'true';
                this.showEditModal = true;
            },


            async fetchLogs(page = 1) {
                this.loading = true;
                this.currentPage = page;
                try {
                    let url = '/admin/machines/' + this.currentMachineId + '/logs-ajax?type=' + this.activeTab + '&page=' + page;
                    if (this.startDate) url += '&start_date=' + this.startDate;
                    if (this.endDate) url += '&end_date=' + this.endDate;
                    if (this.selectedLevel) url += '&level=' + this.selectedLevel;

                    const res = await fetch(url);
                    const data = await res.json();
                    if (data.success) {
                        this.logs = data.data || [];
                        this.currentPage = data.pagination.current_page;
                        this.lastPage = data.pagination.last_page;
                        this.totalLogs = data.pagination.total;
                    }
                } catch (e) { console.error('fetchLogs error:', e); }
                finally { this.loading = false; }
            },

            async openCabinet(id) {
                this.loading = true;
                this.viewMode = 'cabinet';
                try {
                    const res = await fetch('/admin/machines/' + id + '/slots-ajax');
                    const data = await res.json();
                    if (data.success) {
                        this.selectedMachine = data.machine;
                        this.slots = data.slots;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } catch (e) { console.error('openCabinet error:', e); }
                finally { this.loading = false; }
            },

            // 庫存一覽面板 (唯讀)
            async openInventoryPanel(id, sn, name) {
                this.currentMachineId = id;
                this.currentMachineSn = sn;
                this.currentMachineName = name;
                this.inventorySlots = [];
                this.showInventoryPanel = true;
                this.inventoryLoading = true;
                try {
                    const res = await fetch('/admin/machines/' + id + '/slots-ajax');
                    const data = await res.json();
                    if (data.success) {
                        this.inventorySlots = data.slots;
                    }
                } catch (e) { console.error('openInventoryPanel error:', e); }
                finally { this.inventoryLoading = false; }
            },

            confirmResolve() {
                this.showResolveConfirm = true;
            },

            async executeResolve() {
                this.showResolveConfirm = false;
                try {
                    const res = await fetch(`/admin/machines/${this.currentMachineId}/resolve-logs`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.showToast(data.message || "{{ __('All issues marked as resolved.') }}", 'success');
                        this.fetchLogs(1);
                        // Refresh the machine list background to update the status bulb
                        this.fetchPage(window.location.href);
                    } else {
                        window.showToast(data.message || "{{ __('Failed to resolve issues.') }}", 'error');
                    }
                } catch (e) {
                    console.error('resolveLogs error:', e);
                }
            },

            formatDateTime(dateStr) {
                if (!dateStr) return '--';
                const d = new Date(dateStr);
                const pad = (n) => String(n).padStart(2, '0');
                return `${d.getFullYear()}/${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
            },

            getSlotColorClass(slot) {
                if (!slot.expiry_date) return 'bg-slate-50/50 dark:bg-slate-800/50 text-slate-400 border-slate-200/60 dark:border-slate-700/50';
                const todayStr = new Date().toISOString().split('T')[0];
                const expiryStr = slot.expiry_date;
                if (expiryStr < todayStr) {
                    return 'bg-rose-50/60 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/30 shadow-sm shadow-rose-500/5';
                }
                const diffDays = Math.round((new Date(expiryStr) - new Date(todayStr)) / 86400000);
                if (diffDays <= 7) {
                    return 'bg-amber-50/60 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/30 shadow-sm shadow-amber-500/5';
                }
                return 'bg-emerald-50/60 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30 shadow-sm shadow-emerald-500/5';
            },

        };
    };
</script>

<div class="space-y-4 pb-20" x-data="machineApp()"
    @keydown.escape.window="showLogPanel = false; showInventoryPanel = false"
    @ajax:navigate.window.prevent="fetchPage($event.detail.url)" @submit="if($event.target.method.toLowerCase() === 'get') {
        $event.preventDefault();
        const url = new URL($event.target.action, window.location.origin);
        new FormData($event.target).forEach((value, key) => {
            if(value.trim()) url.searchParams.set(key, value);
            else url.searchParams.delete(key);
        });
        fetchPage(url.pathname + url.search);
    }">
    {{-- Page Header --}}
    <x-page-header :title="__('Machine List')" :subtitle="__('Manage your machine fleet and operational data')" />

    <!-- Main Card (Machine List) -->
    <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">
        <!-- Loading Spinner Overlay -->
        <x-luxury-spinner show="isLoadingTable" />

        <div id="ajax-content-container"
            :class="{ 'opacity-30 pointer-events-none transition-opacity duration-300': isLoadingTable }">

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.machines.index') }}"
                class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8"
                @submit.prevent="
                    const url = new URL($el.action, window.location.origin);
                    new FormData($el).forEach((value, key) => {
                        if (value.trim()) url.searchParams.set(key, value);
                        else url.searchParams.delete(key);
                    });
                    fetchPage(url.pathname + url.search);
                ">
                <input type="hidden" name="tab" value="list">

                <div class="relative group flex-1 sm:flex-none">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Search machines...') }}"
                        class="luxury-input py-2.5 pl-11 pr-4 block w-full sm:w-72 text-sm font-bold">
                </div>

                @if(auth()->user()->isSystemAdmin())
                <div class="w-full sm:w-72">
                    <x-searchable-select name="company_id" :options="$companies" :selected="request('company_id')"
                        :placeholder="__('All Companies')"
                        x-on:change="$el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }))" />
                </div>
                @endif

                <div class="flex items-center gap-2 ml-auto sm:ml-0">
                    <button type="submit"
                        class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
                        title="{{ __('Search') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    <button type="button"
                        @click="
                            $el.closest('form').querySelectorAll('input[type=text]').forEach(i => i.value = '');
                            $el.closest('form').querySelectorAll('select').forEach(s => {
                                s.value = ' ';
                                const instance = window.HSSelect?.getInstance(s);
                                if (instance) instance.setValue(' ');
                            });
                            $el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }));
                        "
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
                        title="{{ __('Reset') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Desktop Table View -->
            <div class="hidden xl:block overflow-x-auto pb-4">
                <table class="w-full text-left border-separate border-spacing-y-0 text-sm whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                                {{ __('Machine Information') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                                {{ __('Status') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                                {{ __('Temperature') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                                {{ __('Hardware Status') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                                {{ __('APP Version') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">
                                {{ __('Last Time') }}</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                                {{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                        @forelse ($machines as $machine)
                        <tr
                            class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                            <td class="px-6 py-6 cursor-pointer group"
                                @click="openLogPanel('{{ $machine->id }}', '{{ $machine->serial_no }}', '{{ addslashes($machine->name) }}', {{ $machine->ambient_temp_monitoring_enabled ? 1 : 0 }}, {{ $machine->show_card_terminal ? 1 : 0 }})">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm">
                                        @if(isset($machine->image_urls[0]))
                                        <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                                        @else
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div
                                            class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight flex items-center gap-2">
                                            <span>{{ $machine->name }}</span>
                                            @if($machine->has_login_warning)
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 uppercase tracking-wider tooltip shrink-0" title="{{ __('Login warning alert!') }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                                {{ __('Login Alert') }}
                                            </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span
                                                class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                                {{ $machine->serial_no ?: '--' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 text-center">
                                @php
                                $cStatus = $machine->calculated_status;
                                @endphp

                                @if($cStatus === 'online')
                                <div class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 tooltip mx-auto w-fit"
                                    title="{{ $machine->is_unstable ? __('Connection is unstable (disconnected :count times in the last hour)', ['count' => $machine->recent_disconnection_count]) : __('Machine connection is normal') }}">
                                    <div class="relative flex h-2 w-2">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </div>
                                    <span
                                        class="text-xs font-black text-emerald-600 dark:text-emerald-400 tracking-widest uppercase">{{
                                        __('Online') }}</span>
                                    
                                    @if($machine->is_unstable)
                                    <div class="ml-1 text-amber-500 animate-pulse">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    @endif
                                </div>
                                @elseif($cStatus === 'warning')
                                <div class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 tooltip mx-auto w-fit"
                                    title="{{ __('Connection warnings') }}">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></div>
                                        <span
                                            class="text-xs font-black text-amber-600 dark:text-amber-400 tracking-widest uppercase">{{
                                            __('Warning') }}</span>
                                    </div>
                                    @if($machine->latest_status_log_time)
                                    <span class="text-[9px] font-bold text-amber-500/80 block leading-none">{{ $machine->latest_status_log_time->diffForHumans() }}</span>
                                    @endif
                                </div>
                                @elseif($cStatus === 'error')
                                <div class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-full bg-rose-500/10 border border-rose-500/20 tooltip mx-auto w-fit"
                                    title="{{ __('Connection or system errors') }}">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></div>
                                        <span
                                            class="text-xs font-black text-rose-600 dark:text-rose-400 tracking-widest uppercase">{{
                                            __('Error') }}</span>
                                    </div>
                                    @if($machine->latest_status_log_time)
                                    <span class="text-[9px] font-bold text-rose-500/80 block leading-none">{{ $machine->latest_status_log_time->diffForHumans() }}</span>
                                    @endif
                                </div>
                                @else
                                <div class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-500/10 border border-slate-500/20 tooltip mx-auto w-fit"
                                    title="{{ __('No heartbeat for over 30 seconds') }}">
                                    <div class="h-2 w-2 rounded-full bg-slate-400"></div>
                                    <span
                                        class="text-xs font-black text-slate-500 dark:text-slate-400 tracking-widest uppercase">{{
                                        __('Offline') }}</span>
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-6 text-center">
                                <span class="font-mono font-bold text-slate-600 dark:text-slate-300">{{
                                    $machine->display_temperature ?? '--' }}°C</span>
                            </td>
                            <td class="px-6 py-6 text-center">
                                @php
                                    $hStatus = $machine->hardware_status;
                                @endphp
                                <div class="flex items-center justify-center gap-3">
                                    {{-- 下位機 --}}
                                    <div class="flex items-center gap-1.5 tooltip" title="{{ __('Sub-machine Status') }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{
                                            $hStatus === 'error' ? 'bg-rose-500 animate-pulse' :
                                            ($hStatus === 'warning' ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500/50')
                                        }}"></span>
                                        <span class="text-xs font-black {{
                                            $hStatus === 'error' ? 'text-rose-500' :
                                            ($hStatus === 'warning' ? 'text-amber-500' : 'text-slate-400')
                                        }} uppercase tracking-widest">
                                            {{ __('Sub-machine') }}
                                            @if($machine->latest_hardware_log_time && $hStatus !== 'normal')
                                            <span class="text-[9px] lowercase opacity-70 block -mt-0.5 ml-3 font-bold">{{ $machine->latest_hardware_log_time->diffForHumans() }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    {{-- 刷卡機 (信用卡/電子票證/手機支付共用同一台)：僅基礎版且啟用刷卡機才顯示 --}}
                                    @if($machine->show_card_terminal)
                                    @php $ctStatus = $machine->card_terminal_status; @endphp
                                    <div class="flex items-center gap-1.5 tooltip" title="{{ __('Card Terminal Status') }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{
                                            $ctStatus === 'error' ? 'bg-rose-500 animate-pulse' :
                                            ($ctStatus === 'warning' ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500/50')
                                        }}"></span>
                                        <span class="text-xs font-black {{
                                            $ctStatus === 'error' ? 'text-rose-500' :
                                            ($ctStatus === 'warning' ? 'text-amber-500' : 'text-slate-400')
                                        }} uppercase tracking-widest">
                                            {{ __('Card Terminal') }}
                                            @if($machine->latest_card_terminal_log_time && $ctStatus !== 'normal')
                                            <span class="text-[9px] lowercase opacity-70 block -mt-0.5 ml-3 font-bold">{{ $machine->latest_card_terminal_log_time->diffForHumans() }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td
                                class="px-6 py-6 font-mono text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center">
                                {{ $machine->firmware_version ?: '-' }}
                            </td>
                            <td
                                class="px-6 py-6 font-mono text-xs font-black text-slate-400 tracking-widest text-center">
                                {{ $machine->last_heartbeat_at ? $machine->last_heartbeat_at->format('Y-m-d H:i:s') :
                                '-' }}
                            </td>
                            <td class="px-6 py-6 text-right">
                                <div class="flex items-center justify-end gap-2">

                                    <button type="button"
                                        @click="openEditModal('{{ $machine->id }}', '{{ addslashes($machine->name) }}', '{{ $machine->ambient_temp_setting }}', '{{ $machine->ambient_temp_monitoring_enabled ? 1 : 0 }}')"
                                        class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all duration-200"
                                        title="{{ __('Edit Name') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button type="button"
                                        @click="openLogPanel('{{ $machine->id }}', '{{ $machine->serial_no }}', '{{ addslashes($machine->name) }}', {{ $machine->ambient_temp_monitoring_enabled ? 1 : 0 }}, {{ $machine->show_card_terminal ? 1 : 0 }})"
                                        class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all duration-200"
                                        title="{{ __('View Logs') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.67 8.5 7.652 6 12 6c4.348 0 8.332 2.5 9.964 5.678a1.012 1.012 0 0 1 0 .644C20.33 15.5 16.348 18 12 18c-4.348 0-8.332-2.5-9.964-5.678z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                        </svg>
                                    </button>
                                    <a href="{{ route('admin.machines.pricing', $machine) }}"
                                        class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 dark:hover:bg-amber-500/10 border border-transparent hover:border-amber-500/20 transition-all duration-200"
                                        title="{{ __('Machine Pricing') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <x-empty-state mode="table" :colspan="7" :message="__('No machines found')">
                            <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9.75 17 9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                            </svg>
                        </x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile/Tablet Card View -->
            <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse ($machines as $machine)
                <div
                    class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div class="flex items-center gap-4 min-w-0">
                            <div
                                class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                                @if(isset($machine->image_urls[0]))
                                <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                                @else
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 @click="openLogPanel('{{ $machine->id }}', '{{ $machine->serial_no }}', '{{ addslashes($machine->name) }}', {{ $machine->ambient_temp_monitoring_enabled ? 1 : 0 }}, {{ $machine->show_card_terminal ? 1 : 0 }})"
                                    class="text-base font-extrabold text-slate-800 dark:text-slate-100 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight cursor-pointer flex items-center gap-2 min-w-0">
                                    <span class="truncate">{{ $machine->name }}</span>
                                    @if($machine->has_login_warning)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 uppercase tracking-wider tooltip shrink-0" title="{{ __('Login warning alert!') }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                        {{ __('Login Alert') }}
                                    </span>
                                    @endif
                                </h3>
                                <p
                                    class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                                    {{ $machine->serial_no ?: '--' }}
                                </p>
                            </div>
                        </div>

                        @php
                        $cStatus = $machine->calculated_status;
                        @endphp
                        @if($cStatus === 'online')
                        <div class="flex h-2.5 w-2.5 relative">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </div>
                        @elseif($cStatus === 'warning')
                        <div class="h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse"></div>
                        @elseif($cStatus === 'error')
                        <div class="h-2.5 w-2.5 rounded-full bg-rose-500 animate-pulse"></div>
                        @else
                        <div class="h-2.5 w-2.5 rounded-full bg-slate-400"></div>
                        @endif
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                        <div>
                            <p
                                class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                                {{ __('Temperature') }}</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">{{
                                $machine->display_temperature ?? '--' }}°C</p>
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                                {{ __('APP Version') }}</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">{{
                                $machine->firmware_version ?: '-' }}</p>
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                                {{ __('Status') }}
                            </p>
                            <x-status-badge
                                :status="$cStatus === 'online' ? 'online' : ($cStatus === 'error' ? 'error' : 'offline')"
                                size="sm" />
                            @if($machine->latest_status_log_time && in_array($cStatus, ['error', 'warning']))
                            <p class="text-[9px] font-bold text-slate-400 mt-1">{{ $machine->latest_status_log_time->diffForHumans() }}</p>
                            @endif
                        </div>
                        <div>
                            <p
                                class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                                {{ __('Hardware Status') }}</p>
                            @php $hStatus = $machine->hardware_status; @endphp
                            <div class="flex flex-col gap-1.5">
                                {{-- 下位機 --}}
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full {{
                                        $hStatus === 'error' ? 'bg-rose-500 animate-pulse' :
                                        ($hStatus === 'warning' ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500/50')
                                    }}"></span>
                                    <span class="text-xs font-black {{
                                        $hStatus === 'error' ? 'text-rose-500' :
                                        ($hStatus === 'warning' ? 'text-amber-500' : 'text-slate-400')
                                    }} uppercase tracking-widest">{{ __('Sub-machine') }}</span>
                                </div>
                                {{-- 刷卡機 (信用卡/電子票證/手機支付共用同一台)：僅基礎版且啟用刷卡機才顯示 --}}
                                @if($machine->show_card_terminal)
                                @php $ctStatus = $machine->card_terminal_status; @endphp
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full {{
                                        $ctStatus === 'error' ? 'bg-rose-500 animate-pulse' :
                                        ($ctStatus === 'warning' ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500/50')
                                    }}"></span>
                                    <span class="text-xs font-black {{
                                        $ctStatus === 'error' ? 'text-rose-500' :
                                        ($ctStatus === 'warning' ? 'text-amber-500' : 'text-slate-400')
                                    }} uppercase tracking-widest">{{ __('Card Terminal') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-span-2">
                            <p
                                class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                                {{ __('Last Heartbeat') }}</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">
                                {{ $machine->last_heartbeat_at ? $machine->last_heartbeat_at->format('Y-m-d H:i:s') :
                                '-' }}
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3">
                        <button type="button"
                            @click="openEditModal('{{ $machine->id }}', '{{ addslashes($machine->name) }}', '{{ $machine->ambient_temp_setting }}', '{{ $machine->ambient_temp_monitoring_enabled ? 1 : 0 }}')"
                            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                            {{ __('Edit') }}
                        </button>
                        <button type="button"
                                                            @click="openLogPanel('{{ $machine->id }}', '{{ $machine->serial_no }}', '{{ addslashes($machine->name) }}', {{ $machine->ambient_temp_monitoring_enabled ? 1 : 0 }}, {{ $machine->show_card_terminal ? 1 : 0 }})"
                                                            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.67 8.5 7.652 6 12 6c4.348 0 8.332 2.5 9.964 5.678a1.012 1.012 0 0 1 0 .644C20.33 15.5 16.348 18 12 18c-4.348 0-8.332-2.5-9.964-5.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                            </svg>
                            {{ __('Logs') }}
                        </button>
                        <a href="{{ route('admin.machines.pricing', $machine) }}"
                            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-amber-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Pricing') }}
                        </a>
                    </div>
                </div>
                @empty
                <div class="md:col-span-2">
                    <x-empty-state :message="__('No machines found')">
                        <svg class="w-16 h-16 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9.75 17 9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                        </svg>
                    </x-empty-state>
                </div>
                @endforelse
            </div>

            <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
                @if($machines->total() > 0)
                    {{ $machines->appends(request()->query())->links('vendor.pagination.luxury') }}
                @else
                    <div class="flex items-center justify-between gap-4 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                        <span>{{ __('No records found') }}</span>
                        <span>0 / 0</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Offcanvas Log Panel -->
    <div x-show="showLogPanel" class="fixed inset-0 z-[100] overflow-hidden" style="display: none;"
        aria-labelledby="slide-over-title" role="dialog" aria-modal="true">

        <!-- Background backdrop -->
        <div x-show="showLogPanel" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showLogPanel = false">
        </div>

        <div class="fixed inset-y-0 right-0 max-w-full flex">
            <!-- Sliding panel -->
            <div x-show="showLogPanel"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="w-screen max-w-4xl">

                <div
                    class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-800 rounded-l-[32px] overflow-hidden">
                    <!-- Header -->
                    <div
                        class="px-5 py-6 sm:px-8 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <h2
                                    class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white font-display flex items-center gap-2 sm:gap-3">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-500 flex-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242" />
                                        <path d="M12 12v9" />
                                        <path d="m8 17 4 4 4-4" />
                                    </svg>
                                    <span class="truncate">{{ __('Machine Logs') }}</span>
                                </h2>
                                <div
                                    class="mt-2 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 text-[12px] sm:text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest overflow-hidden">
                                    <span x-text="currentMachineSn"
                                        class="font-mono text-cyan-600 dark:text-cyan-400 truncate"></span>
                                    <span class="hidden sm:inline opacity-50">—</span>
                                    <span x-text="currentMachineName" class="truncate"></span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 h-7 flex items-center">
                                <button type="button" @click="showLogPanel = false"
                                    class="bg-white dark:bg-slate-800 rounded-full p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition duration-300 shadow-sm border border-slate-200 dark:border-slate-700">
                                    <span class="sr-only">{{ __('Close Panel') }}</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Responsive Search within Panel -->
                        <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                            <div class="grid grid-cols-1 sm:flex sm:flex-wrap sm:items-center gap-3 sm:gap-4 flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 group">
                                    <label
                                        class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest whitespace-nowrap">{{
                                        __('Start Time') }}</label>
                                    <div class="relative">
                                        <span
                                            class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                        <input type="text" x-ref="startDatePicker" x-model="startDate" x-init="flatpickr($refs.startDatePicker, { 
                                                enableTime: true, 
                                                dateFormat: 'Y/m/d H:i', 
                                                time_24hr: true,
                                                locale: 'zh_tw',
                                                disableMobile: true,
                                                defaultDate: startDate,
                                                onClose: (selectedDates, dateStr) => { startDate = dateStr; fetchLogs(1); }
                                            })"
                                            class="luxury-input py-2.5 pl-12 pr-4 w-full sm:w-52 text-sm font-bold tracking-tight cursor-pointer">
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 group">
                                    <label
                                        class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest whitespace-nowrap">{{
                                        __('End Time') }}</label>
                                    <div class="relative">
                                        <span
                                            class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                        <input type="text" x-ref="endDatePicker" x-model="endDate" x-init="flatpickr($refs.endDatePicker, { 
                                                enableTime: true, 
                                                dateFormat: 'Y/m/d H:i', 
                                                time_24hr: true,
                                                locale: 'zh_tw',
                                                disableMobile: true,
                                                defaultDate: endDate,
                                                onClose: (selectedDates, dateStr) => { endDate = dateStr; fetchLogs(1); }
                                            })"
                                            class="luxury-input py-2.5 pl-12 pr-4 w-full sm:w-52 text-sm font-bold tracking-tight cursor-pointer">
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 group w-full sm:w-auto">
                                    <label
                                        class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest whitespace-nowrap">{{
                                        __('Level') }}</label>
                                    <x-searchable-select id="log-level-select" name="level" class="w-full sm:w-44"
                                        :placeholder="__('All Levels')" :has-search="false"
                                        @change="selectedLevel = $event.target.value.trim(); fetchLogs(1)">
                                        <option value="info" data-title="{{ __('Info') }}">{{ __('Info') }}</option>
                                        <option value="warning" data-title="{{ __('Warning') }}">{{ __('Warning') }}</option>
                                        <option value="error" data-title="{{ __('Error') }}">{{ __('Error') }}</option>
                                    </x-searchable-select>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-start sm:justify-end gap-4 sm:gap-6 shrink-0">
                                <button @click="confirmResolve()"
                                    class="text-[12px] font-bold text-rose-500 dark:text-rose-400 uppercase tracking-widest hover:text-rose-600 transition-colors flex items-center gap-1.5 px-3 py-1.5 rounded-xl hover:bg-rose-500/5 transition-all whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    {{ __('Clear Abnormal Status') }}
                                </button>
                                <button @click="startDate = ''; endDate = ''; selectedLevel = '';
                                        (() => { const s = document.getElementById('log-level-select'); const i = window.HSSelect?.getInstance(s); if (i) i.setValue(' '); })();
                                        fetchLogs(1)"
                                    class="text-[12px] font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-widest hover:text-cyan-500 transition-colors flex items-center gap-1.5 whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ __('Clear Filter') }}
                                </button>
                            </div>
                        </div>
                    </div><!-- /Header -->

                    <!-- Body / Navigation Tabs -->
                    <div class="flex-1 flex flex-col min-h-0">
                        <div
                            class="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 sticky top-0 z-10 px-6 sm:px-8 overflow-x-auto overflow-y-hidden hide-scrollbar">
                            <nav class="-mb-px flex space-x-6 sm:space-x-8" aria-label="Tabs">
                                <button @click="activeTab = 'status'"
                                    :class="{'border-cyan-500 text-cyan-600 dark:text-cyan-400': activeTab === 'status', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:hover:text-slate-300': activeTab !== 'status'}"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-[13px] sm:text-sm transition duration-300">
                                    {{ __('Machine Status') }}
                                </button>
                                <button @click="activeTab = 'login'"
                                    :class="{'border-cyan-500 text-cyan-600 dark:text-cyan-400': activeTab === 'login', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:hover:text-slate-300': activeTab !== 'login'}"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-[13px] sm:text-sm transition duration-300">
                                    {{ __('Machine Login Logs') }}
                                </button>
                                 <button @click="activeTab = 'submachine'"
                                    :class="{'border-cyan-500 text-cyan-600 dark:text-cyan-400': activeTab === 'submachine', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:hover:text-slate-300': activeTab !== 'submachine'}"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-[13px] sm:text-sm transition duration-300">
                                    {{ __('Sub-machine Status Request') }}
                                </button>
                                <button @click="activeTab = 'card_terminal'"
                                    x-show="currentMachineCardTerminalEnabled"
                                    :class="{'border-cyan-500 text-cyan-600 dark:text-cyan-400': activeTab === 'card_terminal', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:hover:text-slate-300': activeTab !== 'card_terminal'}"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-[13px] sm:text-sm transition duration-300"
                                    x-cloak>
                                    {{ __('Card Terminal Logs') }}
                                </button>
                                <button @click="activeTab = 'ambient_temp'"
                                    x-show="currentMachineAmbientTempEnabled"
                                    :class="{'border-cyan-500 text-cyan-600 dark:text-cyan-400': activeTab === 'ambient_temp', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:hover:text-slate-300': activeTab !== 'ambient_temp'}"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-[13px] sm:text-sm transition duration-300"
                                    x-cloak>
                                    {{ __('Ambient Temperature Log') }}
                                </button>
 
                            </nav>
                        </div>
 
                        <!-- Tab Contents -->
                        <div class="flex-1 overflow-y-auto p-6 sm:p-8">
 
                            <div class="relative min-h-[400px]">
                                <!-- Loading State -->
                                <div x-show="loading" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-300"
                                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] rounded-2xl"
                                    x-cloak>
 
                                    <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                                        <!-- 外圈：快速旋轉 -->
                                        <div
                                            class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin">
                                        </div>
                                        <!-- 內圈：反向慢速旋轉 -->
                                        <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin"
                                            style="animation-duration: 3s; direction: reverse;"></div>
                                        <!-- 核心：脈衝圖示 -->
                                        <div class="relative w-8 h-8 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p
                                        class="text-[12px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
                                        {{ __('Loading Data') }}...</p>
                                </div>
 
                                <!-- Temperature Trend Chart -->
                                <div x-show="activeTab === 'status'" 
                                    class="mb-6 p-4 rounded-2xl bg-slate-50/50 dark:bg-white/[0.02] border border-slate-100 dark:border-slate-800/50">
                                    <div class="flex items-center justify-between mb-4 px-2">
                                        <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 shadow-[0_0_8px_rgba(6,182,212,0.5)]"></span>
                                            {{ __('Temperature Trend') }}
                                        </h3>
                                        <span class="text-[10px] font-bold text-slate-400" x-show="logs.length > 0">
                                            <span x-text="currentMachineName"></span> @ <span x-text="startDate.split(' ')[0]"></span>
                                        </span>
                                    </div>
                                    <div id="temperature-chart" class="min-h-[200px] w-full"></div>
                                </div>
 
                                <!-- Ambient Temperature Trend Chart -->
                                <div x-show="activeTab === 'ambient_temp'" 
                                    class="mb-6 p-4 rounded-2xl bg-slate-50/50 dark:bg-white/[0.02] border border-slate-100 dark:border-slate-800/50" x-cloak>
                                    <div class="flex items-center justify-between mb-4 px-2">
                                        <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></span>
                                            {{ __('Ambient Temperature Trend') }}
                                        </h3>
                                        <span class="text-[10px] font-bold text-slate-400" x-show="logs.length > 0">
                                            <span x-text="currentMachineName"></span> @ <span x-text="startDate.split(' ')[0]"></span>
                                        </span>
                                    </div>
                                    <div id="ambient-temperature-chart" class="min-h-[200px] w-full"></div>
                                </div>

                                <!-- Logs Container -->
                                <div x-show="activeTab !== 'expiry'"
                                    class="luxury-card border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                                    <!-- Desktop Table -->
                                    <div class="hidden sm:block overflow-x-auto">
                                        <table
                                            class="w-full text-left border-separate border-spacing-y-0 text-sm whitespace-nowrap">
                                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                                <tr>
                                                    <th
                                                        class="px-6 py-4 text-[12px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-200 dark:border-slate-700 w-40">
                                                        {{ __('Timestamp') }}</th>
                                                    <th
                                                        class="px-6 py-4 text-[12px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-200 dark:border-slate-700 w-24 text-center">
                                                        {{ __('Level') }}</th>
                                                    <th
                                                        class="px-6 py-4 text-[12px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-200 dark:border-slate-700">
                                                        {{ __('Message') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
                                                <template x-for="log in logs" :key="log.id">
                                                    <tr
                                                        class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all">
                                                        <td class="px-6 py-4">
                                                            <div class="flex flex-col">
                                                                <span
                                                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest"
                                                                    x-text="formatDateTime(log.created_at).split(' ')[0]"></span>
                                                                <span
                                                                    class="text-[14px] font-bold text-slate-600 dark:text-slate-200 mt-0.5"
                                                                    x-text="formatDateTime(log.created_at).split(' ')[1]"></span>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 text-center">
                                                            <span :class="{
                                                                'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20': log.level === 'info',
                                                                'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20': log.level === 'warning',
                                                                'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20': log.level === 'error'
                                                            }"
                                                                class="inline-flex items-center px-2 py-0.5 rounded-md border text-[12px] font-black uppercase tracking-wider">
                                                                <span x-text="log.level"></span>
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4">
                                                            <p class="text-[13px] font-medium text-slate-700 dark:text-slate-200 truncate max-w-md"
                                                                :title="log.translated_message || log.message"
                                                                x-text="log.translated_message || log.message"></p>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Mobile List view -->
                                    <div class="sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
                                        <template x-for="log in logs" :key="log.id">
                                            <div
                                                class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span
                                                        class="text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest"
                                                        x-text="formatDateTime(log.created_at)"></span>
                                                    <span :class="{
                                                        'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20': log.level === 'info',
                                                        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20': log.level === 'warning',
                                                        'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20': log.level === 'error'
                                                    }"
                                                        class="inline-flex items-center px-1.5 py-0.5 rounded-md border text-[9px] font-black uppercase tracking-tight">
                                                        <span x-text="log.level"></span>
                                                    </span>
                                                </div>
                                                <p class="text-[13px] font-bold text-slate-700 dark:text-slate-200 line-clamp-3 leading-relaxed"
                                                    x-text="log.translated_message || log.message"></p>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Empty state -->
                                    <template x-if="logs.length === 0 && !loading">
                                        <div class="px-6 py-20 text-center">
                                            <div class="flex flex-col items-center">
                                                <div
                                                    class="p-4 rounded-full bg-slate-50 dark:bg-slate-800/50 mb-4 border border-slate-100 dark:border-slate-800/50">
                                                    <svg class="w-8 h-8 text-slate-300 dark:text-slate-600"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.5">
                                                        <path
                                                            d="M21 7v10c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V7c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2z" />
                                                        <path d="M12 11l4-4" />
                                                        <path d="M8 15l4-4" />
                                                    </svg>
                                                </div>
                                                <span
                                                    class="text-[12px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{
                                                    __('No matching logs found') }}</span>
                                            </div>
                                        </div>
                                    </template>


                                </div>

                            </div>

                        </div>

                        <!-- Fixed Pagination Footer -->
                        <div x-show="logs.length > 0 && activeTab !== 'expiry'"
                            class="mt-auto border-t border-slate-100 dark:border-slate-800/80 px-6 py-4 flex items-center justify-between bg-slate-50/30 dark:bg-slate-900/30 backdrop-blur-sm rounded-bl-[32px]">
                            <div class="flex items-center gap-4">
                                <button @click="fetchLogs(currentPage - 1)" :disabled="currentPage <= 1"
                                    class="h-9 px-4 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all shadow-sm border border-slate-200 dark:border-slate-700 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-white dark:hover:bg-slate-800 flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                    {{ __('Previous') }}
                                </button>

                                <div class="relative group">
                                    <select @change="fetchLogs(parseInt($event.target.value))"
                                        class="h-9 pl-4 pr-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[11px] font-black text-slate-600 dark:text-slate-300 appearance-none focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 outline-none transition-all cursor-pointer shadow-sm hover:border-slate-300 dark:hover:border-slate-600 !bg-none">
                                        <template x-for="p in Array.from({length: lastPage}, (_, i) => i + 1)">
                                            <option :value="p" :selected="p === currentPage"
                                                x-text="p + ' / ' + lastPage"></option>
                                        </template>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-cyan-500/50 group-hover:text-cyan-500 transition-colors">
                                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>

                                <button @click="fetchLogs(currentPage + 1)" :disabled="currentPage >= lastPage"
                                    class="h-9 px-4 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all shadow-sm border border-slate-200 dark:border-slate-700 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-white dark:hover:bg-slate-800 flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                    {{ __('Next') }}
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <div
                                class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em]">
                                <span class="text-slate-600 dark:text-slate-300" x-text="totalLogs"></span> {{
                                __('items') }}
                            </div>
                        </div>
                    </div><!-- /Body -->

                </div>
            </div><!-- /Sliding panel -->
        </div>
    </div><!-- /Offcanvas -->

    <!-- Edit Machine Name Modal -->
    <div x-show="showEditModal" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;" role="dialog"
        aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background Backdrop -->
            <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showEditModal = false">
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:min-h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="showEditModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100 dark:border-slate-800">

                <div>
                    <h3
                        class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight leading-none mb-2">
                        {{ __('Edit Machine Name') }}</h3>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{
                        __('Update identification for your asset') }}</p>

                    <form :action="'/admin/machines/' + editMachineId" method="POST" class="mt-8 space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <label
                                class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.1em]">{{
                                __('New Machine Name') }}</label>
                            <input type="text" name="name" x-model="editMachineName" required
                                class="luxury-input block w-full px-6 py-4 text-base font-bold text-slate-800 dark:text-white bg-slate-50/50 dark:bg-slate-900/50"
                                placeholder="{{ __('Enter machine name...') }}">
                        </div>
 
                        <div class="space-y-4" x-show="editAmbientTempMonitoringEnabled" x-cloak>
                            <label
                                class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.1em]">{{
                                __('Ambient Temperature Upper Limit') }} ({{ __('Upper limit; the fan will turn on when reached') }})</label>
                            
                            {{-- Luxury +/- Counter UI --}}
                            <div class="flex items-center h-14 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden w-full group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                {{-- Minus Button --}}
                                <button type="button" @click="editAmbientTempSetting = (editAmbientTempSetting ? Math.max(0, parseInt(editAmbientTempSetting) - 1) : 25)" 
                                    class="shrink-0 w-14 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                    </svg>
                                </button>
                                
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                
                                {{-- Input Field --}}
                                <div class="flex-1 relative flex items-center">
                                    <input type="number" name="ambient_temp_setting" x-model="editAmbientTempSetting"
                                        class="w-full bg-transparent border-none text-center text-base font-extrabold text-slate-800 dark:text-white focus:ring-0 p-0 placeholder:text-slate-400 placeholder:font-bold placeholder:text-sm [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        placeholder="{{ __('Enter ambient temperature setting...') }}">
                                    
                                    {{-- Clear Button --}}
                                    <button type="button" x-show="editAmbientTempSetting !== '' && editAmbientTempSetting !== null" @click="editAmbientTempSetting = ''"
                                        class="absolute right-3 p-1 rounded-full text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 active:scale-95 transition-all"
                                        style="display: none;">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                
                                {{-- Plus Button --}}
                                <button type="button" @click="editAmbientTempSetting = (editAmbientTempSetting ? parseInt(editAmbientTempSetting) + 1 : 25)" 
                                    class="shrink-0 w-14 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>
                            </div>
                            
                            {{-- Help Text --}}
                            <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 tracking-wide mt-1 pl-1">
                                {{ __('Can be empty. If left blank, it means ambient temperature monitoring is disabled.') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <button type="button" @click="showEditModal = false"
                                class="px-8 py-4 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black rounded-2xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit"
                                class="flex-1 bg-cyan-500 hover:bg-cyan-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-cyan-500/30 transition-all duration-300 transform hover:-translate-y-0.5 active:scale-95">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- /Edit Modal -->

    <!-- Inventory Offcanvas Panel (唯讀庫存一覽) -->
    <div x-show="showInventoryPanel" class="fixed inset-0 z-[100] overflow-hidden" style="display: none;"
        aria-labelledby="inventory-panel-title" role="dialog" aria-modal="true">

        <!-- Background backdrop -->
        <div x-show="showInventoryPanel" x-transition:enter="ease-in-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            @click="showInventoryPanel = false">
        </div>

        <div class="fixed inset-y-0 right-0 max-w-full flex">
            <!-- Sliding panel -->
            <div x-show="showInventoryPanel"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="w-screen max-w-4xl">

                <div
                    class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-800 rounded-l-[32px] overflow-hidden">
                    <!-- Header -->
                    <div
                        class="px-5 py-6 sm:px-8 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <h2 id="inventory-panel-title"
                                    class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white font-display flex items-center gap-2 sm:gap-3">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-500 flex-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span class="truncate">{{ __('Stock & Expiry Overview') }}</span>
                                </h2>
                                <div
                                    class="mt-2 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 text-[12px] sm:text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest overflow-hidden">
                                    <span x-text="currentMachineSn"
                                        class="font-mono text-cyan-600 dark:text-cyan-400 truncate"></span>
                                    <span class="hidden sm:inline opacity-50">—</span>
                                    <span x-text="currentMachineName" class="truncate"></span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 h-7 flex items-center">
                                <button type="button" @click="showInventoryPanel = false"
                                    class="bg-white dark:bg-slate-800 rounded-full p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition duration-300 shadow-sm border border-slate-200 dark:border-slate-700">
                                    <span class="sr-only">{{ __('Close Panel') }}</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- 統計摘要 -->
                        <div class="mt-6 flex items-center gap-4">
                            <div
                                class="px-5 py-3 rounded-2xl bg-white dark:bg-slate-800/50 flex flex-col items-center min-w-[100px] border border-slate-100 dark:border-slate-800/50">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{
                                    __('Total Slots') }}</span>
                                <span class="text-2xl font-black text-slate-700 dark:text-slate-200"
                                    x-text="inventorySlots.length"></span>
                            </div>
                            <div
                                class="px-5 py-3 rounded-2xl bg-rose-500/5 border border-rose-500/10 flex flex-col items-center min-w-[100px]">
                                <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-0.5">{{
                                    __('Low Stock') }}</span>
                                <span class="text-2xl font-black text-rose-600"
                                    x-text="inventorySlots.filter(s => s != null && s.max_stock > 0 && s.stock <= (s.max_stock * 0.2)).length"></span>
                            </div>
                            <div
                                class="px-5 py-3 rounded-2xl bg-amber-500/5 border border-amber-500/10 flex flex-col items-center min-w-[100px]">
                                <span class="text-[9px] font-black text-amber-500 uppercase tracking-widest mb-0.5">{{
                                    __('Expiring') }}</span>
                                <span class="text-2xl font-black text-amber-600"
                                    x-text="inventorySlots.filter(s => { if (!s || !s.expiry_date) return false; const diff = Math.round((new Date(s.expiry_date) - new Date()) / 86400000); return diff >= 0 && diff <= 7; }).length"></span>
                            </div>
                        </div>
                    </div><!-- /Header -->

                    <!-- Body / Cabinet Grid -->
                    <div class="flex-1 overflow-y-auto p-6 sm:p-8">
                        <div class="relative min-h-[400px]">
                            <!-- Loading State -->
                            <div x-show="inventoryLoading" x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] rounded-2xl"
                                x-cloak>

                                <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                                    <!-- 外圈：快速旋轉 -->
                                    <div
                                        class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin">
                                    </div>
                                    <!-- 內圈：反向慢速旋轉 -->
                                    <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin"
                                        style="animation-duration: 3s; direction: reverse;"></div>
                                    <!-- 核心：脈衝圖示 -->
                                    <div class="relative w-8 h-8 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                        </svg>
                                    </div>
                                </div>
                                <p
                                    class="text-[12px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
                                    {{ __('Loading Data') }}...</p>
                            </div>

                            <!-- Status Legend -->
                            <div class="flex items-center gap-6 mb-6" x-show="!inventoryLoading">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-rose-500 shadow-lg shadow-rose-500/30"></span>
                                    <span class="text-[12px] font-black text-slate-500 uppercase tracking-[0.15em]">{{
                                        __('Expired') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-3 h-3 rounded-full bg-amber-500 shadow-lg shadow-amber-500/30"></span>
                                    <span class="text-[12px] font-black text-slate-500 uppercase tracking-[0.15em]">{{
                                        __('Warning') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-3 h-3 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/30"></span>
                                    <span class="text-[12px] font-black text-slate-500 uppercase tracking-[0.15em]">{{
                                        __('Normal') }}</span>
                                </div>
                            </div>

                            <!-- Slots Grid (唯讀) -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5"
                                x-show="!inventoryLoading">
                                <template x-for="slot in inventorySlots" :key="slot.id">
                                    <div :class="getSlotColorClass(slot)"
                                        class="min-h-[260px] rounded-[2rem] p-5 flex flex-col items-center justify-center border-2 transition-all duration-300 relative">

                                        <!-- Slot Header -->
                                        <div
                                            class="absolute top-3.5 left-4 right-4 flex justify-between items-center z-10">
                                            <div
                                                class="px-2.5 py-1 rounded-xl bg-slate-900/10 dark:bg-white/10 backdrop-blur-md border border-slate-900/5 dark:border-white/10 flex-shrink-0">
                                                <span
                                                    class="text-xs font-black uppercase tracking-tighter text-slate-800 dark:text-white"
                                                    x-text="slot.slot_no"></span>
                                            </div>
                                            <template x-if="slot.max_stock > 0 && slot.stock <= (slot.max_stock * 0.2)">
                                                <div
                                                    class="px-2 py-1 rounded-xl bg-rose-500 text-white text-[9px] font-black uppercase tracking-widest shadow-lg shadow-rose-500/30 animate-pulse whitespace-nowrap select-none">
                                                    {{ __('Low') }}
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Product Image -->
                                        <div class="relative w-16 h-16 mb-3 mt-2">
                                            <div
                                                class="absolute inset-0 rounded-2xl bg-white/20 dark:bg-slate-900/40 backdrop-blur-xl border border-white/30 dark:border-white/5 shadow-inner overflow-hidden">
                                                <template x-if="slot.product && slot.product.image_url">
                                                    <img :src="slot.product.image_url"
                                                        class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!slot.product || !slot.product.image_url">
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-7 h-7 opacity-20" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                        </svg>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Slot Info -->
                                        <div class="text-center w-full space-y-2">
                                            <template x-if="slot.product">
                                                <div class="text-sm font-black truncate w-full opacity-90 tracking-tight"
                                                    x-text="slot.product.name"></div>
                                            </template>
                                            <template x-if="!slot.product">
                                                <div
                                                    class="text-sm font-bold text-slate-300 dark:text-slate-600 tracking-tight">
                                                    {{ __('Empty') }}</div>
                                            </template>

                                            <div class="space-y-2">
                                                <!-- Stock Level -->
                                                <div class="flex items-baseline justify-center gap-1">
                                                    <span class="text-xl font-black tracking-tighter leading-none"
                                                        x-text="slot.stock"></span>
                                                    <span class="text-xs font-black opacity-30">/</span>
                                                    <span class="text-sm font-bold opacity-50"
                                                        x-text="slot.max_stock || 10"></span>
                                                </div>

                                                <!-- Expiry Date -->
                                                <div class="text-sm font-black tracking-tight leading-none opacity-80"
                                                    x-text="slot.expiry_date ? slot.expiry_date.replace(/-/g, '/') : '----/--/--'">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Empty state -->
                            <template x-if="inventorySlots.length === 0 && !inventoryLoading">
                                <div class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="p-4 rounded-full bg-slate-50 dark:bg-slate-800/50 mb-4 border border-slate-100 dark:border-slate-800/50">
                                            <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <span
                                            class="text-[12px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{
                                            __('No slot data available') }}</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div><!-- /Body -->

                </div>
            </div><!-- /Sliding panel -->
        </div>
    </div><!-- /Inventory Offcanvas -->

    <x-confirm-modal alpineVar="showResolveConfirm" confirmAction="executeResolve()" iconType="warning"
        title="{{ __('Clear Abnormal Status') }}"
        message="{{ __('Are you sure you want to resolve all recent issues for this machine?') }}"
        confirmText="{{ __('Confirm') }}" cancelText="{{ __('Cancel') }}" confirmColor="rose" />

    @endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endsection
