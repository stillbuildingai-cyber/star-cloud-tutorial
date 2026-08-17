{{--
    極奢華多環加載器 (x-luxury-spinner)
    用於 AJAX 更新時遮蓋整個 luxury-card 內容區域。

    使用前提：父容器必須有 position: relative（如 class="relative"）

    Props:
    - $show   (string)  必填 — Alpine.js 的布林條件表達式，例如 "isLoadingTable"
    - $zIndex (string)  選填 — z-index class，預設 "z-50"
    - $rounded (string) 選填 — rounded class，預設 "rounded-3xl"

    Slot:
    - $slot — 可選，自訂核心圖示。若不傳入則使用預設的燒瓶圖示。

    用法：
    ════════════════════════════════════
    1. 基本用法 (使用預設圖示)：
       <x-luxury-spinner show="isLoadingTable" />

    2. 自訂圖示：
       <x-luxury-spinner show="tabLoading === 'products'">
           <svg class="w-6 h-6 text-cyan-500 animate-pulse" ...>...</svg>
       </x-luxury-spinner>
    ════════════════════════════════════
--}}

@props([
    'show'    => 'isLoadingTable',
    'zIndex'  => 'z-50',
    'rounded' => 'rounded-3xl',
])

<div
    x-show="{{ $show }}"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute inset-0 {{ $zIndex }} {{ $rounded }} flex flex-col items-center justify-center bg-white/40 dark:bg-slate-900/40 backdrop-blur-[1px]"
    x-cloak
>
    {{-- 雙環容器 --}}
    <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
        {{-- 外圈：快速旋轉 --}}
        <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin"></div>
        {{-- 內圈：反向慢速旋轉 --}}
        <div class="absolute inset-2 rounded-full border border-cyan-500/10 animate-spin" style="animation-duration: 3s; direction: reverse;"></div>
        {{-- 核心：脈衝圖示 (可透過 slot 替換) --}}
        <div class="relative w-8 h-8 flex items-center justify-center">
            @if ($slot->isNotEmpty())
                {{ $slot }}
            @else
                {{-- 預設圖示：燒瓶 (通用資料載入) --}}
                <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            @endif
        </div>
    </div>

    {{-- 載入文字 --}}
    <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] animate-pulse">
        {{ __('Loading Data') }}...
    </p>
</div>
