{{-- 自動補貨單 Modal --}}
<div x-show="showAutoModal" class="fixed inset-0 z-[110] overflow-y-auto" style="display: none;" x-cloak>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showAutoModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAutoModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:min-h-screen">&#8203;</span>
        <div x-show="showAutoModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" class="relative inline-flex flex-col align-bottom bg-white dark:bg-slate-900 rounded-[2.5rem] text-left shadow-2xl transform sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-slate-100 dark:border-slate-800 z-10 max-h-[90vh]" @click.stop>

            {{-- Header --}}
            <div class="px-10 py-8 pb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight leading-none mb-3">
                        <svg class="w-6 h-6 inline-block text-cyan-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                        {{ __('Auto Replenishment') }}
                    </h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Automatically calculate replenishment needs based on machine capacity') }}</p>
                </div>
                <button @click="showAutoModal = false" class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700 shadow-sm">
                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-10 py-2 custom-scrollbar">
                {{-- Step 1: 選擇倉庫與機台 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1">{{ __('Source Warehouse') }} <span class="text-rose-500">*</span></label>
                        <x-searchable-select name="auto_warehouse_id" :options="$warehouses ?? []" :placeholder="__('Select Warehouse')" class="w-full" @change="autoWarehouseId = $event.target.value; if(autoPreviewLoaded) loadAutoPreview()" />
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1">{{ __('Target Machine') }} <span class="text-rose-500">*</span></label>
                        <x-searchable-select name="auto_machine_id" :options="$machines->map(fn($m) => (object)['id' => $m->id, 'name' => $m->name . ' (' . $m->serial_no . ')'])" :placeholder="__('Select Machine')" class="w-full" @change="autoMachineId = $event.target.value; if(autoPreviewLoaded) loadAutoPreview()" />
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1 mb-3">{{ __('Note') }}</label>
                    <input type="text" x-model="autoNote" class="luxury-input w-full px-6 py-3 bg-slate-50/50 dark:bg-slate-900/50" placeholder="{{ __('Optional remarks...') }}">
                </div>

                {{-- 載入預覽按鈕 --}}
                <div x-show="!autoPreviewLoaded" class="text-center py-6">
                    <button type="button" @click="loadAutoPreview()" :disabled="autoLoading || !autoWarehouseId || !autoMachineId"
                        class="btn-luxury-primary px-10 py-3 relative">
                        <span :class="autoLoading ? 'opacity-0' : ''">{{ __('Calculate Replenishment') }}</span>
                        <template x-if="autoLoading"><div class="absolute inset-0 flex items-center justify-center"><div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div></div></template>
                    </button>
                </div>

                {{-- 庫存不足警告 --}}
                <template x-if="autoPreviewLoaded && autoHasWarning">
                    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-2xl">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            <div>
                                <p class="text-sm font-black text-amber-700 dark:text-amber-400">{{ __('Warehouse stock insufficient warning') }}</p>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- 補貨清單預覽 --}}
                <template x-if="autoPreviewLoaded && autoSlots.length > 0">
                    <div class="space-y-4 pb-6">
                        <div class="flex items-center justify-between px-1">
                            <h4 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">{{ __('Slots need replenishment') }}</h4>
                            <span class="text-[10px] font-black text-cyan-500 bg-cyan-500/10 px-2 py-0.5 rounded-md uppercase" x-text="autoSlots.length + ' {{ __("Items") }}'"></span>
                        </div>
                        {{-- 桌面版：Table 佈局 --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left border-separate border-spacing-y-0">
                                <thead>
                                    <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                                        <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">{{ __('Slot') }}</th>
                                        <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">{{ __('Product') }}</th>
                                        <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Stock / Capacity') }}</th>
                                        <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Fill Quantity') }}</th>
                                        <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-center">{{ __('Warehouse Stock') }}</th>
                                        <th class="w-10 border-b border-slate-100 dark:border-slate-800"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(slot, idx) in autoSlots" :key="slot.slot_no">
                                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all border-b border-slate-50 dark:border-slate-800/30 last:border-0">
                                            <td class="px-4 py-2.5 font-mono font-bold text-slate-700 dark:text-slate-300" x-text="slot.slot_no"></td>
                                            <td class="px-4 py-2.5">
                                                <div class="flex items-center gap-2">
                                                    <template x-if="slot.image_url"><img :src="slot.image_url" class="w-8 h-8 rounded-lg object-cover border border-slate-100 dark:border-slate-800"></template>
                                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate max-w-[150px]" x-text="slot.product_name"></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                                <div class="flex items-baseline justify-center gap-0.5">
                                                    <span class="text-base font-black text-slate-800 dark:text-slate-100 tracking-tighter" x-text="slot.current_stock"></span>
                                                    <span class="text-[10px] font-bold text-slate-400 opacity-50">/</span>
                                                    <span class="text-xs font-bold text-slate-500 opacity-80" x-text="slot.max_stock"></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-center flex justify-center">
                                                <div class="flex items-center gap-1 bg-slate-100/50 dark:bg-slate-900/50 p-1 rounded-xl border border-slate-200/50 dark:border-slate-800/50 w-fit">
                                                    <button type="button" @click="slot.quantity > 1 ? slot.quantity-- : null" class="p-1 text-slate-400 hover:text-cyan-500 transition-colors">
                                                        <svg class="w-2.5 h-2.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                                    </button>
                                                    <input type="number" x-model.number="slot.quantity" min="1" :max="slot.max_stock - slot.current_stock" class="w-8 bg-transparent border-none p-0 text-center font-mono font-bold text-slate-800 dark:text-slate-200 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none text-xs" placeholder="0">
                                                    <button type="button" @click="slot.quantity < (slot.max_stock - slot.current_stock) ? slot.quantity++ : null" class="p-1 text-slate-400 hover:text-cyan-500 transition-colors">
                                                        <svg class="w-2.5 h-2.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-center">
                                                <div class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-black tracking-tight" 
                                                    :class="getActualFillable(idx) < slot.quantity ? 'bg-rose-500/10 text-rose-500' : 'bg-emerald-500/10 text-emerald-500'">
                                                    <span x-text="getActualFillable(idx)"></span>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2.5 text-center">
                                                <button type="button" @click="removeAutoSlot(idx)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-all" title="{{ __('Remove') }}">
                                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- 手機版：Card 佈局 --}}
                        <div class="md:hidden space-y-3">
                            <template x-for="(slot, idx) in autoSlots" :key="'m' + slot.slot_no">
                                <div class="relative bg-slate-50/50 dark:bg-slate-800/30 rounded-2xl border border-slate-100 dark:border-slate-800/50 p-4 transition-all">
                                    {{-- 刪除按鈕 --}}
                                    <button type="button" @click="removeAutoSlot(idx)" class="absolute top-3 right-3 p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-all" title="{{ __('Remove') }}">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>

                                    {{-- 上半部：商品資訊 --}}
                                    <div class="flex items-center gap-3 mb-3 pr-8">
                                        <div class="flex-shrink-0 w-7 h-7 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                                            <span class="text-xs font-black text-cyan-500 font-mono" x-text="slot.slot_no"></span>
                                        </div>
                                        <template x-if="slot.image_url">
                                            <img :src="slot.image_url" class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                        </template>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate" x-text="slot.product_name"></span>
                                    </div>

                                    {{-- 下半部：數據區 --}}
                                    <div class="grid grid-cols-3 gap-2">
                                        {{-- 庫存 / 上限 --}}
                                        <div class="text-center bg-white/60 dark:bg-slate-900/40 rounded-xl py-2 px-1">
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Stock / Capacity') }}</div>
                                            <div class="flex items-baseline justify-center gap-0.5">
                                                <span class="text-base font-black text-slate-800 dark:text-slate-100 tracking-tighter" x-text="slot.current_stock"></span>
                                                <span class="text-[10px] font-bold text-slate-400 opacity-50">/</span>
                                                <span class="text-xs font-bold text-slate-500 opacity-80" x-text="slot.max_stock"></span>
                                            </div>
                                        </div>

                                        {{-- 補貨數量 --}}
                                        <div class="text-center bg-white/60 dark:bg-slate-900/40 rounded-xl py-2 px-1">
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Fill Quantity') }}</div>
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" @click="slot.quantity > 1 ? slot.quantity-- : null" class="p-0.5 text-slate-400 hover:text-cyan-500 transition-colors">
                                                    <svg class="w-3 h-3 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                                </button>
                                                <input type="number" x-model.number="slot.quantity" min="1" :max="slot.max_stock - slot.current_stock" class="w-8 bg-transparent border-none p-0 text-center font-mono font-bold text-sm text-slate-800 dark:text-slate-200 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" placeholder="0">
                                                <button type="button" @click="slot.quantity < (slot.max_stock - slot.current_stock) ? slot.quantity++ : null" class="p-0.5 text-slate-400 hover:text-cyan-500 transition-colors">
                                                    <svg class="w-3 h-3 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- 倉庫數量 --}}
                                        <div class="text-center bg-white/60 dark:bg-slate-900/40 rounded-xl py-2 px-1">
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Warehouse Stock') }}</div>
                                            <div class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-black tracking-tight"
                                                :class="getActualFillable(idx) < slot.quantity ? 'bg-rose-500/10 text-rose-500' : 'bg-emerald-500/10 text-emerald-500'">
                                                <span x-text="getActualFillable(idx)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-10 py-6 border-t border-slate-100 dark:border-slate-800/50 flex items-center justify-end gap-3">
                <button type="button" @click="showAutoModal = false" class="btn-luxury-ghost px-6">{{ __('Cancel') }}</button>
                <template x-if="autoPreviewLoaded">
                    <button type="button" @click="loadAutoPreview()" :disabled="autoLoading" 
                        class="px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-black text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" :class="autoLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        <span>{{ __('Recalculate') }}</span>
                    </button>
                </template>
                <template x-if="autoPreviewLoaded && autoSlots.length > 0">
                    <button type="button" @click="submitAutoReplenishment()" :disabled="autoLoading" class="btn-luxury-primary px-12 relative">
                        <span :class="autoLoading ? 'opacity-0' : ''">{{ __('Create') }}</span>
                        <template x-if="autoLoading"><div class="absolute inset-0 flex items-center justify-center"><div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div></div></template>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
