@props([
'name' => null,
'options' => [],
'selected' => null,
'placeholder' => null,
'id' => null,
'hasSearch' => true,
])

@php
$id = $id ?? $name ?? 'select-' . uniqid();
$options = is_iterable($options) ? $options : [];

// Skill Standard: Use " " for empty/all options to bypass Preline hiding while staying 'blank'
$isEmptySelected = (is_null($selected) || (string)$selected === '' || (string)$selected === ' ');

$config = [
"placeholder" => $placeholder ?: __('Select...'),
"hasSearch" => (bool)$hasSearch,
"searchPlaceholder" => $placeholder ?: __('Search...'),
"isHidePlaceholder" => false,
"searchClasses" => "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
"searchWrapperClasses" => "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
"toggleClasses" => "hs-select-toggle luxury-select-toggle",
"toggleTemplate" => '<button type="button" aria-expanded="false"><span class="me-2" data-icon></span><span class="text-slate-800 dark:text-slate-200" data-title></span><div class="ms-auto"><svg class="size-4 text-slate-400 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg></div></button>',
"dropdownClasses" => "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[150] animate-luxury-in",
"optionClasses" => "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
"optionTemplate" => '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>'
];
@endphp

<div {{ $attributes->merge(['class' => 'relative w-full'])->only('class') }}>
    <select name="{{ $name }}" id="{{ $id }}" data-hs-select='{!! json_encode($config) !!}' class="hidden" {{
        $attributes->except(['options', 'selected', 'placeholder', 'id', 'name', 'class', 'hasSearch']) }}>
        @if($placeholder)
        <option value=" " {{ $isEmptySelected ? 'selected' : '' }} data-title="{{ $placeholder }}">
            {{ $placeholder }}
        </option>
        @endif

        {{ $slot }}

        @foreach($options as $v => $l)
        @php
        $val = is_object($l) ? ($l->id ?? $l->value) : $v;
        $text = is_object($l) ? ($l->name ?? $l->label ?? $l->title) : $l;
        @endphp
        <option value="{{ $val }}" {{ (string)$selected===(string)$val ? 'selected' : '' }} data-title="{{ $text }}">
            {{ $text }}
        </option>
        @endforeach
    </select>
</div>