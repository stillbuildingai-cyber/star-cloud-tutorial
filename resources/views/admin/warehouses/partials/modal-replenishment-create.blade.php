{{-- 新建補貨單 Modal --}}
<div x-show="showReplenishmentModal" class="fixed inset-0 z-[110] overflow-y-auto" style="display: none;" x-cloak>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showReplenishmentModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            @click="showReplenishmentModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:min-h-screen" aria-hidden="true">&#8203;</span>
        <div x-show="showReplenishmentModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            class="relative inline-flex flex-col align-bottom bg-white dark:bg-slate-900 rounded-[2.5rem] text-left shadow-2xl transform sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-slate-100 dark:border-slate-800 z-10 max-h-[90vh]"
            @click.stop>
            <div class="px-10 py-8 pb-4 flex items-center justify-between">
                <div>
                    <h3
                        class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight leading-none mb-3">
                        {{ __('New Replenishment') }}</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Create stock replenishment for specific machines') }}</p>
                </div>
                <button @click="showReplenishmentModal = false"
                    class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700 shadow-sm">
                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-10 py-2 custom-scrollbar overflow-x-visible">
                <form id="replenishmentForm" action="{{ route('admin.warehouses.replenishments.store') }}" method="POST"
                    class="space-y-6 pb-20">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1">{{ __('Source Warehouse') }} <span class="text-rose-500">*</span></label>
                            <x-searchable-select name="warehouse_id" :options="$warehouses ?? []"
                                :placeholder="__('Select Warehouse')" class="w-full" required x-model="fromId"
                                @change="fromId = $event.target.value; fetchStock()" />
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1">{{ __('Target Machine') }} <span class="text-rose-500">*</span></label>
                            <x-searchable-select name="machine_id"
                                :options="$machines->map(fn($m) => (object)['id' => $m->id, 'name' => $m->name . ' (' . $m->serial_no . ')'])"
                                :placeholder="__('Select Machine')" class="w-full" required
                                @change="targetMachineId = $event.target.value; fetchMachineSlots()" />
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1">{{ __('Note') }}</label>
                        <input type="text" name="note"
                            class="luxury-input w-full px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50"
                            placeholder="{{ __('Optional remarks...') }}">
                    </div>
                    <div class="space-y-5">
                        <div class="flex items-center justify-between pl-1">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-[0.15em]">{{ __('Replenishment Items') }} <span class="text-rose-500">*</span></label>
                            <button type="button" @click="addItem()"
                                class="text-xs font-black text-cyan-500 hover:text-cyan-400 uppercase tracking-widest flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                {{ __('Add Product') }}
                            </button>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(item, index) in items" :key="modalOpenCount + '-' + index">
                                <div
                                    class="group flex flex-wrap items-center gap-3 p-3 bg-slate-50/50 dark:bg-slate-900/30 rounded-2xl border border-slate-100 dark:border-slate-800/50 hover:border-cyan-500/30 transition-all">
                                    <div class="w-full sm:w-32 relative" :id="'slot-select-wrapper-' + index"
                                        x-init="$nextTick(() => updateSlotSelects(index))">
                                        <div x-show="isLoadingSlots"
                                            class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 flex items-center justify-center z-10 rounded-xl">
                                            <div
                                                class="w-4 h-4 border-2 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hidden sm:block w-px h-6 bg-slate-200 dark:bg-slate-800/50"></div>
                                    <div class="flex-1 min-w-[200px] relative" :id="'product-select-wrapper-' + index"
                                        x-init="$nextTick(() => updateProductDisplay(index))">
                                    </div>
                                    <div class="hidden sm:block w-px h-6 bg-slate-200 dark:bg-slate-800/50"></div>
                                    <div
                                        class="flex items-center gap-1 bg-slate-100/50 dark:bg-slate-900/50 p-1 rounded-xl border border-slate-200/50 dark:border-slate-800/50 ml-auto sm:ml-0">
                                        <button type="button" @click="item.quantity > 1 ? item.quantity-- : null"
                                            class="p-1.5 text-slate-400 hover:text-cyan-500 transition-colors">
                                            <svg class="w-3 h-3 stroke-[3]" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                            </svg>
                                        </button>
                                        <input type="number" :name="'items['+index+'][quantity]'"
                                            x-model.number="item.quantity" min="1" required
                                            class="w-8 bg-transparent border-none p-0 text-center font-mono font-bold text-slate-800 dark:text-slate-200 focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                            placeholder="0">
                                        <button type="button" @click="item.quantity++"
                                            class="p-1.5 text-slate-400 hover:text-cyan-500 transition-colors">
                                            <svg class="w-3 h-3 stroke-[3]" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <button type="button" @click="removeItem(index)"
                                        class="p-2 text-slate-300 hover:text-rose-500 transition-all"
                                        x-show="items.length > 1">
                                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </form>
            </div>
            <div
                class="px-10 py-6 border-t border-slate-100 dark:border-slate-800/50 flex items-center justify-end gap-4">
                <button type="button" @click="showReplenishmentModal = false" class="btn-luxury-ghost px-8">{{ __('Cancel') }}</button>
                <button type="button" @click="submitReplenishment()" :disabled="loading"
                    class="btn-luxury-primary px-12 relative flex items-center justify-center">
                    <span :class="loading ? 'opacity-0' : ''">{{ __('Create') }}</span>
                    <template x-if="loading">
                        <div class="absolute inset-0 flex items-center justify-center"><svg
                                class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg></div>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>