{{--
    搜尋欄 + 重置按鈕 (x-search-bar)
    鎖定全站搜尋輸入框、搜尋按鈕、重置按鈕的樣式與重置邏輯。

    Props:
    - $action      (string) 必填 — 表單 action URL，通常使用 route()
    - $name        (string) 選填 — 搜尋欄 input name，預設 "search"
    - $value       (string) 選填 — 搜尋欄預填值，通常 request('search')
    - $placeholder (string) 選填 — placeholder 文字
    - $onSubmit    (string) 選填 — Alpine submit handler，預設標準 AJAX fetchPage 方式
    - $width       (string) 選填 — 搜尋框寬度 class，預設 "sm:w-72"

    Slot：
    - $slot — 選填，額外的篩選器（如下拉選單、日期選擇器）

    用法：
    ════════════════════════════════════
    1. 基本用法（AJAX 搜尋）：
       <x-search-bar
           :action="route('admin.machines.index')"
           :value="request('search')"
           :placeholder="__('Search machines...')"
       />

    2. 帶額外篩選器：
       <x-search-bar
           :action="route('admin.warehouses.index')"
           :value="request('search')"
           :placeholder="__('Search...')"
       >
           <x-searchable-select name="type" ... />
       </x-search-bar>

    3. 帶隱藏欄位（如 tab）：
       直接在 x-search-bar 區塊外面的 form 補 hidden input 即可。
       由於此組件自帶 <form>，若需 hidden input 可透過 slot 傳入。
    ════════════════════════════════════

    注意：
    - 重置按鈕邏輯已統一：清除所有 text input → 觸發 submit。
    - 組件自帶 <form>，不需要外層再包 <form>。
--}}

@props([
    'action'      => '',
    'name'        => 'search',
    'value'       => '',
    'placeholder' => '',
    'width'       => 'sm:w-72',
])

<form
    method="GET"
    action="{{ $action }}"
    class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8"
    @submit.prevent="
        const url = new URL($el.action, window.location.origin);
        new FormData($el).forEach((value, key) => {
            if (value.trim()) url.searchParams.set(key, value);
            else url.searchParams.delete(key);
        });
        fetchPage(url.pathname + url.search);
    "
>
    {{-- 額外隱藏欄位 / 額外篩選器（透過 slot 傳入） --}}
    {{ $slot }}

    {{-- 搜尋輸入框 + 按鈕群組 --}}
    <div class="flex items-center gap-3 flex-1 sm:flex-none">
        {{-- 搜尋輸入框 --}}
        <div class="relative group flex-1 sm:flex-none">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </span>
            <input
                type="text"
                name="{{ $name }}"
                value="{{ $value }}"
                placeholder="{{ $placeholder ?: __('Search...') }}"
                class="luxury-input py-2.5 pl-11 pr-4 block w-full {{ $width }} text-sm"
            >
        </div>

        {{-- 搜尋按鈕 + 重置按鈕 --}}
        <div class="flex items-center gap-2 flex-none">
            {{-- 搜尋按鈕 --}}
            <button
                type="submit"
                class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
                title="{{ __('Search') }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>

            {{-- 重置按鈕（統一邏輯：清除所有 text input → 觸發 submit） --}}
            <button
                type="button"
                @click="
                    $el.closest('form').querySelectorAll('input[type=text]').forEach(i => i.value = '');
                    $el.closest('form').dispatchEvent(new Event('submit', { cancelable: true }));
                "
                class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
                title="{{ __('Reset') }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>
</form>
