<form action="{{ route('admin.sales.pass-codes') }}" method="GET"
    class="flex flex-col md:flex-row md:items-center gap-3 mb-8"
    @submit.prevent="fetchTabData('list')">

    <div class="relative group flex-1 md:flex-none">
        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10">
            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
        </span>
        <input type="text" name="search" value="{{ request('search') }}"
            class="py-2.5 pl-12 pr-6 block w-full md:w-80 luxury-input"
            placeholder="{{ __('Search by code, name or machine...') }}">
    </div>

    <div class="w-full md:w-48">
        <x-searchable-select name="status" id="filter-status" :placeholder="__('All Status')"
            :selected="request('status')"
            @change="$el.closest('form').dispatchEvent(new Event('submit'))">
            <option value="active" {{ request('status')==='active' ? 'selected' : '' }}
                data-title="{{ __('Active') }}">{{ __('Active') }}</option>
            <option value="expired" {{ request('status')==='expired' ? 'selected' : '' }}
                data-title="{{ __('Expired') }}">{{ __('Expired') }}</option>
            <option value="disabled" {{ request('status')==='disabled' ? 'selected' : '' }}
                data-title="{{ __('Cancelled') }}">{{ __('Cancelled') }}</option>
        </x-searchable-select>
    </div>

    <div class="flex items-center gap-2 ml-auto md:ml-0 shrink-0">
        <button type="submit"
            class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all"
            title="{{ __('Search') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>
        <button type="button"
            @click="
                const selectEl = document.getElementById('filter-status');
                if (selectEl) {
                    selectEl.value = ' ';
                    if (window.HSSelect) {
                        const inst = window.HSSelect.getInstance(selectEl);
                        if (inst) inst.setValue(' ');
                    }
                }
                $el.closest('form').querySelector('input[name=search]').value = '';
                $el.closest('form').dispatchEvent(new Event('submit'));
            "
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all"
            title="{{ __('Reset') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>
</form>

{{-- Table (Desktop) --}}
<div class="hidden xl:block overflow-x-auto">
    <table class="w-full text-left border-separate border-spacing-0">
        <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/10">
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Pass Code') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Name / Machine') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Expires At') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Status') }}
                </th>
                <th
                    class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Actions') }}
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse ($passCodes as $code)
            <tr
                class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                <td class="px-6 py-6 whitespace-nowrap">
                    @if($code->status === 'active' && (!$code->expires_at || $code->expires_at->isFuture()))
                    <span
                        @click="activeQrCode = '{{ $code->code }}'; activeTicketUrl = '{{ $code->ticket_url }}'; showQrModal = true"
                        class="text-lg font-black font-mono text-cyan-600 dark:text-cyan-400 tracking-widest bg-cyan-50 dark:bg-cyan-500/10 px-4 py-1.5 rounded-xl border border-cyan-100 dark:border-cyan-500/20 shadow-sm cursor-pointer hover:bg-cyan-100 dark:hover:bg-cyan-500/20 transition-all">
                        {{ $code->code }}
                    </span>
                    @else
                    <span
                        class="text-lg font-black font-mono text-slate-400 dark:text-slate-600 tracking-widest bg-slate-50 dark:bg-slate-900/50 px-4 py-1.5 rounded-xl border border-slate-100 dark:border-slate-800/50 shadow-sm">
                        {{ $code->code }}
                    </span>
                    @endif
                </td>
                <td class="px-6 py-6">
                    <div class="text-base font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">
                        {{ $code->name }}</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{
                        $code->machine?->name ?? '-' }}</div>
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @if($code->expires_at)
                    <div
                        class="text-sm font-black text-slate-600 dark:text-slate-300 font-mono tracking-widest">
                        {{ $code->expires_at->format('Y-m-d H:i') }}</div>
                    <div
                        class="text-xs font-bold {{ $code->expires_at->isPast() ? 'text-rose-500' : 'text-slate-400' }} mt-0.5">
                        {{ $code->expires_at->isPast() ? __('Expired') : $code->expires_at->diffForHumans()
                        }}
                    </div>
                    @else
                    <span
                        class="text-xs font-black uppercase tracking-widest text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20">{{
                        __('Permanent') }}</span>
                    @endif
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @php
                    $displayStatus = $code->status === 'active' && $code->expires_at?->isPast() ? 'expired'
                    : ($code->status === 'disabled' ? 'cancelled' : $code->status);
                    @endphp
                    <x-status-badge :status="$displayStatus" />
                </td>
                <td class="px-6 py-6 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if($code->status === 'active' && (!$code->expires_at ||
                        $code->expires_at->isFuture()))
                        <button type="button" @click="openEditModal({
                                id: {{ $code->id }},
                                name: '{{ addslashes($code->name) }}',
                                expires_at: '{{ $code->expires_at ? $code->expires_at->format('Y/m/d H:i') : '' }}'
                            })"
                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300 border border-slate-100 dark:border-slate-800"
                            title="{{ __('Edit') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </button>
                        <button type="button"
                            @click="activeQrCode = '{{ $code->code }}'; activeTicketUrl = '{{ $code->ticket_url }}'; showQrModal = true"
                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300 border border-slate-100 dark:border-slate-800"
                            title="{{ __('View QR') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                            </svg>
                        </button>
                        @endif

                        @if($code->status === 'active')
                        <form id="delete-form-desktop-{{ $code->id }}"
                            action="{{ route('admin.sales.pass-codes.destroy', $code) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                @click="confirmDelete('delete-form-desktop-{{ $code->id }}')"
                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20"
                                title="{{ __('Cancel Code') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-3.38a2.25 2.25 0 00-2.25-2.25h-3.51a2.25 2.25 0 00-2.25 2.25v3.38" />
                                </svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <x-empty-state mode="table" :colspan="5" :message="__('No pass codes found')" />
            @endforelse
        </tbody>
    </table>
</div>

{{-- Card Grid (Mobile) --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse ($passCodes as $code)
    <div
        class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
        {{-- Card Header --}}
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 min-w-0">
                <div
                    class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    @if($code->status === 'active' && (!$code->expires_at || $code->expires_at->isFuture()))
                    <h3 @click="activeQrCode = '{{ $code->code }}'; activeTicketUrl = '{{ $code->ticket_url }}'; showQrModal = true"
                        class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight cursor-pointer">
                        {{ $code->code }}
                    </h3>
                    @else
                    <h3
                        class="text-base font-extrabold text-slate-400 dark:text-slate-600 truncate tracking-tight">
                        {{ $code->code }}
                    </h3>
                    @endif
                    <p
                        class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                        {{ $code->name }}
                    </p>
                </div>
            </div>
            <div class="min-w-0">
                @php
                $displayStatusMobile = $code->status === 'active' && $code->expires_at?->isPast() ?
                'expired' : ($code->status === 'disabled' ? 'cancelled' : $code->status);
                @endphp
                <x-status-badge :status="$displayStatusMobile" size="sm" />
            </div>
        </div>

        {{-- Info Grid --}}
        <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p
                    class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                    {{ __('Machine') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">{{
                    $code->machine?->name ?? '-' }}</p>
            </div>
            <div>
                <p
                    class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                    {{ __('Expires At') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono tracking-tighter">
                    {{ $code->expires_at ? $code->expires_at->format('Y-m-d H:i') : __('Permanent') }}
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-3">
            @if($code->status === 'active' && (!$code->expires_at || $code->expires_at->isFuture()))
            <button type="button" @click="openEditModal({
                    id: {{ $code->id }},
                    name: '{{ addslashes($code->name) }}',
                    expires_at: '{{ $code->expires_at ? $code->expires_at->format('Y/m/d H:i') : '' }}'
                })"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest border border-slate-100 dark:border-slate-800 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                {{ __('Edit') }}
            </button>
            <button type="button"
                @click="activeQrCode = '{{ $code->code }}'; activeTicketUrl = '{{ $code->ticket_url }}'; showQrModal = true"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest border border-slate-100 dark:border-slate-800 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                </svg>
                {{ __('QR Code') }}
            </button>
            @endif

            @if($code->status === 'active')
            <form id="delete-form-mobile-{{ $code->id }}"
                action="{{ route('admin.sales.pass-codes.destroy', $code) }}" method="POST" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="button" @click="confirmDelete('delete-form-mobile-{{ $code->id }}')"
                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-rose-500/5 text-rose-500 text-xs font-black uppercase tracking-widest border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all duration-300">
                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-3.38a2.25 2.25 0 00-2.25-2.25h-3.51a2.25 2.25 0 00-2.25 2.25v3.38" />
                    </svg>
                    {{ __('Cancel') }}
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-full">
        <x-empty-state :message="__('No pass codes found')" />
    </div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $passCodes->links('vendor.pagination.luxury') }}
</div>
