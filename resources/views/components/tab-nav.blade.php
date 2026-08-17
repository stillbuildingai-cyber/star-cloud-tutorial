{{--
    膠囊式 Tab 導覽列 (x-tab-nav)
    鎖定所有 Tab 頁面的導覽樣式。

    Props:
    - $model  (string) 必填 — Alpine.js 的 x-model 綁定變數名稱，例如 "activeTab"

    Slot:
    - $slot — 必填，傳入 x-tab-nav-item 子組件的集合

    搭配使用 x-tab-nav-item：
    ════════════════════════════════════
    <x-tab-nav model="activeTab">
        <x-tab-nav-item value="products" :label="__('Product List')" />
        <x-tab-nav-item value="categories" :label="__('Category Management')" />
    </x-tab-nav>
    ════════════════════════════════════

    完整用法（搭配 Alpine x-data）：
    父層 div 必須有 x-data，內含 activeTab 狀態變數。
    ════════════════════════════════════
--}}

@props([
    'model' => 'activeTab',
])

<div x-data="{ 
        isScrollable: false, 
        isScrolledToRight: true,
        checkScroll() {
            if (!this.$refs.tabContainer) return;
            const el = this.$refs.tabContainer;
            this.isScrollable = el.scrollWidth > el.clientWidth;
            this.isScrolledToRight = Math.abs(el.scrollWidth - el.clientWidth - el.scrollLeft) < 5;
        }
    }" 
    x-init="checkScroll(); window.addEventListener('resize', () => checkScroll())"
    class="relative w-full sm:w-fit group">
    
    <div
        x-ref="tabContainer"
        @scroll="checkScroll"
        class="flex items-center gap-1 p-1.5 bg-slate-100 dark:bg-slate-900/50 rounded-2xl w-full sm:w-fit border border-slate-200/50 dark:border-slate-800/50 overflow-x-auto whitespace-nowrap custom-scrollbar [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
        aria-label="Tabs"
    >
        {{ $slot }}
    </div>

    <!-- Right Fade & Arrow Indicator -->
    <div x-show="isScrollable && !isScrolledToRight" 
         x-transition.opacity
         class="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l from-slate-100 dark:from-slate-900/90 to-transparent pointer-events-none rounded-r-2xl flex items-center justify-end pr-2 z-10">
         <div class="w-6 h-6 flex items-center justify-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm rounded-full shadow-sm text-slate-400 dark:text-slate-500 animate-pulse border border-slate-200/50 dark:border-slate-700/50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
            </svg>
         </div>
    </div>
</div>
