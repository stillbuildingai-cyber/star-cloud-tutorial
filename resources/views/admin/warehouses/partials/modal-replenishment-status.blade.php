{{-- 狀態更新確認 Modal --}}
<div x-show="showStatusModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showStatusModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showStatusModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div x-show="showStatusModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" class="inline-block px-4 pt-5 pb-4 overflow-visible text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-8 border border-slate-100 dark:border-white/10">
            <div class="sm:flex sm:items-start">
                <div :class="{
                    'bg-cyan-50 dark:bg-cyan-500/10 text-cyan-600': statusTargetValue === 'prepared',
                    'bg-amber-50 dark:bg-amber-500/10 text-amber-600': statusTargetValue === 'delivering',
                    'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600': statusTargetValue === 'completed'
                }" class="flex items-center justify-center flex-shrink-0 w-14 h-14 mx-auto rounded-2xl sm:mx-0">
                    <svg x-show="statusTargetValue === 'prepared'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    <svg x-show="statusTargetValue === 'delivering'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                    <svg x-show="statusTargetValue === 'completed'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="mt-4 text-center sm:mt-0 sm:ms-6 sm:text-left">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight uppercase" x-text="statusModalTitle"></h3>
                    <div class="mt-3">
                        <p class="text-base font-bold text-slate-500 dark:text-slate-400 leading-relaxed" x-text="statusModalMessage"></p>
                    </div>
                </div>
            </div>

            {{-- 指派人員 (僅在備貨或配送階段顯示，或者使用者想在完成時也能指派) --}}
            <div class="mt-8 pt-8 border-t border-slate-100 dark:border-white/5 space-y-4">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 pl-1">{{ __('Assign Personnel (Optional)') }}</label>
                <div class="relative group">
                    <x-searchable-select 
                        id="status-assignee-select"
                        name="assigned_to" 
                        :placeholder="__('Unassigned / No Change')"
                        @change="assignUserId = $event.target.value"
                    >
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" data-title="{{ $u->name }}">{{ $u->name }}</option>
                        @endforeach
                    </x-searchable-select>
                </div>
                <p class="text-[10px] font-bold text-slate-400 pl-1 italic">* {{ __('You can assign or change the personnel handling this order') }}</p>
            </div>

            <div class="mt-8 sm:mt-10 sm:flex sm:flex-row-reverse gap-3">
                <button type="button" @click="executeStatusUpdate()" :disabled="loading"
                    :class="{
                        'bg-cyan-500 hover:bg-cyan-600 shadow-cyan-200': statusTargetValue === 'prepared',
                        'bg-amber-500 hover:bg-amber-600 shadow-amber-200': statusTargetValue === 'delivering',
                        'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-200': statusTargetValue === 'completed'
                    }"
                    class="inline-flex justify-center w-full px-6 py-3 text-sm font-black text-white rounded-xl shadow-lg dark:shadow-none hover:scale-[1.02] active:scale-[0.98] sm:w-auto uppercase tracking-widest transition-all">
                    {{ __('Confirm') }}
                </button>
                <button type="button" @click="showStatusModal = false"
                    class="inline-flex justify-center w-full px-6 py-3 mt-3 text-sm font-black text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto uppercase tracking-widest transition-all">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
