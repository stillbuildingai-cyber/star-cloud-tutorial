@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-28"
     x-data="machinePricing({{ Js::from($items) }}, '{{ route('admin.machines.pricing.update', $machine) }}')">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.machines.index') }}"
            class="p-2.5 rounded-xl bg-white dark:bg-slate-900 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-all border border-slate-200/50 dark:border-slate-700/50 shadow-sm hover:shadow-md active:scale-95">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white font-display tracking-tight flex items-center gap-3">
                <span class="p-2 rounded-xl bg-cyan-500/10 dark:bg-cyan-500/20">
                    <svg class="w-6 h-6 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                {{ __('Machine Pricing') }}
            </h1>
            <div class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest overflow-hidden">
                <span class="font-mono text-cyan-600 dark:text-cyan-400 truncate">{{ $machine->serial_no }}</span>
                <span class="opacity-50">—</span>
                <span class="truncate">{{ $machine->name }}</span>
            </div>
        </div>
    </div>

    <div class="luxury-card rounded-3xl p-6 sm:p-8 space-y-6">
        {{-- Hint + Search --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium leading-relaxed max-w-2xl">
                {{ __('Leave blank to use the global product price. Member price will be capped at the machine selling price.') }}
            </p>
            <div class="relative w-full sm:w-72 flex-shrink-0">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" x-model="search" class="py-2.5 pl-12 pr-6 block w-full luxury-input"
                    placeholder="{{ __('Search products...') }}">
            </div>
        </div>

        {{-- Empty (no products in company) --}}
        <template x-if="items.length === 0">
            <div class="text-center py-20 text-slate-400 font-bold uppercase tracking-widest text-sm">
                {{ __('No products available for this company') }}
            </div>
        </template>

        {{-- Table --}}
        <div x-show="items.length > 0" class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-200/60 dark:border-slate-700/50">
                        <th class="text-left py-3 px-3">{{ __('Product') }}</th>
                        <th class="text-center py-3 px-3 w-40">{{ __('Machine Price') }}</th>
                        <th class="text-center py-3 px-3 w-40">{{ __('Member Price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in filteredItems" :key="item.product_id">
                        <tr class="border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            {{-- Product --}}
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden flex-shrink-0 flex items-center justify-center text-slate-300">
                                        <template x-if="item.image_url">
                                            <img :src="item.image_url" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!item.image_url">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-black text-slate-800 dark:text-white truncate" x-text="item.name"></div>
                                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">
                                            {{ __('Global') }}: $<span x-text="Math.floor(item.global_price)"></span>
                                            <template x-if="item.global_member_price !== null">
                                                <span> · {{ __('Member') }} $<span x-text="Math.floor(item.global_member_price)"></span></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            {{-- Machine Price --}}
                            <td class="py-3 px-3">
                                <div class="flex items-center h-12 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all overflow-hidden">
                                    <button type="button" @click="bump(item, 'override_price', -1)" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                                    </button>
                                    <div class="flex-1 min-w-[56px]">
                                        <input type="number" min="1" step="1" inputmode="numeric"
                                            x-model="item.override_price"
                                            :placeholder="Math.floor(item.global_price)"
                                            class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    </div>
                                    <button type="button" @click="bump(item, 'override_price', 1)" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    </button>
                                </div>
                            </td>
                            {{-- Member Price --}}
                            <td class="py-3 px-3">
                                <div class="flex items-center h-12 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all overflow-hidden">
                                    <button type="button" @click="bump(item, 'override_member_price', -1)" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                                    </button>
                                    <div class="flex-1 min-w-[56px]">
                                        <input type="number" min="1" step="1" inputmode="numeric"
                                            x-model="item.override_member_price"
                                            :placeholder="item.global_member_price !== null ? Math.floor(item.global_member_price) : '—'"
                                            class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    </div>
                                    <button type="button" @click="bump(item, 'override_member_price', 1)" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="items.length > 0 && filteredItems.length === 0">
                        <tr><td colspan="3" class="text-center py-12 text-slate-400 font-bold uppercase tracking-widest text-sm">{{ __('No matching products') }}</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sticky Save Bar --}}
    <div class="fixed bottom-0 inset-x-0 sm:left-auto sm:right-8 sm:bottom-8 z-40 flex justify-end px-4 sm:px-0">
        <div class="w-full sm:w-auto bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-white/10 rounded-2xl shadow-2xl px-5 py-4 flex items-center justify-end gap-3">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mr-auto sm:mr-2" x-text="overrideCount + ' {{ __('overrides set') }}'"></span>
            <a href="{{ route('admin.machines.index') }}"
                class="px-5 py-2.5 rounded-xl text-sm font-black text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition-colors">
                {{ __('Cancel') }}
            </a>
            <button type="button" @click="save()" :disabled="saving || items.length === 0"
                class="px-6 py-2.5 rounded-xl text-sm font-black bg-cyan-500 text-white shadow-lg shadow-cyan-500/25 hover:shadow-cyan-500/40 hover:-translate-y-0.5 active:translate-y-0 transition-all uppercase tracking-widest disabled:opacity-40 disabled:pointer-events-none flex items-center gap-2">
                <template x-if="saving">
                    <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                </template>
                {{ __('Save & Sync') }}
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('machinePricing', (initialItems, saveUrl) => ({
        // 空字串代表「沿用全域價」，以 placeholder 顯示全域價作為提示
        items: initialItems.map(p => ({
            ...p,
            override_price: p.override_price !== null ? p.override_price : '',
            override_member_price: p.override_member_price !== null ? p.override_member_price : '',
        })),
        saveUrl,
        search: '',
        saving: false,

        get filteredItems() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter(i => (i.name || '').toLowerCase().includes(q) || String(i.barcode || '').toLowerCase().includes(q));
        },

        // +/- 步進：欄位為空(沿用全域)時，從全域價起跳；最低 1（與後端 min:1 一致）
        bump(item, field, delta) {
            const base = field === 'override_member_price'
                ? (item.global_member_price !== null ? item.global_member_price : item.global_price)
                : item.global_price;
            const cur = (item[field] === '' || item[field] === null) ? base : parseInt(item[field]);
            item[field] = Math.max(1, (parseInt(cur) || base) + delta);
        },

        get overrideCount() {
            return this.items.filter(i =>
                (i.override_price !== '' && i.override_price !== null) ||
                (i.override_member_price !== '' && i.override_member_price !== null)
            ).length;
        },

        async save() {
            if (this.saving || this.items.length === 0) return;
            this.saving = true;
            const payload = this.items.map(p => ({
                product_id: p.product_id,
                price: (p.override_price === '' || p.override_price === null) ? null : p.override_price,
                member_price: (p.override_member_price === '' || p.override_member_price === null) ? null : p.override_member_price,
            }));
            try {
                const res = await fetch(this.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ items: payload }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || '{{ __('Saved') }}', type: 'success' } }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || '{{ __('Save failed') }}', type: 'error' } }));
                }
            } catch (e) {
                console.error('Pricing save error:', e);
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Save failed') }}', type: 'error' } }));
            } finally {
                this.saving = false;
            }
        }
    }));
});
</script>
@endsection
