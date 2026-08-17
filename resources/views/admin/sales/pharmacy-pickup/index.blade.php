@extends('layouts.admin')

@section('content')
@php
    $statusLabels = [
        'awaiting_pickup' => __('Awaiting Pickup'),
        'completed' => __('Picked Up'),
        'failed' => __('Voided'),
        'pending' => __('Pending'),
        'abandoned' => __('Abandoned'),
    ];
    $statusColors = [
        'awaiting_pickup' => 'bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400',
        'completed' => 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400',
        'failed' => 'bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400',
        'pending' => 'bg-slate-500/10 border border-slate-500/20 text-slate-500',
        'abandoned' => 'bg-slate-500/10 border border-slate-500/20 text-slate-500',
    ];
@endphp

<div class="space-y-4 pb-20" x-data="pharmacyPickupManager()">

    {{-- Page Header --}}
    <x-page-header
        :title="__('menu.sales.pharmacy-pickup')"
        :subtitle="__('Issue a pharmacy pickup order, print the QR ticket, patient scans at the machine to dispense.')">
        @if($machines->isNotEmpty())
            <button @click="openCreateModal()"
                class="btn-luxury-primary whitespace-nowrap flex items-center gap-2 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                <span class="text-sm sm:text-base">{{ __('Create Pharmacy Pickup Order') }}</span>
            </button>
        @endif
    </x-page-header>

    {{-- Flash / Errors --}}
    @if(session('success'))
        <div class="p-5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center gap-3 font-bold text-sm animate-luxury-in">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-5 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-2xl flex items-center gap-3 font-bold text-sm animate-luxury-in">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-5 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-2xl text-sm animate-luxury-in">
            <ul class="list-disc ps-5 space-y-1 font-bold">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if($machines->isEmpty())
        <div class="p-5 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 rounded-2xl flex items-start gap-4 font-bold text-sm">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
            {{ __('No machines enabled for pharmacy pickup. Enable it under Machine System Settings (Pickup Sheet mode → Pharmacy Pickup switch).') }}
        </div>
    @endif

    {{-- 領藥單列表 --}}
    <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative overflow-hidden">

        {{-- Desktop Table --}}
        <div class="hidden xl:block overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-0">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Order No.') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Machine') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Items') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Pickup Code') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Status') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">{{ __('Created') }}</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($orders as $order)
                        <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200 {{ session('created_order_id') == $order->id ? 'bg-emerald-50/60 dark:bg-emerald-500/5' : '' }}">
                            <td class="px-6 py-6 whitespace-nowrap">
                                <div class="text-base font-black font-mono text-slate-800 dark:text-slate-100 tracking-tight">{{ $order->order_no }}</div>
                                @if($order->pricing_slip_no)
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-0.5">{{ __('Pricing Slip No.') }}: {{ $order->pricing_slip_no }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-6">
                                <div class="text-base font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">{{ $order->machine?->name ?? '-' }}</div>
                                <div class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-[0.2em]">{{ $order->machine?->serial_no }}</div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="space-y-0.5">
                                    @foreach($order->items as $it)
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $it->product_name }} <span class="text-cyan-600 dark:text-cyan-400 font-black">× {{ $it->quantity }}</span></div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-6 text-center whitespace-nowrap">
                                @if($order->pickupCode?->code)
                                    <span class="text-base font-black font-mono text-cyan-600 dark:text-cyan-400 tracking-widest bg-cyan-50 dark:bg-cyan-500/10 px-3 py-1.5 rounded-xl border border-cyan-100 dark:border-cyan-500/20">{{ $order->pickupCode->code }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-6 text-center whitespace-nowrap">
                                <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusColors[$order->status] ?? 'bg-slate-500/10 border border-slate-500/20 text-slate-500' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <div class="text-sm font-black text-slate-600 dark:text-slate-300 font-mono">{{ $order->created_at?->format('Y-m-d H:i') }}</div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $order->creator?->name }}</div>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($order->status !== 'failed' && $order->pickupCode)
                                        <a href="{{ route('admin.sales.pharmacy-pickup.print', $order) }}" target="_blank"
                                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all"
                                            title="{{ __('Print') }}">
                                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                                        </a>
                                    @endif
                                    @if($order->status === 'awaiting_pickup')
                                        <form id="pp-cancel-desktop-{{ $order->id }}" method="POST" action="{{ route('admin.sales.pharmacy-pickup.cancel', $order) }}">
                                            @csrf
                                            <button type="button" @click="confirmCancel('pp-cancel-desktop-{{ $order->id }}')"
                                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 border border-transparent hover:border-rose-500/20 transition-all"
                                                title="{{ __('Cancel') }}">
                                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-3.38a2.25 2.25 0 00-2.25-2.25h-3.51a2.25 2.25 0 00-2.25 2.25v3.38"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    @if($order->status !== 'awaiting_pickup' && !($order->status !== 'failed' && $order->pickupCode))
                                        <span class="text-slate-300 dark:text-slate-600 pr-2">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state mode="table" :colspan="7" :message="__('No pharmacy pickup orders yet.')" />
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Card Grid --}}
        <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($orders as $order)
                <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group {{ session('created_order_id') == $order->id ? 'ring-2 ring-emerald-500/30' : '' }}">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm shrink-0">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate tracking-tight font-mono">{{ $order->order_no }}</h3>
                                <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">{{ $order->machine?->name ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shrink-0 {{ $statusColors[$order->status] ?? 'bg-slate-500/10 border border-slate-500/20 text-slate-500' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                    </div>

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Pickup Code') }}</p>
                            <p class="text-sm font-black font-mono text-cyan-600 dark:text-cyan-400 tracking-widest">{{ $order->pickupCode?->code ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Pricing Slip No.') }}</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $order->pricing_slip_no ?: '-' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Items') }}</p>
                            <div class="space-y-0.5">
                                @foreach($order->items as $it)
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $it->product_name }} <span class="text-cyan-600 dark:text-cyan-400 font-black">× {{ $it->quantity }}</span></p>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Created') }}</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">{{ $order->created_at?->format('Y-m-d H:i') }} <span class="text-slate-400 font-sans">· {{ $order->creator?->name }}</span></p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    @if(($order->status !== 'failed' && $order->pickupCode) || $order->status === 'awaiting_pickup')
                        <div class="flex items-center gap-3">
                            @if($order->status !== 'failed' && $order->pickupCode)
                                <a href="{{ route('admin.sales.pharmacy-pickup.print', $order) }}" target="_blank"
                                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest border border-slate-100 dark:border-slate-800 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247"/></svg>
                                    {{ __('Print') }}
                                </a>
                            @endif
                            @if($order->status === 'awaiting_pickup')
                                <form id="pp-cancel-mobile-{{ $order->id }}" method="POST" action="{{ route('admin.sales.pharmacy-pickup.cancel', $order) }}" class="flex-1">
                                    @csrf
                                    <button type="button" @click="confirmCancel('pp-cancel-mobile-{{ $order->id }}')"
                                        class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-rose-500/5 text-rose-500 text-xs font-black uppercase tracking-widest border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-300">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79"/></svg>
                                        {{ __('Cancel') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full">
                    <x-empty-state :message="__('No pharmacy pickup orders yet.')" />
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
            {{ $orders->appends(request()->query())->links('vendor.pagination.luxury') }}
        </div>
    </div>

    {{-- Create Pharmacy Pickup Order Modal --}}
    <div x-show="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="showCreateModal = false"></div>

            <div x-show="showCreateModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full overflow-visible">

                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Create Pharmacy Pickup Order') }}</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('admin.sales.pharmacy-pickup.store') }}" method="POST" class="space-y-6"
                    @submit.prevent="validateAndSubmit" x-ref="createForm">
                    @csrf
                    <input type="hidden" name="machine_id" :value="selectedMachine">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Machine --}}
                        <div class="space-y-2 relative focus-within:z-[60]">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Select Machine') }} <span class="text-rose-500">*</span></label>
                            <x-searchable-select name="machine_select" id="pp-machine"
                                placeholder="{{ __('-- Select Machine --') }}" x-model="selectedMachine"
                                @change="selectedMachine = $event.target.value; fetchProducts()">
                                @foreach($machines as $m)
                                    <option value="{{ $m->id }}" data-title="{{ $m->name }} ({{ $m->serial_no }})">{{ $m->name }} ({{ $m->serial_no }})</option>
                                @endforeach
                            </x-searchable-select>
                        </div>

                        {{-- Pricing Slip No. --}}
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Pricing Slip No.') }} <span class="text-slate-300 normal-case font-bold">({{ __('optional') }})</span></label>
                            <input type="text" name="pricing_slip_no" x-model="pricingSlipNo" maxlength="64"
                                @keydown.enter.prevent
                                class="luxury-input w-full py-3 px-4 text-sm" placeholder="{{ __('e.g. the hospital pricing slip number') }}">
                        </div>
                    </div>

                    {{-- Medicine selection --}}
                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Medicine') }} <span class="text-rose-500">*</span></label>

                        {{-- Empty: no machine selected --}}
                        <div x-show="!selectedMachine" class="rounded-[2rem] border border-dashed border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30 p-10 text-center">
                            <p class="text-sm font-bold text-slate-400">{{ __('Please select a machine') }}</p>
                        </div>

                        {{-- Loading --}}
                        <div x-show="selectedMachine && loadingProducts" class="rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30 p-10 flex items-center justify-center">
                            <x-luxury-spinner show="loadingProducts" />
                        </div>

                        {{-- No products --}}
                        <div x-show="selectedMachine && !loadingProducts && products.length === 0" class="rounded-[2rem] border border-amber-500/20 bg-amber-500/5 p-8 text-center">
                            <p class="text-sm font-bold text-amber-600 dark:text-amber-400">{{ __('This machine currently has no medicine in stock.') }}</p>
                        </div>

                        {{-- Product table --}}
                        <div x-show="selectedMachine && !loadingProducts && products.length > 0"
                            class="overflow-hidden rounded-[2rem] border border-slate-100 dark:border-slate-800">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50/70 dark:bg-slate-900/40">
                                    <tr>
                                        <th class="py-3 px-4 text-left w-10"></th>
                                        <th class="py-3 px-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Medicine') }}</th>
                                        <th class="py-3 px-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Spec') }}</th>
                                        <th class="py-3 px-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Available') }}</th>
                                        <th class="py-3 px-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest w-40">{{ __('Quantity') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                    <template x-for="p in products" :key="p.product_id">
                                        <tr class="transition-colors duration-200"
                                            :class="p.checked ? 'bg-cyan-50/40 dark:bg-cyan-500/5' : 'hover:bg-slate-50/50 dark:hover:bg-white/[0.02]'">
                                            <td class="py-3 px-4 text-center">
                                                <input type="checkbox" x-model="p.checked"
                                                    class="w-4 h-4 text-cyan-500 rounded border-slate-300 dark:border-slate-600 focus:ring-cyan-500/30 cursor-pointer">
                                            </td>
                                            <td class="py-3 px-4 font-bold text-slate-700 dark:text-slate-200" x-text="p.name"></td>
                                            <td class="py-3 px-4 text-slate-500" x-text="p.spec || '-'"></td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="font-black text-slate-700 dark:text-slate-200" x-text="p.available"></span>
                                                <template x-if="p.reserved > 0">
                                                    <span class="block text-[10px] font-bold text-slate-400" x-text="'({{ __('physical') }} ' + p.physical + ' − {{ __('reserved') }} ' + p.reserved + ')'"></span>
                                                </template>
                                            </td>
                                            <td class="py-3 px-4">
                                                {{-- Luxury +/- counter --}}
                                                <div class="flex items-center h-10 mx-auto rounded-xl border border-slate-200/70 dark:border-slate-700/50 bg-white dark:bg-slate-900/50 overflow-hidden w-[140px] transition-all"
                                                    :class="{ 'opacity-40 pointer-events-none': !p.checked }">
                                                    <button type="button" @click="p.qty > 1 ? p.qty-- : 1"
                                                        class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                                    </button>
                                                    <div class="h-5 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                                    <input type="text" x-model.number="p.qty" readonly
                                                        class="flex-1 w-full min-w-0 bg-transparent border-none text-center text-base font-black text-slate-800 dark:text-white focus:ring-0 p-0">
                                                    <div class="h-5 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                                    <button type="button" @click="p.qty < p.available ? p.qty++ : p.available"
                                                        class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                                    </button>
                                                </div>
                                                {{-- Submitted only when checked (empty name = skipped) --}}
                                                <input type="hidden" :name="p.checked ? 'products[]' : ''" :value="p.product_id">
                                                <input type="hidden" :name="p.checked ? `qty[${p.product_id}]` : ''" :value="p.qty">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 pl-1">{{ __('(deducted by issued pickup orders)') }}</p>
                    </div>

                    <div class="flex justify-end gap-x-4 pt-4">
                        <button type="button" @click="showCreateModal = false" class="btn-luxury-ghost px-8">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn-luxury-primary px-12 flex items-center gap-2">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Create & Generate QR') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Confirm Cancel Modal --}}
    <x-confirm-modal alpineVar="showCancelModal" :title="__('Cancel Pickup Code')"
        :message="__('Cancel this pharmacy pickup order?')"
        :confirmText="__('Yes, Cancel')" confirmAction="submitCancel()" confirmColor="rose" iconType="danger" />
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pharmacyPickupManager', () => ({
            showCreateModal: false,
            selectedMachine: '',
            pricingSlipNo: '',
            products: [],
            loadingProducts: false,
            showCancelModal: false,
            cancelTargetForm: null,

            openCreateModal() {
                this.selectedMachine = '';
                this.pricingSlipNo = '';
                this.products = [];
                this.showCreateModal = true;
                this.syncSelect('pp-machine', ' ');
            },

            async fetchProducts() {
                if (!this.selectedMachine) {
                    this.products = [];
                    return;
                }
                this.loadingProducts = true;
                try {
                    const res = await fetch(`/admin/sales/pharmacy-pickup/${this.selectedMachine}/products-ajax`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    this.products = (data.products || []).map(p => ({
                        ...p,
                        checked: false,
                        qty: 1,
                    }));
                } catch (e) {
                    console.error('Error fetching products:', e);
                    window.showToast?.('{{ __("Loading failed") }}', 'error');
                    this.products = [];
                } finally {
                    this.loadingProducts = false;
                }
            },

            validateAndSubmit() {
                if (!this.selectedMachine || this.selectedMachine.toString().trim() === '') {
                    window.showToast?.('{{ __("Please select a machine") }}', 'error');
                    return;
                }
                if (!this.products.some(p => p.checked)) {
                    window.showToast?.('{{ __("Please select at least one medicine with quantity.") }}', 'error');
                    return;
                }
                this.$refs.createForm.submit();
            },

            confirmCancel(formId) {
                this.cancelTargetForm = formId;
                this.showCancelModal = true;
            },

            submitCancel() {
                if (this.cancelTargetForm) {
                    const form = document.getElementById(this.cancelTargetForm);
                    if (form) form.submit();
                }
                this.showCancelModal = false;
            },

            syncSelect(id, value) {
                this.$nextTick(() => {
                    const el = document.getElementById(id);
                    if (el) {
                        const valStr = (value !== undefined && value !== null && value.toString().trim() !== '') ? value.toString() : ' ';
                        el.value = valStr;
                        window.HSSelect?.getInstance(el)?.setValue(valStr);
                    }
                });
            },

            init() {
                this.$watch('selectedMachine', (val) => {
                    this.syncSelect('pp-machine', val);
                });
            }
        }));
    });
</script>
@endsection
