{{--
    空狀態顯示 (x-empty-state)
    用於資料列表無結果時的空狀態顯示。支援兩種使用情境：
    1. 表格 <td> 內（`mode="table"`）
    2. 獨立 Card 區塊（`mode="card"`，預設）

    Props:
    - $message  (string) 選填 — 顯示文字，預設 "No data found"
    - $mode     (string) 選填 — 顯示模式，"card"（預設）或 "table"
    - $colspan  (string) 選填 — table 模式下的 colspan 數值，預設 "6"
    - $xIf      (string) 選填 — Alpine.js x-if 條件（for Alpine v-if wrapper）

    Slot:
    - $slot — 選填，自訂 SVG icon（若不傳入使用預設空盒子圖示）

    用法：
    ════════════════════════════════════
    1. 表格 @empty（table mode，在 @forelse ... @empty 中使用）：
       @empty
       <x-empty-state mode="table" :colspan="7" :message="__('No machines found')" />

    2. Card Grid @empty（card mode，在 card grid 的 @empty 中使用）：
       @empty
       <x-empty-state :message="__('No movement records found')" />

    3. Alpine.js 動態控制（template x-if）：
       <template x-if="logs.length === 0 && !loading">
           <x-empty-state :message="__('No matching logs found')" />
       </template>

    4. 自訂 Icon：
       <x-empty-state :message="__('No warehouses configured')">
           <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" ...>...</svg>
       </x-empty-state>
    ════════════════════════════════════
--}}

@props([
    'message' => '',
    'mode'    => 'card',
    'colspan' => '6',
])

@php
    $finalMessage = $message ?: __('No data found');
@endphp

@if($mode === 'table')
    {{-- 表格 td 包裹版 --}}
    <tr>
        <td colspan="{{ $colspan }}" class="px-6 py-20 text-center">
            <div class="flex flex-col items-center justify-center gap-3">
                <div class="p-4 rounded-3xl bg-slate-50 dark:bg-slate-800/50">
                    @if($slot->isNotEmpty())
                        {{ $slot }}
                    @else
                        {{-- 預設空盒子圖示 --}}
                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    @endif
                </div>
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                    {{ $finalMessage }}
                </p>
            </div>
        </td>
    </tr>
@else
    {{-- 獨立 Card 版（用於 card grid 的 @empty，或 Alpine x-if 包裹） --}}
    <div class="col-span-full py-20 text-center flex flex-col items-center gap-3">
        <div class="p-4 rounded-3xl bg-slate-50 dark:bg-slate-800/50">
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            @endif
        </div>
        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
            {{ $finalMessage }}
        </p>
    </div>
@endif
