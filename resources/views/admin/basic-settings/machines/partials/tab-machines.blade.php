{{-- Machines Tab Content (Partial) --}}
{{-- 此檔案被 index.blade.php 的 @include 和 AJAX 模式共用 --}}

{{-- Toolbar 區：搜尋框 + 按鈕 (同列) --}}
<div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-8">
    {{-- 搜尋輸入框 --}}
    <div class="relative group flex-1 sm:flex-none">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </span>
        <input type="text" x-model="machineSearch"
            @keydown.enter.prevent="searchInTab('machines')"
            placeholder="{{ __('Search machines...') }}"
            class="luxury-input py-2.5 pl-11 pr-4 block w-full sm:w-72 text-sm font-bold">
    </div>

    @if(auth()->user()->isSystemAdmin())
    <div class="w-full sm:w-72">
        <x-searchable-select id="machine-company-filter-machines" name="company_filter" :options="$companies" :selected="request('company_id')"
            :placeholder="__('All Companies')"
            x-on:change="machineCompanyId = $event.target.value; searchInTab('machines')" />
    </div>
    @endif

    <div class="flex items-center gap-2 ml-auto sm:ml-0">
        {{-- 搜尋按鈕 --}}
        <button type="button" @click="searchInTab('machines')"
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
                const select = document.getElementById('machine-company-filter-machines');
                if (select) {
                    select.value = ' ';
                    window.HSSelect?.getInstance(select)?.setValue(' ');
                }
                searchInTab('machines');
            "
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
            title="{{ __('Reset') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>
</div>

{{-- ── 桌面表格 (xl+) ── --}}
<div class="hidden xl:block overflow-x-auto">
    <table class="w-full text-left border-separate border-spacing-y-0">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine Info') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine Model') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Owner') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Status') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Key No') }}</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Card Reader') }}</th>
                <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($machines as $machine)
            @php $status = $machine->calculated_status; @endphp
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                <td class="px-6 py-6 cursor-pointer" @click='openDetail({{ $machine->toJson() }}, {{ $machine->id }}, "{{ $machine->serial_no }}")'>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white group-hover:border-cyan-500 shadow-sm group-hover:shadow-cyan-500/50 transition-all duration-300 overflow-hidden">
                            @if(isset($machine->image_urls[0]))
                            <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                            @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            @endif
                        </div>
                        <div>
                            <div class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                                {{ $machine->name }}</div>
                            <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ $machine->serial_no }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-6 cursor-pointer" @click='openDetail({{ $machine->toJson() }}, {{ $machine->id }}, "{{ $machine->serial_no }}")'>
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                        {{ $machine->machineModel->name ?? '--' }}
                    </span>
                </td>
                <td class="px-6 py-6">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest">
                        {{ $machine->company->name ?? __('System') }}
                    </span>
                </td>
                <td class="px-6 py-6">
                    <x-status-badge :status="$status" size="sm" />
                </td>
                <td class="px-6 py-6">
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">
                        {{ $machine->key_no ?? '--' }}
                    </span>
                </td>
                <td class="px-6 py-6">
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                        {{ $machine->card_reader_seconds ?? 0 }}s <span class="text-slate-300 dark:text-slate-700 mx-1.5">/</span> <span class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest">No.{{ $machine->card_reader_no ?? '--' }}</span>
                    </div>
                </td>
                <td class="px-6 py-6 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button @click="openMaintenanceQr(@js($machine->only(['name', 'serial_no'])))"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 dark:hover:bg-emerald-500/10 border border-transparent hover:border-emerald-500/20 transition-all"
                            title="{{ __('Maintenance QR Code') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                            </svg>
                        </button>
                        <button @click="openPhotoModal(@js($machine->only(['id', 'name', 'image_urls'])))"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all"
                            title="{{ __('Machine Images') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </button>
                        <a href="{{ route('admin.basic-settings.machines.edit', $machine) }}"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all"
                            title="{{ __('Edit Settings') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </a>
                        <button
                            @click="openDetail(@js($machine->only(['name', 'serial_no', 'status', 'location', 'last_heartbeat_at', 'card_reader_no', 'card_reader_seconds', 'firmware_version', 'api_token', 'heating_start_time', 'heating_end_time', 'image_urls', 'key_no'])))"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all"
                            title="{{ __('View Details') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                        @if(auth()->user()->isSystemAdmin())
                        <button type="button"
                            @click="confirmDelete('{{ route('admin.basic-settings.machines.destroy', $machine) }}')"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 hover:bg-rose-500/5 dark:hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 transition-all"
                            title="{{ __('Delete') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <x-empty-state mode="table" :colspan="6" :message="__('No data available')" />
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Mobile / Tablet Card Grid (< xl) ── --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4">
    @forelse($machines as $machine)
    @php $status = $machine->calculated_status; @endphp
    <div class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">

        {{-- Card Header --}}
        <div class="flex items-start justify-between gap-4 mb-5">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0"
                    @click='openDetail({{ $machine->toJson() }}, {{ $machine->id }}, "{{ $machine->serial_no }}")'>
                    @if(isset($machine->image_urls[0]))
                    <img src="{{ $machine->image_urls[0] }}" class="w-full h-full object-cover">
                    @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    @endif
                </div>
                <div class="min-w-0 cursor-pointer" @click='openDetail({{ $machine->toJson() }}, {{ $machine->id }}, "{{ $machine->serial_no }}")'>
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight">
                        {{ $machine->name }}
                    </h3>
                    <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                        {{ $machine->serial_no }}
                    </p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest uppercase">
                    {{ $machine->company->name ?? __('System') }}
                </span>
                <x-status-badge :status="$status" size="sm" />
            </div>
        </div>

        {{-- Info Grid --}}
        <div class="grid grid-cols-2 gap-y-4 mb-5 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Machine Model') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $machine->machineModel->name ?? '--' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Card Reader') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">{{ $machine->card_reader_seconds ?? 0 }}s / No.{{ $machine->card_reader_no ?? '--' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Key No') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">{{ $machine->key_no ?? '--' }}</p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2">
            <button @click="openMaintenanceQr(@js($machine->only(['name', 'serial_no'])))"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold hover:bg-emerald-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                title="{{ __('Maintenance QR Code') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                </svg>
            </button>
            <button @click="openPhotoModal(@js($machine->only(['id', 'name', 'image_urls'])))"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                title="{{ __('Machine Images') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </button>
            <a href="{{ route('admin.basic-settings.machines.edit', $machine) }}"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                title="{{ __('Edit') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
            </a>
            <button
                @click="openDetail(@js($machine->only(['name', 'serial_no', 'status', 'location', 'last_heartbeat_at', 'card_reader_no', 'card_reader_seconds', 'firmware_version', 'api_token', 'heating_start_time', 'heating_end_time', 'image_urls', 'key_no'])))"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50"
                title="{{ __('Detail') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </button>
            @if(auth()->user()->isSystemAdmin())
            <button type="button"
                @click="confirmDelete('{{ route('admin.basic-settings.machines.destroy', $machine) }}')"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-rose-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                {{ __('Delete') }}
            </button>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-full">
        <x-empty-state :message="__('No data available')" />
    </div>
    @endforelse
</div>

<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $machines->appends(['tab' => 'machines', 'search' => request('search'), 'company_id' => request('company_id')])->links('vendor.pagination.luxury') }}
</div>
