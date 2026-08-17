{{--
    Tab 導覽按鈕 (x-tab-nav-item)
    搭配 x-tab-nav 使用的單一 Tab 按鈕。

    Props:
    - $value  (string) 必填 — Tab 的值（對應 Alpine activeTab 狀態）
    - $label  (string) 必填 — Tab 顯示文字
    - $model  (string) 選填 — 綁定的 Alpine 狀態變數名，預設 "activeTab"

    用法：
    <x-tab-nav-item value="products" :label="__('Product List')" />
    <x-tab-nav-item value="categories" :label="__('Category Management')" model="currentTab" />
--}}

@props([
    'value' => '',
    'label' => '',
    'model' => 'activeTab',
])

<button
    type="button"
    {{ $attributes }}
    @click="{{ $model }} = '{{ $value }}'"
    :class="{{ $model }} === '{{ $value }}'
        ? 'bg-white dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 shadow-sm shadow-cyan-500/10'
        : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
    class="px-6 py-3 rounded-xl text-sm font-black uppercase tracking-widest transition-all duration-300 flex-1 sm:flex-none"
>
    {{ $label }}
</button>
