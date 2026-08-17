{{--
    頁面標題區塊 (x-page-header)
    鎖定所有頁面的 h1 標題 + 副標題 + 右側 Action 區樣式。

    Props:
    - $title    (string) 必填 — 頁面主標題（建議使用 __('key') 翻譯）
    - $subtitle (string) 選填 — 副標題描述文字

    Slot:
    - $slot — 選填，右側 Action 按鈕區（btn-luxury-primary 等）

    用法：
    ════════════════════════════════════
    1. 無 Action 按鈕（純標題）：
       <x-page-header
           :title="__('Machine List')"
           :subtitle="__('Manage your machine fleet')"
       />

    2. 帶右側 Action 按鈕：
       <x-page-header
           :title="__('Warehouse Overview')"
           :subtitle="__('Manage your warehouses')"
       >
           <button @click="openCreateModal()" class="btn-luxury-primary flex items-center gap-2">
               <svg class="w-4 h-4" ...>...</svg>
               <span class="text-sm sm:text-base">{{ __('Add') }}</span>
           </button>
       </x-page-header>

    3. 帶 Alpine 動態 Action（如 Tab 控制的按鈕）：
       <x-page-header :title="__('Product Management')" :subtitle="__('...')">
           <template x-if="activeTab === 'products'">
               <a href="..." class="btn-luxury-primary ...">...</a>
           </template>
       </x-page-header>
    ════════════════════════════════════
--}}

@props([
    'title'    => '',
    'subtitle' => '',
    'containerClass' => 'flex items-center justify-between gap-4',
    'actionsClass' => 'flex items-center gap-3 shrink-0',
])

<div class="{{ $containerClass }}">
    {{-- 左側：標題 + 副標題 --}}
    <div class="flex items-center gap-4 min-w-0">
        @if(isset($back))
            <div class="shrink-0">
                {{ $back }}
            </div>
        @endif
        <div class="min-w-0">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white tracking-tight font-display transition-all duration-300 truncate">
                {{ $title }}
            </h1>
            @if($subtitle)
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest truncate">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    </div>

    {{-- 右側：Action 按鈕（透過 slot 傳入，無傳入則不渲染） --}}
    @if($slot->isNotEmpty())
        <div class="{{ $actionsClass }}">
            {{ $slot }}
        </div>
    @endif
</div>
