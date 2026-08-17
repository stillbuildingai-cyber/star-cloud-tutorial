@extends('layouts.admin')

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endsection

@section('content')
<div class="space-y-6 pb-20" x-data="machineReportApp(@js($summary), @js($chartData), @js($tableData), '{{ $dateRange }}', '{{ $selectedMachineId }}', '{{ $selectedProductId }}', '{{ $selectedCompanyId }}')">
    
    <!-- Page Header -->
    <x-page-header
        :title="__('Machine Report Analysis')"
        :subtitle="__('Vending machine revenue and payment analysis report')"
    >
        <div class="flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 backdrop-blur-sm">
            <span class="flex h-2 w-2 rounded-full bg-cyan-500 animate-pulse"></span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ __('Real-time operational data synchronized') }}</span>
        </div>
    </x-page-header>

    <!-- Top KPI Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- 銷售件數 -->
        <div class="luxury-card rounded-3xl p-6 relative overflow-hidden group animate-luxury-in" style="animation-delay: 50ms">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-cyan-500/5 rounded-full blur-2xl group-hover:bg-cyan-500/10 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">{{ __('Sales Quantity') }}</p>
                    <h3 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight" x-text="Number(summary.total_quantity).toLocaleString() + ' 件'">-</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-500">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-cyan-500"></span>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Product physical shipment count') }}</span>
            </div>
        </div>

        <!-- 銷售金額 -->
        <div class="luxury-card rounded-3xl p-6 relative overflow-hidden group animate-luxury-in" style="animation-delay: 100ms">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">{{ __('Total Sales Amount') }}</p>
                    <h3 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight" x-text="'NT$ ' + Number(summary.total_sales).toLocaleString()">-</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-500">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-indigo-500"></span>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Total transaction amount of successful payments') }}</span>
            </div>
        </div>

        <!-- 商品總成本 -->
        <div class="luxury-card rounded-3xl p-6 relative overflow-hidden group animate-luxury-in" style="animation-delay: 150ms">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-all duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">{{ __('Total Product Cost') }}</p>
                    <h3 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight" x-text="'NT$ ' + Number(summary.total_cost).toLocaleString()">-</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ __('Accumulated dynamic product purchase cost') }}</span>
            </div>
        </div>

        <!-- 銷售總利潤 -->
        <div class="luxury-card rounded-3xl p-6 relative overflow-hidden group animate-luxury-in" style="animation-delay: 200ms">
            <div class="absolute -right-4 -top-4 w-24 h-24 rounded-full blur-2xl transition-all duration-500"
                 :class="summary.total_profit >= 0 ? 'bg-amber-500/5 group-hover:bg-amber-500/10' : 'bg-rose-500/5 group-hover:bg-rose-500/10'"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">{{ __('Total Sales Profit') }}</p>
                    <h3 class="text-3xl font-black tracking-tight" 
                        :class="summary.total_profit >= 0 ? 'text-slate-800 dark:text-white' : 'text-rose-500'"
                        x-text="'NT$ ' + Number(summary.total_profit).toLocaleString()">-</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-colors"
                     :class="summary.total_profit >= 0 ? 'bg-amber-500/10 text-amber-500' : 'bg-rose-500/10 text-rose-500'">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full" :class="summary.total_profit >= 0 ? 'bg-amber-500' : 'bg-rose-500'"></span>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest"
                      x-text="summary.total_profit >= 0 ? '{{ __('Sales revenue minus product cost net value') }}' : '{{ __('Current data indicates a loss') }}'"></span>
            </div>
        </div>
    </div>

    <!-- Filter Panel Card -->
    <div class="luxury-card rounded-3xl p-4 sm:p-5 relative animate-luxury-in z-[40]" style="animation-delay: 250ms">
        <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-center justify-between gap-4">
            
            <!-- Date Shortcut Button Bar -->
            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mr-1.5">{{ __('Quick Filter') }}</span>
                <template x-for="item in shortcuts" :key="item.type">
                    <button type="button" 
                            @click="setShortcutRange(item.type)"
                            :class="activeShortcut === item.type ? 'bg-cyan-500 text-white font-black shadow-sm shadow-cyan-500/20' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold'"
                            class="px-3 py-1.5 rounded-xl text-[11px] transition-all active:scale-95 duration-200"
                            x-text="item.name"></button>
                </template>
            </div>

            <!-- Filter Controls Form -->
            <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3 w-full xl:w-auto xl:flex-1 xl:justify-end relative">
                <!-- Date Range (Flatpickr) -->
                <div class="w-full sm:w-80 relative focus-within:z-[50]">
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400 group-focus-within:text-cyan-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </span>
                        <input type="text" x-ref="dateInput" readonly
                               class="luxury-input py-2.5 pl-12 pr-6 block w-full text-sm font-bold cursor-pointer"
                               placeholder="{{ __('Date Range') }}">
                    </div>
                </div>

                <!-- Company Selection (Super Admin only) -->
                @if(auth()->user()->isSystemAdmin())
                <div class="w-full sm:w-52 relative focus-within:z-[30]">
                    <x-searchable-select name="company_id" id="company_id" :placeholder="__('All Companies')" :selected="$selectedCompanyId"
                        @change="companyChanged($event.target.value)">
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ (string)$selectedCompanyId === (string)$c->id ? 'selected' : '' }} data-title="{{ $c->name }}">{{ $c->name }}</option>
                        @endforeach
                    </x-searchable-select>
                </div>
                @endif

                <!-- Machine Selection -->
                <div class="w-full sm:w-52 relative focus-within:z-[30]">
                    <x-searchable-select name="machine_id" id="machine_id" :placeholder="__('All Machines')" :selected="$selectedMachineId"
                        @change="selectedMachineId = ($event.target.value && $event.target.value.trim() !== '') ? $event.target.value.trim() : 'ALL'; fetchData()">
                        @foreach($machines as $m)
                            <option value="{{ $m->id }}" {{ (string)$selectedMachineId === (string)$m->id ? 'selected' : '' }} data-title="{{ $m->name }}{{ $m->serial_no ? ' (' . $m->serial_no . ')' : '' }}">
                                {{ $m->name }}{{ $m->serial_no ? ' (' . $m->serial_no . ')' : '' }}
                            </option>
                        @endforeach
                    </x-searchable-select>
                </div>

                <!-- Product Selection -->
                <div class="w-full sm:w-52 relative focus-within:z-[30]">
                    <x-searchable-select name="product_id" id="product_id" :placeholder="__('All Products (ALL)')" :selected="$selectedProductId"
                        @change="selectedProductId = ($event.target.value && $event.target.value.trim() !== '') ? $event.target.value.trim() : 'ALL'; fetchData()">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ (string)$selectedProductId === (string)$p->id ? 'selected' : '' }} data-title="{{ $p->name }}{{ $p->barcode ? ' (' . $p->barcode . ')' : '' }}">
                                {{ $p->name }}{{ $p->barcode ? ' (' . $p->barcode . ')' : '' }}
                            </option>
                        @endforeach
                    </x-searchable-select>
                </div>

                <!-- Payment Methods Multi-select -->
                <div class="w-full sm:w-64 relative focus-within:z-[30]" id="payment-types-wrapper" @multi-select-changed.window="paymentTypesChanged()">
                    <x-multi-select
                        name="payment_types"
                        id="payment-types-select"
                        :options="$availablePaymentTypes"
                        :selected="$selectedPaymentTypes"
                        :placeholder="__('Filter by Payment Method')"
                    />
                </div>
            </div>

        </div>
    </div>

    <!-- Chart Panel Card -->
    <div class="luxury-card rounded-[2.5rem] p-8 relative overflow-hidden animate-luxury-in z-[30]" style="animation-delay: 300ms">
        <x-luxury-spinner show="loading" />
        <div :class="{ 'opacity-30 pointer-events-none': loading }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h4 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Machine Sales & Profit Trend') }}</h4>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">{{ __('Daily operational variance curve for selected timeframe') }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('Sales Amount') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('Product Cost') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-cyan-500"></span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('Sales Quantity') }}</span>
                    </div>
                </div>
            </div>
            
            <div class="w-full">
                <div id="machine-trend-chart" class="min-h-[380px] w-full"></div>
            </div>
        </div>
    </div>

    <!-- Data Table & Mobile List Panel Card -->
    <div class="luxury-card rounded-[2.5rem] p-8 relative overflow-hidden animate-luxury-in z-[20]" style="animation-delay: 350ms">
        <x-luxury-spinner show="loading" />
        <div :class="{ 'opacity-30 pointer-events-none': loading }">
            
            <!-- Table Header controls -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                <div>
                    <h4 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Detailed Analysis Data') }}</h4>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1" x-text="'共篩選出 ' + filteredTableData.length + ' 筆機台項目'"></p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                    <!-- Search Input -->
                    <div class="relative group min-w-[240px]">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10 text-slate-400">
                            <svg class="h-4 w-4 group-focus-within:text-cyan-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" x-model="searchQuery" placeholder="{{ __('Search machine name, serial no...') }}" 
                               class="luxury-input py-2 pl-12 pr-6 block w-full text-xs">
                    </div>

                    <!-- Exporters Dropdown -->
                    <div class="flex items-center gap-2" x-data="{ exportOpen: false }">
                        <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false"
                                class="btn-luxury-ghost py-2.5 px-4 rounded-xl flex items-center gap-2 text-xs">
                            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
                            </svg>
                            <span>{{ __('Export Report') }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="exportOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        
                        <!-- Exporter Menu -->
                        <div x-show="exportOpen" x-transition
                             class="absolute right-8 mt-12 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-xl rounded-2xl p-2 z-30 min-w-[140px]"
                             x-cloak>
                            <button @click="copyTableData(); exportOpen = false" class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                                📋 {{ __('Copy to Clipboard') }}
                            </button>
                            <button @click="exportCSV(); exportOpen = false" class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                                📄 {{ __('Export to CSV') }}
                            </button>
                            <button @click="exportExcel(); exportOpen = false" class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                                📊 {{ __('Export to Excel') }}
                            </button>
                            <button @click="printTable(); exportOpen = false" class="w-full text-left py-2 px-3 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 rounded-xl transition-all flex items-center gap-2">
                                🖨️ {{ __('Print & PDF') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Table (xl:block) -->
            <div class="hidden xl:block overflow-x-auto">
                <table class="w-full text-left border-separate border-spacing-y-0">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 rounded-l-2xl w-24">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ __('Rank') }}</span>
                                </div>
                            </th>
                            <th @click="sortBy('serial_no')" class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100/50 dark:hover:bg-slate-800/20 transition-colors">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ __('Machine Serial No') }}</span>
                                    <template x-if="sortField === 'serial_no'">
                                        <span x-text="sortAsc ? '▲' : '▼'" class="text-[9px] text-cyan-500"></span>
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('name')" class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100/50 dark:hover:bg-slate-800/20 transition-colors">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ __('Machine Name') }}</span>
                                    <template x-if="sortField === 'name'">
                                        <span x-text="sortAsc ? '▲' : '▼'" class="text-[9px] text-cyan-500"></span>
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('total_quantity')" class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right cursor-pointer hover:bg-slate-100/50 dark:hover:bg-slate-800/20 transition-colors">
                                <div class="flex items-center justify-end gap-1.5">
                                    <span>{{ __('Quantity Sold') }}</span>
                                    <template x-if="sortField === 'total_quantity'">
                                        <span x-text="sortAsc ? '▲' : '▼'" class="text-[9px] text-cyan-500"></span>
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('total_sales')" class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right cursor-pointer hover:bg-slate-100/50 dark:hover:bg-slate-800/20 transition-colors">
                                <div class="flex items-center justify-end gap-1.5">
                                    <span>{{ __('Total Sales') }}</span>
                                    <template x-if="sortField === 'total_sales'">
                                        <span x-text="sortAsc ? '▲' : '▼'" class="text-[9px] text-cyan-500"></span>
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('total_cost')" class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right cursor-pointer hover:bg-slate-100/50 dark:hover:bg-slate-800/20 transition-colors">
                                <div class="flex items-center justify-end gap-1.5">
                                    <span>{{ __('Total Product Cost') }}</span>
                                    <template x-if="sortField === 'total_cost'">
                                        <span x-text="sortAsc ? '▲' : '▼'" class="text-[9px] text-cyan-500"></span>
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('total_profit')" class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right cursor-pointer hover:bg-slate-100/50 dark:hover:bg-slate-800/20 transition-colors">
                                <div class="flex items-center justify-end gap-1.5">
                                    <span>{{ __('Total Net Profit') }}</span>
                                    <template x-if="sortField === 'total_profit'">
                                        <span x-text="sortAsc ? '▲' : '▼'" class="text-[9px] text-cyan-500"></span>
                                    </template>
                                </div>
                            </th>
                            <th @click="sortBy('margin')" class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right cursor-pointer hover:bg-slate-100/50 dark:hover:bg-slate-800/20 transition-colors rounded-r-2xl">
                                <div class="flex items-center justify-end gap-1.5">
                                    <span>{{ __('Gross Margin') }}</span>
                                    <template x-if="sortField === 'margin'">
                                        <span x-text="sortAsc ? '▲' : '▼'" class="text-[9px] text-cyan-500"></span>
                                    </template>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <template x-for="(row, index) in paginatedTableData" :key="row.machine_id || row.serial_no">
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                                <td class="px-6 py-4 text-xs font-mono font-black text-slate-400 dark:text-slate-500">
                                    <template x-if="((currentPage - 1) * perPage) + index + 1 === 1">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20 font-black shadow-sm">1</span>
                                    </template>
                                    <template x-if="((currentPage - 1) * perPage) + index + 1 === 2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-400/10 text-slate-400 border border-slate-400/20 font-black shadow-sm">2</span>
                                    </template>
                                    <template x-if="((currentPage - 1) * perPage) + index + 1 === 3">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-700/10 text-amber-700 border border-amber-700/20 font-black shadow-sm">3</span>
                                    </template>
                                    <template x-if="((currentPage - 1) * perPage) + index + 1 > 3">
                                        <span x-text="((currentPage - 1) * perPage) + index + 1" class="pl-2"></span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-xs font-black font-mono text-slate-400 dark:text-slate-500 tracking-widest" x-text="row.serial_no || '-'"></td>
                                <td class="px-6 py-4 text-sm font-extrabold text-slate-700 dark:text-slate-200" x-text="row.name"></td>
                                <td class="px-6 py-4 text-sm font-black text-right text-slate-600 dark:text-slate-300 font-mono" x-text="Number(row.total_quantity).toLocaleString() + ' 件'"></td>
                                <td class="px-6 py-4 text-sm font-black text-right text-indigo-500 dark:text-indigo-400 font-mono" x-text="'NT$ ' + Number(row.total_sales).toLocaleString()"></td>
                                <td class="px-6 py-4 text-sm font-black text-right text-slate-500 font-mono" x-text="'NT$ ' + Number(row.total_cost).toLocaleString()"></td>
                                <td class="px-6 py-4 text-sm font-black text-right font-mono"
                                    :class="row.total_profit >= 0 ? 'text-emerald-500' : 'text-rose-500'"
                                    x-text="'NT$ ' + Number(row.total_profit).toLocaleString()"></td>
                                <td class="px-6 py-4 text-sm font-black text-right font-mono"
                                    :class="row.total_sales > 0 && (row.total_profit / row.total_sales) >= 0 ? 'text-cyan-500' : 'text-rose-500'"
                                    x-text="row.total_sales > 0 ? ((row.total_profit / row.total_sales) * 100).toFixed(1) + '%' : '0.0%'"></td>
                            </tr>
                        </template>
                        
                        <!-- Empty State Table -->
                        <template x-if="paginatedTableData.length === 0">
                            <tr>
                                <td colspan="8" class="py-12 text-center">
                                    <div class="w-16 h-16 mx-auto bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-300 mb-4 scale-animation">
                                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M15 15l6 6m-6-6l-6-6m6 6l6-6m-6 6l-6 6" />
                                        </svg>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('No machine data matching search criteria') }}</p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Mobile List (xl:hidden) -->
            <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
                <template x-for="(row, index) in paginatedTableData" :key="row.machine_id || row.serial_no">
                    <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
                        
                        <!-- Header -->
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center border transition-all duration-300 overflow-hidden shadow-sm shrink-0 font-black text-base"
                                     :class="
                                        ((currentPage - 1) * perPage) + index + 1 === 1 ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                                        (((currentPage - 1) * perPage) + index + 1 === 2 ? 'bg-slate-400/10 text-slate-400 border-slate-400/20' : 
                                        (((currentPage - 1) * perPage) + index + 1 === 3 ? 'bg-amber-700/10 text-amber-700 border-amber-700/20' : 
                                        'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white group-hover:border-cyan-500'))
                                     ">
                                    <template x-if="((currentPage - 1) * perPage) + index + 1 === 1"><span>🥇</span></template>
                                    <template x-if="((currentPage - 1) * perPage) + index + 1 === 2"><span>🥈</span></template>
                                    <template x-if="((currentPage - 1) * perPage) + index + 1 === 3"><span>🥉</span></template>
                                    <template x-if="((currentPage - 1) * perPage) + index + 1 > 3">
                                        <span x-text="((currentPage - 1) * perPage) + index + 1" class="text-sm font-black font-mono"></span>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight" x-text="row.name"></h3>
                                    <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate" x-text="row.serial_no || '-'"></p>
                                </div>
                            </div>
                            <!-- Profit Margin Badge -->
                            <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full"
                                  :class="row.total_sales > 0 && (row.total_profit / row.total_sales) >= 0 ? 'bg-cyan-500/10 text-cyan-600 border border-cyan-500/20' : 'bg-rose-500/10 text-rose-600 border border-rose-500/20'"
                                  x-text="'毛利 ' + (row.total_sales > 0 ? ((row.total_profit / row.total_sales) * 100).toFixed(1) + '%' : '0.0%')"></span>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Sales Quantity') }}</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono" x-text="row.total_quantity + ' 件'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Total Sales') }}</p>
                                <p class="text-sm font-bold text-indigo-500 dark:text-indigo-400 font-mono" x-text="'NT$ ' + Number(row.total_sales).toLocaleString()"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Product Cost') }}</p>
                                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 font-mono" x-text="'NT$ ' + Number(row.total_cost).toLocaleString()"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Total Net Profit') }}</p>
                                <p class="text-sm font-bold font-mono" :class="row.total_profit >= 0 ? 'text-emerald-500' : 'text-rose-500'" x-text="'NT$ ' + Number(row.total_profit).toLocaleString()"></p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3">
                            <button type="button" @click="selectedMachineId = row.machine_id; fetchData()"
                                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                                📊 {{ __('Analyze Solely') }}
                            </button>
                        </div>

                    </div>
                </template>
                
                <!-- Empty State Mobile -->
                <template x-if="paginatedTableData.length === 0">
                    <div class="py-12 text-center col-span-2">
                        <div class="w-16 h-16 mx-auto bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-300 mb-4">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M15 15l6 6m-6-6l-6-6m6 6l6-6m-6 6l-6 6" />
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('No machine data matching search criteria') }}</p>
                    </div>
                </template>
            </div>

            <!-- Pagination Control Bar -->
            <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6 flex flex-col lg:flex-row items-center justify-between w-full gap-6">
                
                <!-- Left Side: Limit Selector & Info -->
                <div class="order-2 lg:order-1 flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
                    <!-- Limit Selector -->
                    <div class="flex items-center gap-3 bg-slate-50/50 dark:bg-slate-900/50 px-3 py-1.5 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm leading-none">
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1 leading-none">{{ __('Show') }}</span>
                        <div class="relative group flex items-center">
                            <select x-model="perPage" @change="currentPage = 1" 
                                    class="h-7 pl-2 pr-7 rounded-lg bg-white dark:bg-slate-800 border-none text-[11px] font-black text-slate-600 dark:text-slate-300 appearance-none focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all cursor-pointer shadow-sm leading-none py-0 !bg-none">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-1.5 pointer-events-none text-cyan-500/50 group-hover:text-cyan-500 transition-colors">
                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Items Info -->
                    <p class="text-[10px] sm:text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center sm:text-left whitespace-nowrap">
                        <span class="hidden xs:inline">{{ __('Showing') }}</span>
                        <span class="text-slate-600 dark:text-slate-300" x-text="filteredTableData.length > 0 ? ((currentPage - 1) * perPage + 1) : 0"></span>
                        {{ __('to') }}
                        <span class="text-slate-600 dark:text-slate-300" x-text="Math.min(currentPage * perPage, filteredTableData.length)"></span>
                        <span class="hidden xs:inline">{{ __('of') }}</span>
                        <span class="inline xs:hidden">/</span>
                        <span class="text-slate-600 dark:text-slate-300" x-text="filteredTableData.length"></span>
                        {{ __('items') }}
                    </p>
                </div>

                <!-- Page Navigation Buttons -->
                <div class="order-1 lg:order-2 flex items-center gap-x-1.5 sm:gap-x-2">
                    <!-- Previous Page Link -->
                    <button type="button" @click="prevPage()" :disabled="currentPage === 1"
                        :class="currentPage === 1 ? 'bg-slate-50 dark:bg-slate-800 text-slate-300 dark:text-slate-600 cursor-not-allowed border-slate-100 dark:border-slate-800' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                        class="h-9 inline-flex items-center gap-x-1.5 sm:gap-x-2 px-3 sm:px-4 rounded-xl text-[10px] sm:text-xs font-black border transition-all shadow-sm group">
                        <svg class="size-3.5 sm:size-4 text-cyan-500 group-hover:scale-110 transition-transform" :class="currentPage === 1 ? 'opacity-30' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                        <span class="hidden xxs:inline">{{ __('Previous') }}</span>
                    </button>

                    <!-- Page Dropdown Quick Jump -->
                    <div class="relative group">
                        <select x-model.number="currentPage" 
                            class="h-9 pl-4 pr-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[11px] sm:text-xs font-black text-slate-600 dark:text-slate-300 appearance-none focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 outline-none transition-all cursor-pointer shadow-sm hover:border-slate-300 dark:hover:border-slate-600 !bg-none"
                            :disabled="totalPages <= 1"
                            x-html="Array.from({length: totalPages}, (_, i) => '<option value=\'' + (i+1) + '\' ' + (currentPage === (i+1) ? 'selected' : '') + '>' + (i+1) + ' / ' + totalPages + '</option>').join('')">
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-cyan-500/50 group-hover:text-cyan-500 transition-colors">
                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <!-- Next Page Link -->
                    <button type="button" @click="nextPage()" :disabled="currentPage === totalPages || totalPages === 0"
                        :class="(currentPage === totalPages || totalPages === 0) ? 'bg-slate-50 dark:bg-slate-800 text-slate-300 dark:text-slate-600 cursor-not-allowed border-slate-100 dark:border-slate-800' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                        class="h-9 inline-flex items-center gap-x-1.5 sm:gap-x-2 px-3 sm:px-4 rounded-xl text-[10px] sm:text-xs font-black border transition-all shadow-sm group">
                        <span class="hidden xxs:inline">{{ __('Next') }}</span>
                        <svg class="size-3.5 sm:size-4 text-cyan-500 group-hover:scale-110 transition-transform" :class="(currentPage === totalPages || totalPages === 0) ? 'opacity-30' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

            </div>

        </div>
    </div>

</div>

<script>
    /**
     * Machine Report Dashboard Alpine Component
     */
    window.machineReportApp = function (initialSummary, initialChartData, initialTableData, initialDateRange, initialMachineId, initialProductId, initialCompanyId) {
        return {
            summary: initialSummary || { total_quantity: 0, total_sales: 0.0, total_cost: 0.0, total_profit: 0.0 },
            chartData: initialChartData || [],
            tableData: initialTableData || [],
            dateRange: initialDateRange || '',
            selectedMachineId: initialMachineId || 'ALL',
            selectedProductId: initialProductId || 'ALL',
            selectedCompanyId: initialCompanyId || '',
            loading: false,
            chart: null,
            fp: null,
            resizeObserver: null,
            
            // Client-side search and sorting
            searchQuery: '',
            sortField: 'total_sales',
            sortAsc: false,
            
            // Client-side pagination
            currentPage: 1,
            perPage: 10,
            
            // Date shortcuts configuration
            activeShortcut: '7days',
            shortcuts: [
                { type: '7days', name: '{{ __("Last 7 Days") }}' },
                { type: 'today', name: '{{ __("Today") }}' },
                { type: 'week', name: '{{ __("This Week") }}' },
                { type: 'lastweek', name: '{{ __("Last Week") }}' },
                { type: 'month', name: '{{ __("This Month") }}' },
                { type: 'lastmonth', name: '{{ __("Last Month") }}' }
            ],

            init() {
                // Initialize Flatpickr Date Range selector
                this.$nextTick(() => {
                    this.fp = flatpickr(this.$refs.dateInput, {
                        mode: 'range',
                        dateFormat: 'Y-m-d',
                        defaultDate: this.dateRange ? this.dateRange.split(' to ') : [],
                        locale: window.flatpickrLocale || 'default',
                        disableMobile: true,
                        onClose: (selectedDates, dateStr) => {
                            if (selectedDates.length === 2) {
                                // Re-format to exact YYYY-MM-DD to YYYY-MM-DD
                                const start = this.formatDateStr(selectedDates[0]);
                                const end = this.formatDateStr(selectedDates[1]);
                                this.dateRange = `${start} to ${end}`;
                                this.activeShortcut = ''; // Clear custom shortcut selection
                                this.fetchData();
                            }
                        }
                    });
                    
                    // Render ApexCharts for the first time
                    this.updateChart(this.chartData);
                });
            },

            // Format date correctly to local YYYY-MM-DD string
            formatDateStr(d) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },

            // Set specific start/end range on Shortcut button click
            setShortcutRange(type) {
                this.activeShortcut = type;
                const today = new Date();
                let start = new Date();
                let end = new Date();

                switch (type) {
                    case '7days':
                        start.setDate(today.getDate() - 6);
                        break;
                    case 'today':
                        // start = end = today
                        break;
                    case 'week': {
                        const day = today.getDay();
                        const diffToMonday = today.getDate() - day + (day === 0 ? -6 : 1);
                        start = new Date(today.getFullYear(), today.getMonth(), diffToMonday);
                        end = new Date(start);
                        end.setDate(start.getDate() + 6);
                        break;
                    }
                    case 'lastweek': {
                        const day = today.getDay();
                        const diffToMonday = today.getDate() - day + (day === 0 ? -6 : 1) - 7;
                        start = new Date(today.getFullYear(), today.getMonth(), diffToMonday);
                        end = new Date(start);
                        end.setDate(start.getDate() + 6);
                        break;
                    }
                    case 'month':
                        start = new Date(today.getFullYear(), today.getMonth(), 1);
                        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        break;
                    case 'lastmonth':
                        start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        end = new Date(today.getFullYear(), today.getMonth(), 0);
                        break;
                }

                const startStr = this.formatDateStr(start);
                const endStr = this.formatDateStr(end);
                this.dateRange = `${startStr} to ${endStr}`;
                
                if (this.fp) {
                    this.fp.setDate([start, end]);
                }
                
                this.fetchData();
            },

            // Trigger safe query parameter reload when Admin switches Tenant Company context
            companyChanged(val) {
                const companyId = (val && val.trim() !== '') ? val.trim() : '';
                this.selectedCompanyId = companyId;
                const url = new URL(window.location.href);
                if (companyId) {
                    url.searchParams.set('company_id', companyId);
                } else {
                    url.searchParams.delete('company_id');
                }
                // When company context is changed, machine/product registry MUST be reset to avoid key mismatch
                url.searchParams.set('machine_id', 'ALL');
                url.searchParams.set('product_id', 'ALL');
                url.searchParams.delete('payment_types');
                window.location.href = url.toString();
            },

            // Listen to multi-select changes and reload
            paymentTypesChanged() {
                this.fetchData();
            },

            // Fetch AJAX data from the Controller asynchronously
            async fetchData() {
                if (this.loading) return;
                this.loading = true;
                
                const url = new URL(window.location.href);
                url.searchParams.set('date_range', this.dateRange);
                url.searchParams.set('machine_id', this.selectedMachineId);
                url.searchParams.set('product_id', this.selectedProductId);
                if (this.selectedCompanyId) {
                    url.searchParams.set('company_id', this.selectedCompanyId);
                }

                // Query payment types hidden inputs from the multi-select component in the DOM
                const selectedPayments = Array.from(document.querySelectorAll('input[name="payment_types[]"]')).map(el => el.value);
                if (selectedPayments.length > 0) {
                    url.searchParams.set('payment_types', selectedPayments.join(','));
                } else {
                    url.searchParams.delete('payment_types');
                }

                try {
                    const res = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        this.summary = data.summary;
                        this.chartData = data.chart_data;
                        this.tableData = data.table_data;
                        this.currentPage = 1; // Reset to page 1 on filter
                        
                        this.$nextTick(() => {
                            this.updateChart(data.chart_data);
                        });
                        
                        // Push standard history state clean
                        window.history.pushState({}, '', url.toString());
                    }
                } catch (e) {
                    console.error('Failed to reload report data:', e);
                    window.showToast?.('加載報表失敗，請重新嘗試', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // Sort column utility
            sortBy(field) {
                if (this.sortField === field) {
                    this.sortAsc = !this.sortAsc;
                } else {
                    this.sortField = field;
                    this.sortAsc = true;
                }
                this.currentPage = 1;
            },

            // Computed: client-side filtered and sorted machine rows
            get filteredTableData() {
                let data = [...this.tableData];
                
                // Client-side instant keyword search
                if (this.searchQuery) {
                    const q = this.searchQuery.toLowerCase().trim();
                    data = data.filter(row => {
                        const nameMatch = row.name ? row.name.toLowerCase().includes(q) : false;
                        const serialMatch = row.serial_no ? row.serial_no.toLowerCase().includes(q) : false;
                        return nameMatch || serialMatch;
                    });
                }
                
                // Client-side quick sorting
                data.sort((a, b) => {
                    let valA, valB;
                    
                    if (this.sortField === 'margin') {
                        valA = a.total_sales > 0 ? (a.total_profit / a.total_sales) : 0;
                        valB = b.total_sales > 0 ? (b.total_profit / b.total_sales) : 0;
                    } else {
                        valA = a[this.sortField];
                        valB = b[this.sortField];
                    }

                    // Handle numerical / string comparison
                    if (typeof valA === 'string') {
                        return this.sortAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
                    } else {
                        return this.sortAsc ? (valA - valB) : (valB - valA);
                    }
                });

                return data;
            },

            // Computed: sliced rows based on pagination settings
            get paginatedTableData() {
                const start = (this.currentPage - 1) * this.perPage;
                const end = start + parseInt(this.perPage);
                return this.filteredTableData.slice(start, end);
            },

            // Computed: total calculated pages
            get totalPages() {
                return Math.ceil(this.filteredTableData.length / this.perPage);
            },

            // Pagination UI text label generator
            get paginationLabel() {
                const total = this.filteredTableData.length;
                if (total === 0) return '顯示第 0 至 0 筆，共 0 筆';
                const start = (this.currentPage - 1) * this.perPage + 1;
                const end = Math.min(start + parseInt(this.perPage) - 1, total);
                return `顯示第 ${start} 至 ${end} 筆，共 ${total} 筆`;
            },

            prevPage() {
                if (this.currentPage > 1) this.currentPage--;
            },

            nextPage() {
                if (this.currentPage < this.totalPages) this.currentPage++;
            },

            // Exporter Module: Copy formatted TSV data into system Clipboard
            copyTableData() {
                let tsv = '排行\t機台編號\t機台名稱\t銷售數量\t銷售金額\t商品成本\t銷售利潤\t毛利率\n';
                this.filteredTableData.forEach((row, idx) => {
                    const margin = row.total_sales > 0 ? ((row.total_profit / row.total_sales) * 100).toFixed(1) + '%' : '0.0%';
                    tsv += `${idx + 1}\t${row.serial_no || '-'}\t${row.name}\t${row.total_quantity}\t${row.total_sales}\t${row.total_cost}\t${row.total_profit}\t${margin}\n`;
                });
                
                navigator.clipboard.writeText(tsv).then(() => {
                    window.showToast?.('已成功將明細複製至剪貼簿', 'success');
                }).catch(err => {
                    console.error('Clipboard copy failed:', err);
                    window.showToast?.('複製失敗，瀏覽器權限可能不足', 'error');
                });
            },

            // Exporter Module: Export UTF-8 BOM CSV for direct MS Excel compatibility
            exportCSV() {
                let csv = '排行,機台編號,機台名稱,銷售數量,銷售金額,商品成本,銷售利潤,毛利率\n';
                this.filteredTableData.forEach((row, idx) => {
                    const margin = row.total_sales > 0 ? ((row.total_profit / row.total_sales) * 100).toFixed(1) + '%' : '0.0%';
                    const escapedName = `"${(row.name || '').replace(/"/g, '""')}"`;
                    csv += `${idx + 1},${row.serial_no || '-'},${escapedName},${row.total_quantity},${row.total_sales},${row.total_cost},${row.total_profit},${margin}\n`;
                });
                
                const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", `機台營運分析報表_${this.dateRange.replace(/ /g, '')}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.showToast?.('CSV 報表導出成功', 'success');
            },

            // Exporter Module: Generate full structured MS Excel XML/HTML spreadsheet
            exportExcel() {
                let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">`;
                html += `<head><meta charset="utf-8"/><style>table { border-collapse: collapse; } th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; } th { background-color: #f1f5f9; font-weight: bold; }</style></head><body>`;
                html += `<h2>機台營運分析報表</h2>`;
                html += `<p>統計期間：${this.dateRange}</p>`;
                html += `<table><thead><tr><th>排行</th><th>機台編號</th><th>機台名稱</th><th>銷售數量</th><th>銷售金額</th><th>商品成本</th><th>銷售利潤</th><th>毛利率</th></tr></thead><tbody>`;
                
                this.filteredTableData.forEach((row, idx) => {
                    const margin = row.total_sales > 0 ? ((row.total_profit / row.total_sales) * 100).toFixed(1) + '%' : '0.0%';
                    html += `<tr><td>${idx + 1}</td><td>${row.serial_no || '-'}</td><td>${row.name}</td><td>${row.total_quantity}</td><td>${row.total_sales}</td><td>${row.total_cost}</td><td>${row.total_profit}</td><td>${margin}</td></tr>`;
                });
                
                const totalMargin = this.summary.total_sales > 0 ? ((this.summary.total_profit / this.summary.total_sales) * 100).toFixed(1) + '%' : '0.0%';
                html += `<tr style="font-weight: bold; background-color: #f8fafc;">
                    <td colspan="3">總計 (Total)</td>
                    <td>${this.summary.total_quantity}</td>
                    <td>${this.summary.total_sales}</td>
                    <td>${this.summary.total_cost}</td>
                    <td>${this.summary.total_profit}</td>
                    <td>${totalMargin}</td>
                </tr>`;
                
                html += `</tbody></table></body></html>`;
                
                const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", `機台營運分析報表_${this.dateRange.replace(/ /g, '')}.xls`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.showToast?.('Excel 報表導出成功', 'success');
            },

            // Exporter Module: Format and render optimized standard Page Print layout
            printTable() {
                const printWindow = window.open('', '_blank');
                let html = `<html><head><title>機台營運分析報表 (${this.dateRange})</title>`;
                html += `<style>
                    body { font-family: system-ui, -apple-system, sans-serif; padding: 30px; color: #1e293b; }
                    h1 { font-size: 26px; font-weight: 800; margin-bottom: 5px; }
                    .subtitle { font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 25px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
                    th, td { border: 1px solid #cbd5e1; padding: 12px 14px; text-align: left; }
                    th { background-color: #f1f5f9; font-weight: bold; color: #334155; }
                    .text-right { text-align: right; }
                    .text-success { color: #10b981; font-weight: bold; }
                    .text-danger { color: #f43f5e; font-weight: bold; }
                    @media print {
                        body { padding: 0; }
                    }
                </style></head><body>`;
                html += `<h1>機台營運分析報表</h1>`;
                html += `<div class="subtitle">統計區間：${this.dateRange}</div>`;
                html += `<table><thead><tr><th>排行</th><th>機台編號</th><th>機台名稱</th><th class="text-right">銷售數量</th><th class="text-right">銷售金額</th><th class="text-right">商品成本</th><th class="text-right">銷售利潤</th><th class="text-right">毛利率</th></tr></thead><tbody>`;
                
                this.filteredTableData.forEach((row, idx) => {
                    const margin = row.total_sales > 0 ? ((row.total_profit / row.total_sales) * 100).toFixed(1) + '%' : '0.0%';
                    const profitClass = row.total_profit >= 0 ? 'text-success' : 'text-danger';
                    html += `<tr>
                        <td>${idx + 1}</td>
                        <td>${row.serial_no || '-'}</td>
                        <td>${row.name}</td>
                        <td class="text-right">${row.total_quantity} 件</td>
                        <td class="text-right">NT$ ${Number(row.total_sales).toLocaleString()}</td>
                        <td class="text-right">NT$ ${Number(row.total_cost).toLocaleString()}</td>
                        <td class="text-right ${profitClass}">NT$ ${Number(row.total_profit).toLocaleString()}</td>
                        <td class="text-right">${margin}</td>
                    </tr>`;
                });
                
                const totalMargin = this.summary.total_sales > 0 ? ((this.summary.total_profit / this.summary.total_sales) * 100).toFixed(1) + '%' : '0.0%';
                html += `<tr style="font-weight: bold; background-color: #f8fafc;">
                    <td colspan="3">總計 (Total)</td>
                    <td class="text-right">${this.summary.total_quantity} 件</td>
                    <td class="text-right">NT$ ${Number(this.summary.total_sales).toLocaleString()}</td>
                    <td class="text-right">NT$ ${Number(this.summary.total_cost).toLocaleString()}</td>
                    <td class="text-right">NT$ ${Number(this.summary.total_profit).toLocaleString()}</td>
                    <td class="text-right">${totalMargin}</td>
                </tr>`;
                
                html += `</tbody></table></body></html>`;
                printWindow.document.write(html);
                printWindow.document.close();
                printWindow.print();
            },

            // Standard dual-axis mixed layout chart configuration generator
            updateChart(chartData) {
                const dates = chartData.map(d => d.date);
                const quantities = chartData.map(d => d.quantity);
                const sales = chartData.map(d => d.sales);
                const costs = chartData.map(d => d.cost);

                const options = {
                    series: [
                        {
                            name: '銷售額 (NT$)',
                            type: 'column',
                            data: sales
                        },
                        {
                            name: '商品成本 (NT$)',
                            type: 'column',
                            data: costs
                        },
                        {
                            name: '銷售量 (件)',
                            type: 'line',
                            data: quantities
                        }
                    ],
                    chart: {
                        width: '100%',
                        height: 380,
                        type: 'line',
                        stacked: false,
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        },
                        fontFamily: 'Outfit, Plus Jakarta Sans, sans-serif',
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800,
                            animateGradually: {
                                enabled: true,
                                delay: 150
                            },
                            dynamicAnimation: {
                                enabled: true,
                                speed: 350
                            }
                        }
                    },
                    stroke: {
                        width: [0, 0, 3.5],
                        curve: 'smooth'
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '45%',
                            borderRadius: 6
                        }
                    },
                    colors: ['#8b5cf6', '#10b981', '#06b6d4'], // Purple, Emerald, Cyan
                    fill: {
                        opacity: [0.85, 0.85, 1],
                        gradient: {
                            inverseColors: false,
                            shade: 'light',
                            type: "vertical",
                            opacityFrom: 0.85,
                            opacityTo: 0.55,
                            stops: [0, 100, 100, 100]
                        }
                    },
                    labels: dates,
                    markers: {
                        size: 4,
                        strokeWidth: 2,
                        hover: {
                            size: 6
                        }
                    },
                    xaxis: {
                        type: 'category',
                        labels: {
                            style: {
                                colors: '#94a3b8',
                                fontWeight: 600,
                                fontSize: '10px'
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: [
                        {
                            title: {
                                text: '金額 (NT$)',
                                style: {
                                    color: '#8b5cf6',
                                    fontWeight: 700,
                                    fontSize: '11px'
                                }
                            },
                            labels: {
                                style: {
                                    colors: '#94a3b8',
                                    fontWeight: 600,
                                    fontSize: '10px'
                                },
                                formatter: function (value) {
                                    return '$' + Math.round(value).toLocaleString();
                                }
                            }
                        },
                        {
                            opposite: true,
                            title: {
                                text: '銷售量 (件)',
                                style: {
                                    color: '#06b6d4',
                                    fontWeight: 700,
                                    fontSize: '11px'
                                }
                            },
                            labels: {
                                style: {
                                    colors: '#94a3b8',
                                    fontWeight: 600,
                                    fontSize: '10px'
                                },
                                formatter: function (value) {
                                    return Math.round(value) + ' 件';
                                }
                            }
                        }
                    ],
                    tooltip: {
                        shared: true,
                        intersect: false,
                        theme: 'dark',
                        style: {
                            fontSize: '11px'
                        },
                        y: {
                            formatter: function (y, { series, seriesIndex, dataPointIndex, w }) {
                                if (typeof y !== "undefined") {
                                    if (seriesIndex === 2) return y.toLocaleString() + ' 件';
                                    return 'NT$ ' + y.toLocaleString();
                                }
                                return y;
                            }
                        }
                    },
                    grid: {
                        borderColor: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.05)' : '#f1f5f9',
                        strokeDashArray: 4,
                        padding: {
                            top: 10,
                            right: 25,
                            bottom: 15,
                            left: 25
                        }
                    },
                    legend: {
                        show: false
                    }
                };

                const chartEl = document.querySelector("#machine-trend-chart");
                if (chartEl) {
                    if (this.chart) this.chart.destroy();
                    this.chart = new ApexCharts(chartEl, options);
                    this.chart.render();
                    
                    // Dispatch a resize event slightly after rendering to ensure it fits the container width perfectly
                    setTimeout(() => {
                        window.dispatchEvent(new Event('resize'));
                    }, 100);

                    // Dispatch another resize event after 1000ms to ensure container layout has fully finished animation transition
                    setTimeout(() => {
                        window.dispatchEvent(new Event('resize'));
                    }, 1000);

                    // Add ResizeObserver for bullet-proof responsive layout updates
                    if (window.ResizeObserver) {
                        if (this.resizeObserver) this.resizeObserver.disconnect();
                        this.resizeObserver = new ResizeObserver(() => {
                            if (this.chart && typeof this.chart.windowResize === 'function') {
                                this.chart.windowResize();
                            }
                        });
                        this.resizeObserver.observe(chartEl.parentElement);
                    }
                }
            }
        };
    }
</script>
@endsection
