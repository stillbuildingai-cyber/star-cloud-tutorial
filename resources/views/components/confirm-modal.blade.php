@props([
    'alpineVar' => 'isOpen',
    'confirmAction' => 'confirm()', // The JS expression to run on confirm
    'iconType' => 'warning', // warning, info, danger, success
    'title' => __('Confirm'),
    'message' => __('Are you sure?'),
    'confirmText' => __('Confirm'),
    'cancelText' => __('Cancel'),
    'confirmColor' => 'sky', // sky, rose, amber, emerald
    'isSubmittingVar' => null, // Optional Alpine variable for submitting state
])

@php
    $iconClasses = [
        'warning' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400',
        'danger' => 'bg-rose-100 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400',
        'info' => 'bg-sky-100 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400',
        'success' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    ][$iconType];

    $btnClasses = [
        'sky' => 'bg-sky-500 hover:bg-sky-600 shadow-sky-200',
        'rose' => 'bg-rose-500 hover:bg-rose-600 shadow-rose-200',
        'amber' => 'bg-amber-500 hover:bg-amber-600 shadow-amber-200',
        'emerald' => 'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-200',
    ][$confirmColor];
@endphp

<template x-teleport="body">
    <div x-show="{{ $alpineVar }}" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="{{ $alpineVar }}" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                @click="{{ $alpineVar }} = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="{{ $alpineVar }}" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-8 border border-slate-100 dark:border-slate-800 relative z-10">

                <div class="sm:flex sm:items-start text-center sm:text-left">
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto rounded-2xl sm:mx-0 sm:h-12 sm:w-12 {{ $iconClasses }}">
                        @if($iconType === 'warning')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        @elseif($iconType === 'danger')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        @elseif($iconType === 'info')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        @elseif($iconType === 'success')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        @endif
                    </div>
                    <div class="mt-3 sm:mt-0 sm:ml-6">
                        <h3 class="text-xl font-black text-slate-800 dark:text-white leading-6 tracking-tight font-display uppercase">
                            {{ $title }}
                        </h3>
                        <div class="mt-4">
                            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 leading-relaxed">
                                {{ $message }}
                            </p>
                        </div>
                    </div>
                </div>
                {{-- 選用插槽：作廢視窗用來放備註輸入框（其他用途留空） --}}
                @if(trim($slot) !== '')
                <div class="mt-6">
                    {{ $slot }}
                </div>
                @endif
                <div class="mt-8 sm:mt-10 sm:flex sm:flex-row-reverse gap-3">
                    <button type="button" @click="{{ $confirmAction }}"
                        @if($isSubmittingVar) :disabled="{{ $isSubmittingVar }}" @endif
                        class="inline-flex items-center justify-center gap-2 w-full px-6 py-3 text-sm font-black text-white transition-all rounded-xl shadow-lg dark:shadow-none hover:scale-[1.02] active:scale-[0.98] sm:w-auto uppercase tracking-widest font-display {{ $btnClasses }} disabled:opacity-60 disabled:cursor-not-allowed">
                        @if($isSubmittingVar)
                        <svg x-show="{{ $isSubmittingVar }}" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        @endif
                        <span>{{ $confirmText }}</span>
                    </button>
                    <button type="button" @click="{{ $alpineVar }} = false"
                        @if($isSubmittingVar) :disabled="{{ $isSubmittingVar }}" @endif
                        class="inline-flex justify-center w-full px-6 py-3 mt-3 text-sm font-black text-slate-700 dark:text-slate-200 transition-all bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto uppercase tracking-widest font-display disabled:opacity-60 disabled:cursor-not-allowed">
                        {{ $cancelText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
