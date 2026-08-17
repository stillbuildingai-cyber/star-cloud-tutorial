@extends('layouts.admin')

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endsection

@section('content')
<script>
    /**
     * Machine Utilization Dashboard Alpine Component
     * Robust Pattern: Defined as a global function to ensure availability before Alpine initialization.
     */
    window.utilizationDashboard = function (initialMachines, initialStats) {
        return {
            startDate: new Date().toISOString().split('T')[0],
            machines: initialMachines || [],
            fleetStats: initialStats || { onlineCount: 0, totalMachines: 0, totalOrders: 0, totalRevenue: 0, alertCount: 0 },
            searchQuery: '',
            selectedMachineId: null,
            selectedMachine: null,
            loading: false,
            chart: null,
            miniChart: null,

            get filteredMachines() {
                if (!this.searchQuery) return this.machines;
                const q = this.searchQuery.toLowerCase();
                return this.machines.filter(m =>
                    m.name.toLowerCase().includes(q) ||
                    m.serial_no.toLowerCase().includes(q)
                );
            },

            init() {
                this.$nextTick(() => {
                    if (this.machines.length > 0) {
                        this.selectMachine(this.machines[0]);
                    }
                });
            },



            async selectMachine(machine) {
                this.selectedMachineId = machine.id;
                this.loading = true;
                window.dispatchEvent(new CustomEvent('show-global-loading'));
                try {
                    // Correct route as defined in web.php: /admin/machines/utilization-ajax/{id}
                    const res = await fetch(`/admin/machines/utilization-ajax/${machine.id}?date=${this.startDate}`);
                    const data = await res.json();
                    if (data.success) {
                        this.selectedMachine = { ...machine, ...data.data };
                        this.$nextTick(() => this.updateChart(data.data.chart_data));
                    }
                } catch (e) {
                    console.error('Failed to fetch machine data:', e);
                } finally {
                    this.loading = false;
                    window.dispatchEvent(new CustomEvent('hide-global-loading'));
                }
            },

            async fetchData() {
                this.loading = true;
                window.dispatchEvent(new CustomEvent('show-global-loading'));
                try {
                    const res = await fetch(`/admin/machines/utilization-ajax?date=${this.startDate}`);
                    const data = await res.json();
                    if (data.success) {
                        this.fleetStats = data.data;
                        if (this.selectedMachineId) {
                            const m = this.machines.find(x => x.id === this.selectedMachineId);
                            if (m) this.selectMachine(m);
                        }
                    }
                } catch (e) {
                    console.error('Failed to fetch fleet data:', e);
                } finally {
                    this.loading = false;
                    window.dispatchEvent(new CustomEvent('hide-global-loading'));
                }
            },

            updateChart(chartData) {
                const labels = chartData ? chartData.labels : [];
                const values = chartData ? chartData.values : [];
                const options = {
                    series: [{ name: '{{ __("Sales") }}', data: values }],
                    chart: {
                        type: 'bar',
                        height: 350,
                        toolbar: { show: false },
                        fontFamily: 'Plus Jakarta Sans, sans-serif',
                        accessibility: {
                            enabled: false
                        }
                    },
                    plotOptions: {
                        bar: { borderRadius: 6, columnWidth: '60%' }
                    },
                    dataLabels: { enabled: false },
                    colors: ['#06b6d4'],
                    grid: {
                        borderColor: '#f1f5f9',
                        strokeDashArray: 4,
                        padding: { left: 20 }
                    },
                    xaxis: {
                        categories: labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            rotate: -45,
                            style: { colors: '#94a3b8', fontWeight: 700, fontSize: '9px' }
                        }
                    },
                    yaxis: {
                        min: 0,
                        tickAmount: 4,
                        labels: {
                            formatter: (v) => Math.round(v),
                            style: { colors: '#94a3b8', fontWeight: 700, fontSize: '10px' }
                        }
                    },
                    tooltip: {
                        theme: 'dark',
                        custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                            const hour = labels[dataPointIndex] || '';
                            const count = series[seriesIndex][dataPointIndex];
                            return '<div class="px-3 py-2 bg-slate-900 text-white rounded-lg border border-slate-700 shadow-xl">' +
                                '<span class="text-[10px] font-black uppercase tracking-widest block opacity-50 mb-1">' + hour + '</span>' +
                                '<span class="text-sm font-black">' + count + ' {{ __("orders") }}</span>' +
                                '</div>';
                        }
                    }
                };

                const chartEl = document.querySelector("#utilization-chart");
                if (chartEl) {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(chartEl, options);
                    this.chart.render();
                }
            }
        };
    }
</script>

<div class="space-y-4" x-data="utilizationDashboard(@js($compactMachines), @js($fleetStats))">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight">{{ __('Machine Utilization')
                }}</h1>
            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mt-1">{{ __('Real-time fleet status and revenue monitoring') }}</p>
        </div>
        <div
            class="flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 backdrop-blur-sm">
            <span class="flex h-2 w-2 rounded-full bg-cyan-500 animate-pulse"></span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ __('Live Fleet Updates')
                }}</span>
        </div>
    </div>

    <!-- Top Stats Bar -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        <!-- Alert Count Card -->
        <div class="luxury-card rounded-3xl p-6 relative overflow-hidden group">
            <div
                class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/10 transition-all duration-500">
            </div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p
                        class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">
                        {{ __('Alerts Today') }}</p>
                    <h3 class="text-3xl font-black tracking-tight"
                        :class="(fleetStats.alertCount || 0) > 0 ? 'text-rose-500' : 'text-slate-800 dark:text-white'"
                        x-text="fleetStats.alertCount || 0"></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center"
                    :class="(fleetStats.alertCount || 0) > 0 ? 'bg-rose-500/10 text-rose-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path
                            d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full"
                    :class="(fleetStats.alertCount || 0) > 0 ? 'bg-rose-500' : 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]'"></span>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest"
                    x-text="(fleetStats.alertCount || 0) > 0 ? '{{ __('Needs Attention') }}' : '{{ __('All Normal') }}'"></span>
            </div>
        </div>

        <!-- Online Pulse Card -->
        <div class="luxury-card rounded-3xl p-6 relative overflow-hidden group">
            <div
                class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-all duration-500">
            </div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p
                        class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">
                        {{ __('Online Status') }}</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-800 dark:text-white tracking-tight"
                            x-text="fleetStats.onlineCount || 0"></span>
                        <span class="text-sm font-bold text-slate-400">/ <span
                                x-text="fleetStats.totalMachines || 0"></span></span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center">
                    <div class="relative flex h-4 w-4">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500"></span>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <div class="h-1.5 flex-1 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 transition-all duration-1000"
                        :style="'width: ' + ((fleetStats.onlineCount / fleetStats.totalMachines) * 100 || 0) + '%'">
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Insights Card -->
        <div class="luxury-card rounded-3xl p-6 relative overflow-hidden group">
            <div
                class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-all duration-500">
            </div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p
                        class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">
                        {{ __('Daily Revenue') }}</p>
                    <h3 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight"
                        x-text="'NT$ ' + Number(fleetStats.totalRevenue || 0).toLocaleString()"></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest"
                x-text="'{{ __('Total Orders') }}: ' + (fleetStats.totalOrders || 0) + ' {{ __('orders') }}'"></p>
        </div>

        <!-- Date Control Card -->
        <div class="luxury-card rounded-3xl p-6 border-cyan-500/20 bg-gradient-to-br from-white to-cyan-50/30 dark:from-slate-900 dark:to-cyan-950/20"
            x-data="{ 
                fp: null,
                init() {
                    this.fp = flatpickr(this.$refs.dateInput, {
                        dateFormat: 'Y-m-d',
                        defaultDate: this.startDate,
                        locale: window.flatpickrLocale || 'default',
                        disableMobile: true,
                        onChange: (selectedDates, dateStr) => {
                            this.startDate = dateStr;
                            this.fetchData();
                        }
                    });
                    
                    this.$watch('startDate', (val) => {
                        if (this.fp && val !== this.fp.input.value) {
                            this.fp.setDate(val || new Date().toISOString().split('T')[0], false);
                        }
                    });
                }
            }">
            <p class="text-[11px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.2em] mb-4">{{ __('Reporting Period') }}</p>
            
            <div class="flex items-center gap-4">
                <div class="relative group flex-1">
                    <input type="text" x-ref="dateInput" readonly
                        class="w-full bg-transparent border-none p-0 text-xl font-black text-slate-800 dark:text-white focus:ring-0 cursor-pointer">
                    <div class="absolute bottom-0 left-0 w-0 h-0.5 bg-cyan-500 group-hover:w-full transition-all duration-500"></div>
                </div>
                
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" @click="startDate = new Date().toISOString().split('T')[0]; fetchData();"
                        class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-500 hover:text-white transition-all shadow-sm active:scale-95"
                        title="{{ __('Today') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                        </svg>
                    </button>
                    <button type="button" @click="startDate = ''; fetchData();"
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-all active:scale-95"
                        title="{{ __('Clear Filter') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <p class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('Select date to sync data') }}</p>
        </div>
    </div>

    <!-- Master-Detail Interaction Area -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start relative">
        <!-- Premium Loading Overlay -->
        <div x-show="loading" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 z-[60] flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px] rounded-3xl"
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
            <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
                {{ __('Loading Data') }}...</p>
        </div>

        <div class="lg:col-span-4 space-y-4"
            :class="{ 'opacity-40 pointer-events-none transition-opacity duration-500': loading }">

            <div class="flex items-center justify-between mb-2 px-1">
                <h4 class="text-sm font-black text-slate-800 dark:text-slate-200 uppercase tracking-widest">{{
                    __('Machine Registry') }}</h4>
                <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase"
                    x-text="(filteredMachines.length) + ' {{ __('Units') }}'"></span>
            </div>

            <!-- Search -->
            <div class="relative group">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                    <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" x-model="searchQuery" placeholder="{{ __('Search serial or name...') }}"
                    class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm">
            </div>

            <!-- List Container -->
            <div class="space-y-3 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar">
                <template x-for="machine in filteredMachines" :key="machine.id">
                    <button @click="selectMachine(machine)"
                        :class="selectedMachineId === machine.id ? 'border-cyan-500 ring-4 ring-cyan-500/10 bg-cyan-50/30 dark:bg-cyan-950/20' : 'border-slate-200/60 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                        class="w-full text-left p-4 rounded-2xl border bg-white dark:bg-slate-900 transition-all duration-300 group">
                        <div class="flex items-center gap-4">
                            <div :class="machine.status === 'online' ? 'bg-emerald-500' : 'bg-slate-300'"
                                class="w-1.5 h-10 rounded-full transition-colors duration-500"></div>
                            <div class="flex-1 min-w-0">
                                <h5 class="text-sm font-black text-slate-800 dark:text-slate-200 truncate"
                                    x-text="machine.name"></h5>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5"
                                    x-text="machine.serial_no"></p>
                            </div>
                            <svg :class="selectedMachineId === machine.id ? 'text-cyan-500 translate-x-0 opacity-100' : 'text-slate-300 -translate-x-2 opacity-0'"
                                class="w-5 h-5 transition-all duration-300" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="3">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </div>
                    </button>
                </template>

                <!-- Empty State -->
                <template x-if="filteredMachines.length === 0">
                    <div class="py-12 text-center">
                        <div
                            class="w-16 h-16 mx-auto bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-300 mb-4">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.5">
                                <path d="M15 15l6 6m-6-6l-6-6m6 6l6-6m-6 6l-6 6" />
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('No matching machines') }}</p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Detail: Performance Analysis -->
        <div class="lg:col-span-8">
            <template x-if="selectedMachine">
                <div class="space-y-6 animate-fade-in">
                    <!-- Machine Header -->
                    <div
                        class="luxury-card rounded-[2.5rem] p-8 border-b-4 border-b-cyan-500 shadow-xl shadow-cyan-500/5 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8">
                            <div
                                class="flex items-center gap-2 px-4 py-2 rounded-full bg-slate-900/5 dark:bg-white/10 backdrop-blur-md">
                                <div :class="selectedMachine.status === 'online' ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]' : 'bg-slate-400'"
                                    class="w-2 h-2 rounded-full"></div>
                                <span
                                    class="text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-200"
                                    x-text="selectedMachine.status === 'online' ? '{{ __('Online') }}' : '{{ __('Offline') }}'"></span>
                            </div>
                        </div>

                        <div class=" flex items-center gap-6">
                                    <div
                                        class="w-20 h-20 rounded-3xl bg-luxury-gradient flex items-center justify-center text-white shadow-lg shadow-cyan-500/20">
                                        <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight"
                                            x-text="selectedMachine.name"></h2>
                                        <p class="text-sm font-bold text-slate-500 uppercase tracking-[0.2em] mt-1"
                                            x-text="'{{ __('Serial NO') }}: ' + selectedMachine.serial_no"></p>
                            </div>
                        </div>

                        <!-- Highlights -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-10 p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800">
                            <!-- 目前狀態 -->
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">{{ __('Status') }}</p>
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="selectedMachine.status === 'online' ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]' : 'bg-slate-400'"
                                                class="w-2.5 h-2.5 rounded-full flex-shrink-0"></span>
                                            <span
                                                :class="selectedMachine.status === 'online' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500'"
                                                class="text-base font-black tracking-tight"
                                                x-text="selectedMachine.status === 'online' ? '{{ __('Online') }}' : '{{ __('Offline') }}'"></span>
                                </div>
                            </div>
                            <!-- 今日銷售 -->
                            <div>
                                <p class=" text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{
                                                __('Sales Today') }}</p>
                                                <p class="text-xl font-black text-slate-800 dark:text-slate-200"
                                                    x-text="(selectedMachine.output_count || 0) + ' {{ __('orders') }}'"></p>
                                        </div>
                                        <!-- 今日營收 -->
                                        <div>
                                            <p
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                                                {{ __('Revenue') }}</p>
                                            <p class="text-xl font-black text-cyan-600 dark:text-cyan-400"
                                                x-text="'NT$ ' + Number(selectedMachine.revenue || 0).toLocaleString()">
                                            </p>
                                        </div>
                                        <!-- 異常次數 -->
                                        <div>
                                            <p
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                                                {{ __('Errors') }}</p>
                                            <p :class="(selectedMachine.error_count || 0) > 0 ? 'text-rose-500' : 'text-slate-800 dark:text-slate-200'"
                                                class="text-xl font-black"
                                                x-text="(selectedMachine.error_count || 0) + ' {{ __('times') }}'"></p>
                                        </div>
                                    </div>
                            </div>

                            <!-- Utilization Chart -->
                            <div class="luxury-card rounded-[2.5rem] p-8">
                                <div class="flex items-center justify-between mb-8">
                                    <div>
                                        <h4 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{
                                            __('Hourly Sales') }}</h4>
                                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">{{
                                            __('Orders per hour for selected date') }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-cyan-500"></span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{
                                            __('Orders') }}</span>
                                    </div>
                                </div>
                                <div id="utilization-chart" class="w-full h-[350px]"></div>
                            </div>
                        </div>
            </template>

            <!-- Detail Placeholder -->
            <template x-if="!selectedMachine">
                <div
                    class="h-full min-h-[500px] flex flex-col items-center justify-center luxury-card rounded-[2.5rem] border-dashed border-2 border-slate-200 dark:border-slate-800 bg-slate-50/20">
                    <div
                        class="w-24 h-24 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 mb-6 scale-animation">
                        <svg class="w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5l7 7V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-800 dark:text-slate-200 tracking-tight">{{ __('No Machine Selected') }}</h3>
                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mt-2">{{ __('Select an asset from the left to start analysis') }}</p>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection