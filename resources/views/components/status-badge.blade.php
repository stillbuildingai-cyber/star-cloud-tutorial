{{--
    狀態標籤 (x-status-badge)
    用於表格、清單、詳情頁中的狀態顯示。
    兩種使用模式：靜態（PHP 傳值）、動態（Alpine.js 條件）。

    Props:
    - $status   (string)  選填 — 靜態狀態值（直接渲染）
                          支援預設值: active, inactive, online, offline,
                                     enabled, disabled, pending, warning
    - $label    (string)  選填 — 自訂顯示文字（覆蓋預設文字）
    - $color    (string)  選填 — 自訂色彩（覆蓋預設色彩）
                          可選: emerald, rose, cyan, amber, indigo, slate
    - $xText    (string)  選填 — Alpine.js x-text 表達式（動態模式）
    - $xClass   (string)  選填 — Alpine.js :class 表達式（動態模式）
    - $size     (string)  選填 — 尺寸，預設 "md"，可選 "sm" / "md"

    靜態用法（PHP 渲染）：
    ════════════════════════════════════
    <x-status-badge status="active" />
    <x-status-badge status="offline" />
    <x-status-badge :status="$machine->is_online ? 'online' : 'offline'" />
    <x-status-badge :status="$product->is_active ? 'active' : 'inactive'" />

    動態用法（Alpine.js 控制）：
    ════════════════════════════════════
    <x-status-badge
        :x-text="'selectedProduct?.is_active ? \'' . __('Active') . '\' : \'' . __('Disabled') . '\''"
        :x-class="'selectedProduct?.is_active
            ? \'bg-emerald-500/10 text-emerald-600 border-emerald-500/20\'
            : \'bg-slate-500/10 text-slate-500 border-slate-500/20\''"
    />
    ════════════════════════════════════
--}}

@props([
    'status' => '',
    'label'  => '',
    'color'  => '',
    'xText'  => '',
    'xClass' => '',
    'size'   => 'md',
])

@php
    // 預設狀態對應表
    $presets = [
        'active'   => ['label' => __('Active'),   'color' => 'emerald'],
        'inactive' => ['label' => __('Inactive'), 'color' => 'slate'],
        'online'   => ['label' => __('Online'),   'color' => 'emerald'],
        'offline'  => ['label' => __('Offline'),  'color' => 'slate'],
        'enabled'  => ['label' => __('Enabled'),  'color' => 'emerald'],
        'disabled' => ['label' => __('Disabled'), 'color' => 'slate'],
        'pending'  => ['label' => __('Pending'),  'color' => 'amber'],
        'warning'  => ['label' => __('Warning'),  'color' => 'amber'],
        'error'    => ['label' => __('Error'),    'color' => 'rose'],
        'used'     => ['label' => __('Used'),     'color' => 'indigo'],
        'expired'  => ['label' => __('Expired'),  'color' => 'rose'],
        'cancelled'=> ['label' => __('Cancelled'),'color' => 'slate'],
        'verify_success' => ['label' => __('Verified'), 'color' => 'emerald'],
        'verified'       => ['label' => __('Verified'), 'color' => 'emerald'],
        'consume'        => ['label' => __('Consumed'), 'color' => 'cyan'],
        'completed'      => ['label' => __('Completed'), 'color' => 'cyan'],
        'sent'           => ['label' => __('Sent'),      'color' => 'indigo'],
        'success'        => ['label' => __('Success'),   'color' => 'emerald'],
        'failed'         => ['label' => __('Failed'),    'color' => 'rose'],
        'timeout'        => ['label' => __('Timeout'),   'color' => 'rose'],
        'superseded'     => ['label' => __('Superseded'),'color' => 'slate'],
        
        // 操作紀錄 (Activity Log)
        'create'         => ['label' => __('Create'),    'color' => 'emerald'],
        'update'         => ['label' => __('Update'),    'color' => 'amber'],
        'delete'         => ['label' => __('Delete'),    'color' => 'rose'],
        'created'        => ['label' => __('Created'),   'color' => 'emerald'],
        'updated'        => ['label' => __('Updated'),   'color' => 'amber'],
        'deleted'        => ['label' => __('Deleted'),   'color' => 'rose'],
    ];

    $preset     = $presets[$status] ?? ['label' => $status, 'color' => 'slate'];
    $finalLabel = $label ?: $preset['label'];
    $finalColor = $color ?: $preset['color'];

    // 色彩 class 對應
    $colorClasses = [
        'emerald' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
        'rose'    => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
        'cyan'    => 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20',
        'amber'   => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
        'indigo'  => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
        'slate'   => 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20',
    ];
    $colorClass = $colorClasses[$finalColor] ?? $colorClasses['slate'];

    // 尺寸
    $sizeClass = $size === 'sm'
        ? 'px-2.5 py-1 text-[10px]'
        : 'px-3 py-1.5 text-xs';

    // 判斷是否為動態模式（Alpine.js）
    $isDynamic = $xText || $xClass;
@endphp

<span
    class="inline-flex items-center font-black uppercase tracking-widest rounded-full border transition-all duration-300 {{ $sizeClass }} {{ $isDynamic ? '' : $colorClass }}"
    {{ $xClass ? ':class="' . $xClass . '"' : '' }}
    {{ $xText ? 'x-text="' . $xText . '"' : '' }}
>
    @if(!$isDynamic)
        {{ $finalLabel }}
    @endif
</span>
