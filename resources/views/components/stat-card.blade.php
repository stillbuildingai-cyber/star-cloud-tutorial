{{--
    KPI 統計卡片 (x-stat-card)
    用於頁面頂部的摘要數據展示（如倉庫總覽、機台總覽的統計區域）。

    Props:
    - $label        (string) 必填 — 卡片標籤文字
    - $value        (string) 必填 — 顯示的數值
    - $color        (string) 選填 — 標籤色彩，預設 "slate"
                              可選值: slate, cyan, indigo, emerald, amber, rose
    - $delay        (string) 選填 — 進場動畫延遲，例如 "100ms"、"200ms"

    用法：
    ════════════════════════════════════
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-stat-card :label="__('Total Warehouses')" :value="$stats['total']" />
        <x-stat-card :label="__('Main Warehouses')"  :value="$stats['main']"  color="cyan"   delay="100ms" />
        <x-stat-card :label="__('Branch Warehouses')" :value="$stats['branch']" color="indigo" delay="200ms" />
        <x-stat-card :label="__('Low Stock Alerts')"  :value="$stats['low_stock']" color="rose" delay="300ms" />
    </div>

    色彩意象（對應 SKILL.md Section 8）：
    - slate   — 中性統計（預設）
    - cyan    — 主要/強調數據
    - emerald — 正向/健康指標
    - indigo  — 分類/系統設定類
    - amber   — 警告/注意類
    - rose    — 危險/異常/負面指標
    ════════════════════════════════════
--}}

@props([
    'label' => '',
    'value' => '',
    'color' => 'slate',
    'delay' => '',
])

@php
    $labelColors = [
        'slate'   => 'text-slate-500 dark:text-slate-400',
        'cyan'    => 'text-cyan-500',
        'emerald' => 'text-emerald-500',
        'indigo'  => 'text-indigo-500',
        'amber'   => 'text-amber-500',
        'rose'    => 'text-rose-500',
    ];
    $labelClass = $labelColors[$color] ?? $labelColors['slate'];
@endphp

<div
    class="luxury-card p-6 rounded-2xl animate-luxury-in"
    @if($delay) style="animation-delay: {{ $delay }}" @endif
>
    <p class="text-sm font-black uppercase tracking-widest {{ $labelClass }}">
        {{ $label }}
    </p>
    <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">
        {{ $value }}
    </p>
</div>
