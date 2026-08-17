{{-- 補貨單詳情 Slide-over --}}
<div x-show="showOrderDetails" class="fixed inset-0 z-[120] overflow-hidden" style="display: none;" x-cloak>
    <div class="absolute inset-0 overflow-hidden">
        <div x-show="showOrderDetails" x-transition:enter="ease-in-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showOrderDetails = false"></div>
        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div x-show="showOrderDetails" x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="pointer-events-auto w-screen max-w-2xl">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-100 dark:border-slate-800">
                    {{-- Header --}}
                    <div class="px-8 py-8 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight leading-none mb-3">{{ __('Replenishment Details') }}</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <template x-if="activeOrder">
                                    <span class="text-cyan-600 dark:text-cyan-400" x-text="activeOrder.order_no"></span>
                                </template>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="window.open('/admin/warehouses/replenishments/' + activeOrder.id + '/print', '_blank')" class="p-2.5 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 text-cyan-500 hover:text-cyan-600 transition-colors border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-center" title="{{ __('Print Order') }}">
                                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.024-.03c-1.285-1.56-1.93-3.19-1.93-5.166C4.766 4.902 6.713 3 9.12 3c2.407 0 4.354 1.902 4.354 4.624 0 1.977-.645 3.607-1.93 5.167-.004.005-.008.01-.012.015L9.12 15.652l-2.4-1.832z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12h2m-2 4h2m-2-8h2M3 12h2m-2 4h2m-2-8h2M12 18H8.5c-.83 0-1.5-.67-1.5-1.5V14m8.5 4H16m0 0v-4.5c0-.83-.67-1.5-1.5-1.5H11" />
                                </svg>
                            </button>
                            <button type="button" @click="showOrderDetails = false" class="p-2.5 rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-400 transition-colors border border-slate-100 dark:border-slate-800 shadow-sm">
                                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="relative flex-1 px-6 py-8">
                        <div x-show="detailsLoading" class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-[2px] z-10 flex items-center justify-center">
                            <div class="w-10 h-10 border-4 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin"></div>
                        </div>
                        <template x-if="activeOrder">
                            <div class="space-y-10">
                                {{-- Info Grid --}}
                                <div class="grid grid-cols-2 gap-6 p-6 bg-slate-50/50 dark:bg-slate-800/30 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                                    <div class="space-y-1">
                                        <p class="text-sm font-black text-slate-400 uppercase tracking-widest">{{ __('Status') }}</p>
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-black uppercase tracking-widest"
                                            :class="{
                                                'bg-amber-500/10 text-amber-500 border border-amber-500/20': activeOrder.status === 'pending',
                                                'bg-cyan-500/10 text-cyan-500 border border-cyan-500/20': activeOrder.status === 'prepared',
                                                'bg-indigo-500/10 text-indigo-500 border border-indigo-500/20': activeOrder.status === 'delivering',
                                                'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20': activeOrder.status === 'completed',
                                                'bg-slate-500/10 text-slate-500 border border-slate-500/20': activeOrder.status === 'cancelled',
                                            }"
                                            x-text="{'pending':'{{ __("Pending") }}','prepared':'{{ __("Prepared") }}','delivering':'{{ __("Delivering") }}','completed':'{{ __("Completed") }}','cancelled':'{{ __("Cancelled") }}'}[activeOrder.status]"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-black text-slate-400 uppercase tracking-widest">{{ __('Assigned To') }}</p>
                                        <p class="text-base font-bold" :class="activeOrder.assignee_name ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400'" x-text="activeOrder.assignee_name || '{{ __("Unassigned") }}'"></p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-black text-slate-400 uppercase tracking-widest">{{ __('Created By') }}</p>
                                        <p class="text-base font-bold text-slate-700 dark:text-slate-300" x-text="activeOrder.creator_name"></p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-black text-slate-400 uppercase tracking-widest">{{ __('Created At') }}</p>
                                        <p class="text-sm font-mono font-bold text-slate-600 dark:text-slate-400" x-text="activeOrder.created_at"></p>
                                    </div>
                                    <template x-if="activeOrder.completed_at">
                                        <div class="space-y-1">
                                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ __('Completed At') }}</p>
                                            <p class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="activeOrder.completed_at"></p>
                                        </div>
                                    </template>
                                    <template x-if="activeOrder.note">
                                        <div class="col-span-2 space-y-1 pt-2 border-t border-slate-200/50 dark:border-slate-700/50">
                                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ __('Note') }}</p>
                                            <p class="text-sm font-bold text-slate-600 dark:text-slate-400 italic" x-text="activeOrder.note"></p>
                                        </div>
                                    </template>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex flex-wrap gap-2">
                                    {{-- 列印按鈕永遠顯示 --}}
                                    <button type="button" @click="window.open('/admin/warehouses/replenishments/' + activeOrder.id + '/print', '_blank')" class="px-4 py-2 rounded-xl text-xs font-black bg-cyan-500 text-white hover:bg-cyan-600 transition-all shadow-lg shadow-cyan-500/10 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.024-.03c-1.285-1.56-1.93-3.19-1.93-5.166C4.766 4.902 6.713 3 9.12 3c2.407 0 4.354 1.902 4.354 4.624 0 1.977-.645 3.607-1.93 5.167-.004.005-.008.01-.012.015L9.12 15.652l-2.4-1.832z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 12h2m-2 4h2m-2-8h2M3 12h2m-2 4h2m-2-8h2M12 18H8.5c-.83 0-1.5-.67-1.5-1.5V14m8.5 4H16m0 0v-4.5c0-.83-.67-1.5-1.5-1.5H11" />
                                        </svg>
                                        {{ __('Print Order') }}
                                    </button>

                                    {{-- 其他需要依狀態顯示的動作 --}}
                                    <template x-if="activeOrder.status !== 'completed' && activeOrder.status !== 'cancelled'">
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="openAssignModal(activeOrder.id)" class="px-4 py-2 rounded-xl text-xs font-black bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700">
                                                {{ __('Assign Personnel') }}
                                            </button>
                                            <template x-if="activeOrder.status === 'pending'">
                                                <button type="button" @click="advanceStatus(activeOrder.id, 'prepared')" class="px-4 py-2 rounded-xl text-xs font-black bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 hover:bg-cyan-500 hover:text-white transition-all">{{ __('Confirm Prepare') }}</button>
                                            </template>
                                            <template x-if="activeOrder.status === 'prepared'">
                                                <button type="button" @click="advanceStatus(activeOrder.id, 'delivering')" class="px-4 py-2 rounded-xl text-xs font-black bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 hover:bg-indigo-500 hover:text-white transition-all">{{ __('Start Delivery') }}</button>
                                            </template>
                                            <template x-if="activeOrder.status === 'delivering'">
                                                <button type="button" @click="advanceStatus(activeOrder.id, 'completed')" class="px-4 py-2 rounded-xl text-xs font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 hover:bg-emerald-500 hover:text-white transition-all">{{ __('Confirm Complete') }}</button>
                                            </template>
                                            <button type="button" @click="confirmCancel(activeOrder.id)" class="px-4 py-2 rounded-xl text-xs font-black bg-rose-500/10 text-rose-500 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all">{{ __('Cancel Order') }}</button>
                                        </div>
                                    </template>
                                </div>

                                {{-- Items List --}}
                                <div class="space-y-5">
                                    <div class="flex items-center justify-between px-1">
                                        <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Replenishment Items') }}</h3>
                                        <span class="text-xs font-black text-cyan-500 bg-cyan-500/10 px-2 py-0.5 rounded-md uppercase" x-text="activeItems.length + ' {{ __("Items") }}'"></span>
                                    </div>
                                    <div class="space-y-3">
                                        <template x-for="(item, idx) in activeItems" :key="idx">
                                            <div class="group flex items-center gap-4 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-cyan-500/30 transition-all" x-data="{ imgFailed: !item.image_url }">
                                                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-mono font-bold text-slate-500 border border-slate-200 dark:border-slate-700" x-text="item.slot_no"></div>
                                                <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center overflow-hidden border border-slate-100 dark:border-slate-800 group-hover:scale-105 transition-transform relative">
                                                    <template x-if="item.image_url">
                                                        <img :src="item.image_url" class="w-full h-full object-cover" x-show="!imgFailed" x-on:error="imgFailed = true">
                                                    </template>
                                                    <div x-show="imgFailed" class="absolute inset-0 flex items-center justify-center bg-slate-50 dark:bg-slate-800/50">
                                                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600 group-hover:text-cyan-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-black text-slate-800 dark:text-slate-100 truncate tracking-tight" x-text="item.product_name"></p>
                                                    <template x-if="item.current_stock !== null && item.max_stock">
                                                        <p class="text-xs font-bold text-slate-400 mt-0.5">
                                                            {{ __('Stock') }}: <span class="text-slate-600 dark:text-slate-300 font-mono" x-text="item.current_stock"></span>
                                                            / <span class="font-mono" x-text="item.max_stock"></span>
                                                        </p>
                                                    </template>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-base font-black text-cyan-600 dark:text-cyan-400 font-mono tracking-tighter" x-text="'x' + item.quantity"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
