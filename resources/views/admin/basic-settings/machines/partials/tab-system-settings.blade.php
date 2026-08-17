<div class="space-y-0">
    <!-- 1. Header with Search and Filter -->
    <div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8">
        <!-- Search Bar -->
        <div class="relative group flex-1 sm:flex-none">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </span>
            <input type="text" x-model="machineSearch" @input.debounce.500ms="searchInTab('system_settings')"
                @keydown.enter.prevent="searchInTab('system_settings')"
                placeholder="{{ __('Search machine name or code...') }}"
                class="luxury-input py-2.5 pl-11 pr-4 block w-full sm:w-72 text-sm font-bold">
        </div>

        @if(auth()->user()->isSystemAdmin())
        <!-- Company Filter -->
        <div class="w-full sm:w-72">
            <x-searchable-select
                id="machine-company-filter-system-settings"
                name="company_filter"
                :options="$companies"
                :selected="request('company_id')"
                x-on:change="machineCompanyId = $event.target.value; searchInTab('system_settings')"
                :placeholder="__('All Companies')" />
        </div>
        @endif

        <div class="flex items-center gap-2 ml-auto sm:ml-0">
            {{-- 搜尋按鈕 --}}
            <button type="button" @click="searchInTab('system_settings')"
                class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
                title="{{ __('Search') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>

            {{-- 重置按鈕 --}}
            <button type="button"
                @click="
                    machineSearch = '';
                    machineCompanyId = '';
                    const select = document.getElementById('machine-company-filter-system-settings');
                    if (select) {
                        select.value = ' ';
                        window.HSSelect?.getInstance(select)?.setValue(' ');
                    }
                    searchInTab('system_settings');
                "
                class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
                title="{{ __('Reset') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>

        {{-- 同步系統設定：明顯的主要操作按鈕，開啟可選機台的確認彈窗 --}}
        <button type="button" @click="openSyncSettingsModal()"
            class="btn-luxury-primary h-[42px] whitespace-nowrap flex items-center justify-center gap-2 transition-all duration-300 w-full sm:w-auto">
            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            <span class="text-sm font-bold uppercase tracking-widest">{{ __('Sync Settings') }}</span>
        </button>
    </div>


    <!-- ── 桌面表格 (xl+) ── -->
    <div class="hidden xl:block overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-y-0">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/30">
                    <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                        {{ __('Machine Information') }}
                    </th>
                    <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                        {{ __('Company') }}
                    </th>
                    <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                        {{ __('Enabled Features') }}
                    </th>
                    <th class="px-6 py-4 text-[12px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 text-right">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                @forelse($machines as $machine)
                    @php
                        $activeFeatures = [];
                        $shoppingMode = $machine->settings['shopping_mode'] ?? 'basic';
                        
                        if ($shoppingMode === 'employee_card') {
                            $activeFeatures[] = __('Staff Card');
                        } elseif ($shoppingMode === 'pickup_sheet') {
                            $activeFeatures[] = __('Pickup Sheet (Material No.)');
                            if ($machine->settings['pharmacy_pickup_enabled'] ?? false) {
                                $activeFeatures[] = __('Pharmacy Pickup (Rx)');
                            }
                        } else {
                            $activeFeatures[] = __('Basic Mode');
                            
                            if ($machine->tax_invoice_enabled)      $activeFeatures[] = __('Electronic Invoice');
                            if ($machine->card_terminal_enabled) {
                                 $terminals = [];
                                 if ($machine->settings['credit_card_enabled'] ?? false) {
                                     $terminals[] = __('Credit Card Payment');
                                 }
                                 if ($machine->settings['mobile_pay_enabled'] ?? false) {
                                     $terminals[] = __('Mobile Payment');
                                 }
                                 if ($machine->settings['card_pay_enabled'] ?? false) {
                                     $terminals[] = __('Card Payment');
                                 }
                                 if (!empty($terminals)) {
                                     $activeFeatures[] = __('Includes Credit Card/Mobile Pay') . ' (' . implode(', ', $terminals) . ')';
                                 } else {
                                     $activeFeatures[] = __('Includes Credit Card/Mobile Pay');
                                 }
                             }
                            if ($machine->settings['scan_pay_enabled'] ?? false) {
                                $scans = [];
                                if ($machine->scan_pay_esun_enabled) $scans[] = __('E.SUN Pay');
                                if ($machine->settings['scan_pay_tappay_enabled'] ?? false) {
                                    $taps = [];
                                    if ($machine->settings['tappay_linepay'] ?? false) $taps[] = __('Line Pay');
                                    if ($machine->settings['tappay_jkopay'] ?? false) $taps[] = __('JKOPAY');
                                    if ($machine->settings['tappay_pipay'] ?? false) $taps[] = __('Pi Wallet');
                                    if ($machine->settings['tappay_pluspay'] ?? false) $taps[] = __('PlusPay');
                                    if ($machine->settings['tappay_easywallet'] ?? false) $taps[] = __('EasyWallet');
                                    if (!empty($taps)) {
                                        $scans[] = 'TapPay (' . implode('/', $taps) . ')';
                                    }
                                }
                                if (!empty($scans)) {
                                    $activeFeatures[] = __('Scan Payment') . ' (' . implode(', ', $scans) . ')';
                                }
                            }
                            if ($machine->settings['scan_pay_linepay_enabled'] ?? false) {
                                $activeFeatures[] = __('LINE Official Pay');
                            }
                            if ($machine->cash_module_enabled) {
                                $bills = [];
                                if (($machine->settings['cash_bill_1000'] ?? true) !== false) $bills[] = '1000';
                                if (($machine->settings['cash_bill_500'] ?? true) !== false) $bills[] = '500';
                                if (($machine->settings['cash_bill_100'] ?? true) !== false) $bills[] = '100';
                                $coins = [];
                                if (($machine->settings['cash_coin_50'] ?? true) !== false) $coins[] = '50';
                                if (($machine->settings['cash_coin_10'] ?? true) !== false) $coins[] = '10';
                                if (($machine->settings['cash_coin_5'] ?? true) !== false) $coins[] = '5';
                                if (($machine->settings['cash_coin_1'] ?? true) !== false) $coins[] = '1';
                                
                                $cashStr = __('Cash Module');
                                if (!empty($bills) || !empty($coins)) {
                                    $cashStr .= ' (';
                                    if (!empty($bills)) $cashStr .= __('Bill') . ':' . implode('/', $bills);
                                    if (!empty($bills) && !empty($coins)) $cashStr .= ' ';
                                    if (!empty($coins)) $cashStr .= __('Coin') . ':' . implode('/', $coins);
                                    $cashStr .= ')';
                                }
                                $activeFeatures[] = $cashStr;
                            }
                            
                            $pickups = [];
                            if ($machine->settings['pickup_code_enabled'] ?? false) $pickups[] = __('Pickup Code');
                            if ($machine->settings['pass_code_enabled'] ?? false) $pickups[] = __('Passcode');
                            if (!empty($pickups)) {
                                $activeFeatures[] = __('Pickup Module') . ' (' . implode('/', $pickups) . ')';
                            }
                            
                            if ($machine->shopping_cart_enabled)    $activeFeatures[] = __('Shopping Cart');
                            if ($machine->welcome_gift_enabled)     $activeFeatures[] = __('Welcome Gift');
                            if ($machine->settings['subcabinet_enabled'] ?? false) $activeFeatures[] = '副櫃系統';
                        }
                        
                        if ($machine->member_system_enabled)    $activeFeatures[] = __('Member System');
                        if ($machine->ambient_temp_monitoring_enabled) $activeFeatures[] = __('Ambient Temperature Monitoring');
                    @endphp
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                        <td class="px-6 py-6 w-1/4">
                            <div class="flex items-center gap-x-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-cyan-500 group-hover:text-white transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">{{ $machine->name }}</span>
                                    <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 tracking-widest uppercase">{{ $machine->serial_no }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-6">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-400 uppercase tracking-tight">{{ $machine->company?->name ?? __('System') }}</span>
                        </td>
                        <td class="px-6 py-6">
                            <div class="flex flex-wrap gap-2">
                                @forelse($activeFeatures as $feature)
                                    <span class="px-2.5 py-1 rounded-lg bg-cyan-500/10 text-cyan-500 dark:text-cyan-400 text-[11px] font-bold uppercase tracking-wider">{{ $feature }}</span>
                                @empty
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-500/10 text-slate-500 dark:text-slate-400 text-[11px] font-bold uppercase tracking-wider">{{ __('None') }}</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- 同步系統設定：開啟確認彈窗 (機台已預選) --}}
                                <button type="button" @click="openSyncSettingsModal({{ $machine->id }})"
                                    class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                    title="{{ __('Sync Settings') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </button>
                                {{-- 編輯系統設定 --}}
                                <button type="button" @click="openMachineSettingsModal({{ json_encode($machine) }})"
                                    class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all border border-transparent hover:border-cyan-500/20"
                                    title="{{ __('Edit Settings') }}">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                <x-empty-state mode="table" :colspan="4" :message="__('No machines found')" />
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ── Mobile / Tablet Card Grid (< xl) ── -->
    <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($machines as $machine)
        @php
            $activeFeatures = [];
            $shoppingMode = $machine->settings['shopping_mode'] ?? 'basic';
            
            if ($shoppingMode === 'employee_card') {
                $activeFeatures[] = __('Staff Card');
            } elseif ($shoppingMode === 'pickup_sheet') {
                $activeFeatures[] = __('Pickup Sheet (Material No.)');
                if ($machine->settings['pharmacy_pickup_enabled'] ?? false) {
                    $activeFeatures[] = __('Pharmacy Pickup (Rx)');
                }
            } else {
                $activeFeatures[] = __('Basic Mode');
                
                if ($machine->tax_invoice_enabled)      $activeFeatures[] = __('Electronic Invoice');
                if ($machine->card_terminal_enabled) {
                     $terminals = [];
                     if ($machine->settings['credit_card_enabled'] ?? false) {
                         $terminals[] = __('Credit Card Payment');
                     }
                     if ($machine->settings['mobile_pay_enabled'] ?? false) {
                         $terminals[] = __('Mobile Payment');
                     }
                     if ($machine->settings['card_pay_enabled'] ?? false) {
                         $terminals[] = __('Card Payment');
                     }
                     if (!empty($terminals)) {
                         $activeFeatures[] = __('Includes Credit Card/Mobile Pay') . ' (' . implode(', ', $terminals) . ')';
                     } else {
                         $activeFeatures[] = __('Includes Credit Card/Mobile Pay');
                     }
                 }
                if ($machine->settings['scan_pay_enabled'] ?? false) {
                    $scans = [];
                    if ($machine->scan_pay_esun_enabled) $scans[] = __('E.SUN Pay');
                    if ($machine->settings['scan_pay_tappay_enabled'] ?? false) {
                        $taps = [];
                        if ($machine->settings['tappay_linepay'] ?? false) $taps[] = __('Line Pay');
                        if ($machine->settings['tappay_jkopay'] ?? false) $taps[] = __('JKOPAY');
                        if ($machine->settings['tappay_pipay'] ?? false) $taps[] = __('Pi Wallet');
                        if ($machine->settings['tappay_pluspay'] ?? false) $taps[] = __('PlusPay');
                        if ($machine->settings['tappay_easywallet'] ?? false) $taps[] = __('EasyWallet');
                        if (!empty($taps)) {
                            $scans[] = 'TapPay (' . implode('/', $taps) . ')';
                        }
                    }
                    if (!empty($scans)) {
                        $activeFeatures[] = __('Scan Payment') . ' (' . implode(', ', $scans) . ')';
                    }
                }
                if ($machine->settings['scan_pay_linepay_enabled'] ?? false) {
                    $activeFeatures[] = __('LINE Official Pay');
                }
                if ($machine->cash_module_enabled) {
                    $bills = [];
                    if (($machine->settings['cash_bill_1000'] ?? true) !== false) $bills[] = '1000';
                    if (($machine->settings['cash_bill_500'] ?? true) !== false) $bills[] = '500';
                    if (($machine->settings['cash_bill_100'] ?? true) !== false) $bills[] = '100';
                    $coins = [];
                    if (($machine->settings['cash_coin_50'] ?? true) !== false) $coins[] = '50';
                    if (($machine->settings['cash_coin_10'] ?? true) !== false) $coins[] = '10';
                    if (($machine->settings['cash_coin_5'] ?? true) !== false) $coins[] = '5';
                    if (($machine->settings['cash_coin_1'] ?? true) !== false) $coins[] = '1';
                    
                    $cashStr = __('Cash Module');
                    if (!empty($bills) || !empty($coins)) {
                        $cashStr .= ' (';
                        if (!empty($bills)) $cashStr .= __('Bill') . ':' . implode('/', $bills);
                        if (!empty($bills) && !empty($coins)) $cashStr .= ' ';
                        if (!empty($coins)) $cashStr .= __('Coin') . ':' . implode('/', $coins);
                        $cashStr .= ')';
                    }
                    $activeFeatures[] = $cashStr;
                }
                
                $pickups = [];
                if ($machine->settings['pickup_code_enabled'] ?? false) $pickups[] = __('Pickup Code');
                if ($machine->settings['pass_code_enabled'] ?? false) $pickups[] = __('Passcode');
                if (!empty($pickups)) {
                    $activeFeatures[] = __('Pickup Module') . ' (' . implode('/', $pickups) . ')';
                }
                
                if ($machine->shopping_cart_enabled)    $activeFeatures[] = __('Shopping Cart');
                if ($machine->welcome_gift_enabled)     $activeFeatures[] = __('Welcome Gift');
                if ($machine->settings['subcabinet_enabled'] ?? false) $activeFeatures[] = '副櫃系統';
            }
            
            if ($machine->member_system_enabled)    $activeFeatures[] = __('Member System');
            if ($machine->ambient_temp_monitoring_enabled) $activeFeatures[] = __('Ambient Temperature Monitoring');
        @endphp
        <div class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">

            {{-- Card Header --}}
            <div class="flex items-center gap-3 mb-5">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm shrink-0">
                    <svg class="w-6 h-6 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate tracking-tight">{{ $machine->name }}</h3>
                    <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">{{ $machine->serial_no }}</p>
                </div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 shrink-0">{{ $machine->company?->name ?? __('System') }}</span>
            </div>

            {{-- Active Features --}}
            <div class="mb-5 border-y border-slate-100 dark:border-slate-800/50 py-4">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">{{ __('Enabled Features') }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @forelse($activeFeatures as $feature)
                    <span class="px-2.5 py-1 rounded-lg bg-cyan-500/10 text-cyan-500 dark:text-cyan-400 text-[10px] font-black uppercase tracking-wider">{{ $feature }}</span>
                    @empty
                    <span class="px-2.5 py-1 rounded-lg bg-slate-500/10 text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-wider">{{ __('None') }}</span>
                    @endforelse
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-3">
                {{-- 同步系統設定：開啟確認彈窗 (機台已預選) --}}
                <button type="button" @click="openSyncSettingsModal({{ $machine->id }})"
                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    {{ __('Sync Settings') }}
                </button>
                {{-- 編輯系統設定 --}}
                <button type="button" @click="openMachineSettingsModal({{ json_encode($machine) }})"
                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                    </svg>
                    {{ __('Edit Settings') }}
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <x-empty-state :message="__('No machines found')" />
        </div>
        @endforelse
    </div>

    <!-- 3. Pagination -->
    <div class="mt-8 border-t border-slate-100 dark:border-slate-800 pt-6">
        {{ $machines->appends(['tab' => 'system_settings', 'search' => request('search'), 'company_id' => request('company_id')])->links('vendor.pagination.luxury') }}
    </div>
</div>
