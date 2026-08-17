{{-- 取消確認 Modal --}}
<div x-show="showCancelModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showCancelModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div x-show="showCancelModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-8 border border-slate-100 dark:border-white/10">
            <div class="sm:flex sm:items-start">
                <div class="flex items-center justify-center flex-shrink-0 w-14 h-14 mx-auto bg-rose-50 dark:bg-rose-500/10 rounded-2xl sm:mx-0">
                    <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </div>
                <div class="mt-4 text-center sm:mt-0 sm:ms-6 sm:text-left">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight uppercase">{{ __('Cancel Order') }}</h3>
                    <div class="mt-3">
                        <p class="text-base font-bold text-slate-500 dark:text-slate-400 leading-relaxed">
                            {{ __('Are you sure you want to cancel this order? If the order has been prepared, stock will be returned to the warehouse.') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="mt-8 sm:mt-10 sm:flex sm:flex-row-reverse gap-3">
                <button type="button" @click="executeCancel()" :disabled="loading"
                    class="inline-flex justify-center w-full px-6 py-3 text-sm font-black text-white bg-rose-500 rounded-xl hover:bg-rose-600 shadow-lg shadow-rose-200 dark:shadow-none hover:scale-[1.02] active:scale-[0.98] sm:w-auto uppercase tracking-widest transition-all">
                    {{ __('Confirm Cancel') }}
                </button>
                <button type="button" @click="showCancelModal = false"
                    class="inline-flex justify-center w-full px-6 py-3 mt-3 text-sm font-black text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto uppercase tracking-widest transition-all">
                    {{ __('Go Back') }}
                </button>
            </div>
        </div>
    </div>
</div>
