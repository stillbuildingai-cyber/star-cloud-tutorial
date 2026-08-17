@props([
    'message' => __('Are you sure you want to change the status? This may affect associated accounts.'),
    'title' => __('Confirm Status Change')
])

<template x-teleport="body">
    <div x-show="isStatusConfirmOpen" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isStatusConfirmOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                @click="isStatusConfirmOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="isStatusConfirmOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-8 border border-slate-100 dark:border-slate-800">

                <div class="sm:flex sm:items-start text-center sm:text-left">
                    <div
                        class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-amber-100 dark:bg-amber-500/10 rounded-2xl sm:mx-0 sm:h-12 sm:w-12 text-amber-600 dark:text-amber-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
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
                <div class="mt-8 sm:mt-10 sm:flex sm:flex-row-reverse gap-3">
                    <button type="button" @click="submitConfirmedForm()"
                        class="inline-flex justify-center w-full px-6 py-3 text-sm font-black text-white transition-all bg-amber-500 rounded-xl hover:bg-amber-600 shadow-lg shadow-amber-200 dark:shadow-none hover:scale-[1.02] active:scale-[0.98] sm:w-auto uppercase tracking-widest font-display">
                        {{ __('Confirm') }}
                    </button>
                    <button type="button" @click="isStatusConfirmOpen = false"
                        class="inline-flex justify-center w-full px-6 py-3 mt-3 text-sm font-black text-slate-700 dark:text-slate-200 transition-all bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto uppercase tracking-widest font-display">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
