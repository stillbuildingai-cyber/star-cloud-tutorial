{{--
    多選下拉組件 (Multi-Select Dropdown)
    純 Alpine.js 實作，不依賴 Preline HSSelect，避免 tags 模式衝突。

    使用方式：
    <x-multi-select
        name="warehouse_ids"
        :options="$warehouses"
        :selected="request('warehouse_ids', [])"
        :placeholder="__('Filter by Warehouse')"
    />

    Props：
    - name       : 表單欄位名稱，會自動加上 [] (string)
    - options     : 選項列表，支援 Collection<{id, name}> 或 associative array (iterable)
    - selected    : 已選值陣列 (array)
    - placeholder : 未選擇時的提示文字 (string)
    - id          : 元素 ID (string, optional)
    - hasSearch   : 是否顯示搜尋框 (bool, default: true)

    事件：
    - 選項變更後會觸發外層 form 的 submit 事件（400ms debounce）
    - 如不需自動提交，加上 no-auto-submit 屬性
--}}

@props([
    'name' => null,
    'options' => [],
    'selected' => [],
    'placeholder' => null,
    'id' => null,
    'hasSearch' => true,
])

@php
    $id = $id ?? $name ?? 'multi-select-' . uniqid();
    $options = is_iterable($options) ? $options : [];
    $placeholder = $placeholder ?: __('Select...');
    $autoSubmit = !$attributes->has('no-auto-submit');

    // 將選項統一為 [{id, name}] 格式
    $normalizedOptions = collect($options)->map(function ($item, $key) {
        if (is_object($item)) {
            return ['id' => $item->id ?? $item->value ?? $key, 'name' => $item->name ?? $item->label ?? $item->title ?? $key];
        }
        return ['id' => $key, 'name' => $item];
    })->values()->toArray();

    // 確保 selected 是陣列
    $selectedArray = is_array($selected) ? array_map('intval', $selected) : [];
@endphp

<div id="{{ $id }}"
     class="relative {{ $attributes->get('class', '') }}"
     x-data="{
        open: false,
        search: '',
        selected: @js($selectedArray),
        options: @js($normalizedOptions),
        autoSubmit: @js($autoSubmit),

        get filtered() {
            if (!this.search) return this.options;
            const s = this.search.toLowerCase();
            return this.options.filter(o => o.name.toString().toLowerCase().includes(s));
        },
        isSelected(id) { return this.selected.includes(id); },
        toggle(id) {
            const i = this.selected.indexOf(id);
            if (i === -1) { this.selected.push(id); } else { this.selected.splice(i, 1); }
            this.onChanged();
        },
        remove(id) {
            this.selected = this.selected.filter(v => v !== id);
            this.onChanged();
        },
        selectAll() {
            this.selected = this.options.map(o => o.id);
            this.onChanged();
        },
        clear() {
            this.selected = [];
            this.onChanged();
        },
        onChanged() {
            if (!this.autoSubmit) return;
            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('multi-select-changed'));
            });
        },
        getName(id) {
            const o = this.options.find(o => o.id === id);
            return o ? o.name : id;
        }
    }"
     @click.outside="open = false"
     @keydown.escape.window="open = false"
     @multi-select-reset.window="clear()"
     {{ $attributes->except(['class', 'options', 'selected', 'placeholder', 'id', 'name', 'hasSearch', 'no-auto-submit']) }}>

    {{-- 隱藏 input 送出表單用 --}}
    <template x-for="id in selected" :key="id">
        <input type="hidden" name="{{ $name }}[]" :value="id">
    </template>

    {{-- Toggle 按鈕 --}}
    <button type="button" @click="open = !open"
        class="luxury-input w-full pr-10 cursor-pointer text-start flex flex-wrap items-center gap-1.5 relative min-h-[46px]">

        {{-- 未選擇時的 placeholder --}}
        <span x-show="selected.length === 0" class="text-slate-400 dark:text-slate-500 font-bold text-sm">
            {{ $placeholder }}
        </span>

        {{-- 選擇後的 tags --}}
        <template x-for="id in selected" :key="'tag-'+id">
            <span class="inline-flex items-center gap-1 bg-cyan-500/10 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 rounded-lg px-2.5 py-1 text-xs font-bold border border-cyan-500/20 dark:border-cyan-500/30">
                <span x-text="getName(id)"></span>
                <button type="button" @click.stop="remove(id)" class="hover:text-cyan-500 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </span>
        </template>

        {{-- 下拉箭頭 --}}
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
            <svg class="size-4 text-slate-400 transition-transform duration-300" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </div>
    </button>

    {{-- 下拉選單 --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute z-[150] mt-2 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden"
         x-cloak>

        {{-- 搜尋框 --}}
        @if($hasSearch)
        <div class="sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10">
            <input type="text" x-model="search"
                   class="block w-full py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500"
                   placeholder="{{ __('Search...') }}" @click.stop>
        </div>
        @endif

        {{-- 全選 / 清除 --}}
        <div class="flex items-center justify-between px-3 py-1.5 border-b border-slate-100 dark:border-slate-800">
            <button type="button" @click.stop="selectAll()"
                    class="text-[11px] font-bold text-cyan-600 dark:text-cyan-400 hover:underline">{{ __('Select All') }}</button>
            <button type="button" @click.stop="clear()"
                    class="text-[11px] font-bold text-slate-400 hover:text-rose-500 hover:underline transition-colors">{{ __('Clear') }}</button>
        </div>

        {{-- 選項列表 --}}
        <div class="max-h-60 overflow-y-auto p-1.5">
            <template x-for="o in filtered" :key="o.id">
                <label @click.stop class="flex items-center gap-3 py-2.5 px-3 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-cyan-500/10 transition-all duration-200 mb-0.5"
                       :class="isSelected(o.id) && 'bg-cyan-50 dark:bg-cyan-500/5'">
                    <input type="checkbox" :checked="isSelected(o.id)" @change="toggle(o.id)"
                           class="rounded border-slate-300 dark:border-slate-600 text-cyan-500 focus:ring-cyan-500 dark:bg-slate-800 cursor-pointer">
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300" x-text="o.name"></span>
                    <svg x-show="isSelected(o.id)" class="w-4 h-4 text-cyan-500 ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </label>
            </template>

            {{-- 無搜尋結果 --}}
            <div x-show="filtered.length === 0" class="py-6 text-center text-sm text-slate-400 font-bold">
                {{ __('No results found') }}
            </div>
        </div>
    </div>
</div>
