<form action="{{ route('admin.sales.store-gifts') }}" method="GET"
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

    <div class="w-full md:w-48 relative focus-within:z-20">
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
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Welcome Gift Code') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Name / Machine') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Discount Value') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Usage Status') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Expires At') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Status') }}
                </th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.15em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Actions') }}
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            @forelse ($welcomeGifts as $gift)
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                <td class="px-6 py-6 whitespace-nowrap">
                    @if($gift->status === 'active' && (!$gift->expires_at || $gift->expires_at->isFuture()))
                    <span
                        @click="activeQrCode = '{{ $gift->code }}'; activeTicketUrl = '{{ $gift->ticket_url }}'; showQrModal = true"
                        class="text-lg font-black font-mono text-cyan-600 dark:text-cyan-400 tracking-widest bg-cyan-50 dark:bg-cyan-500/10 px-4 py-1.5 rounded-xl border border-cyan-100 dark:border-cyan-500/20 shadow-sm cursor-pointer hover:bg-cyan-100 dark:hover:bg-cyan-500/20 transition-all">
                        {{ $gift->code }}
                    </span>
                    @else
                    <span class="text-lg font-black font-mono text-cyan-600 dark:text-cyan-400 tracking-widest bg-cyan-50 dark:bg-cyan-500/10 px-4 py-1.5 rounded-xl border border-cyan-100 dark:border-cyan-500/20 shadow-sm">
                        {{ $gift->code }}
                    </span>
                    @endif
                </td>
                <td class="px-6 py-6">
                    <div class="text-base font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">
                        {{ $gift->name }}</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{
                        $gift->machine?->name ?? '-' }}</div>
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    <span class="text-sm font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1 rounded-lg border border-indigo-100 dark:border-indigo-500/20">
                        {{ $gift->discount_label }}
                    </span>
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @if($gift->usage_type === 'unlimited')
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                            {{ __('Unlimited') }} ({{ __('Used: :count', ['count' => $gift->usage_count]) }})
                        </span>
                    @else
                        @if($gift->usage_count > 0)
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/40 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700/60 line-through">
                                {{ __('Used') }}
                            </span>
                        @else
                            <span class="text-xs font-bold text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-500/10 px-2 py-0.5 rounded border border-cyan-100 dark:border-cyan-500/20">
                                {{ __('Once') }}
                            </span>
                        @endif
                    @endif
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @if($gift->expires_at)
                    <div class="text-sm font-black text-slate-600 dark:text-slate-300 font-mono tracking-widest">
                        {{ $gift->expires_at->format('Y-m-d H:i') }}</div>
                    <div class="text-xs font-bold {{ $gift->expires_at->isPast() ? 'text-rose-500' : 'text-slate-400' }} mt-0.5">
                        {{ $gift->expires_at->isPast() ? __('Expired') : $gift->expires_at->diffForHumans() }}
                    </div>
                    @else
                    <span class="text-xs font-black uppercase tracking-widest text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20">
                        {{ __('Permanent') }}
                    </span>
                    @endif
                </td>
                <td class="px-6 py-6 whitespace-nowrap">
                    @php
                    $displayStatus = $gift->status === 'active' && $gift->expires_at?->isPast() ? 'expired'
                    : ($gift->status === 'disabled' ? 'cancelled' : $gift->status);
                    @endphp
                    <x-status-badge :status="$displayStatus" />
                </td>
                <td class="px-6 py-6 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if($gift->status === 'active' && (!$gift->expires_at || $gift->expires_at->isFuture()))
                        <button type="button"
                            @click="activeQrCode = '{{ $gift->code }}'; activeTicketUrl = '{{ $gift->ticket_url }}'; showQrModal = true"
                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300 border border-slate-100 dark:border-slate-800"
                            title="{{ __('View QR Code') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                            </svg>
                        </button>

                        <button type="button" @click="openEditModal({
                                id: {{ $gift->id }},
                                code: '{{ $gift->code }}',
                                name: '{{ addslashes($gift->name) }}',
                                discount_type: '{{ $gift->discount_type }}',
                                discount_value: {{ $gift->discount_value }},
                                input_fold: {{ $gift->input_fold ?? 'null' }},
                                usage_type: '{{ $gift->usage_type }}',
                                usage_limit: {{ $gift->usage_limit ?? 'null' }},
                                expires_at: '{{ $gift->expires_at ? $gift->expires_at->format('Y-m-d\TH:i') : '' }}'
                            })"
                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300 border border-slate-100 dark:border-slate-800"
                            title="{{ __('Edit') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </button>
                        @endif

                        @if($gift->status === 'active')
                        <form id="delete-form-desktop-{{ $gift->id }}"
                            action="{{ route('admin.sales.store-gifts.destroy', $gift) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                @click="confirmDelete('delete-form-desktop-{{ $gift->id }}')"
                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-500/5 transition-all border border-transparent hover:border-rose-500/20"
                                title="{{ __('Cancel Gift') }}">
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
            <x-empty-state mode="table" :colspan="7" :message="__('No welcome gifts found')" />
            @endforelse
        </tbody>
    </table>
</div>

{{-- Card Grid (Mobile) --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse ($welcomeGifts as $gift)
    <div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">
        {{-- Card Header --}}
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.5-.38-2.5-1.5 0-1.002 1.042-1.5 2.5-1.5s2.5.498 2.5 1.5c0 1.12-1.145 1.5-2.5 1.5zM3 12v6.75A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V12M3 12h18M3 12a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 12m-9 0v9" />
                    </svg>
                </div>
                <div class="min-w-0">
                    @if($gift->status === 'active' && (!$gift->expires_at || $gift->expires_at->isFuture()))
                    <h3 @click="activeQrCode = '{{ $gift->code }}'; activeTicketUrl = '{{ $gift->ticket_url }}'; showQrModal = true"
                        class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight cursor-pointer">
                        {{ $gift->code }}
                    </h3>
                    @else
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate tracking-tight">
                        {{ $gift->code }}
                    </h3>
                    @endif
                    <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">
                        {{ $gift->name }}
                    </p>
                </div>
            </div>
            <div class="min-w-0">
                @php
                $displayStatusMobile = $gift->status === 'active' && $gift->expires_at?->isPast() ?
                'expired' : ($gift->status === 'disabled' ? 'cancelled' : $gift->status);
                @endphp
                <x-status-badge :status="$displayStatusMobile" size="sm" />
            </div>
        </div>

        {{-- Info Grid --}}
        <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                    {{ __('Machine') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate">
                    {{ $gift->machine?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                    {{ __('Discount Value') }}</p>
                <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $gift->discount_label }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                    {{ __('Usage Status') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                    @if($gift->usage_type === 'unlimited')
                        {{ __('Unlimited') }} ({{ $gift->usage_count }})
                    @else
                        @if($gift->usage_count > 0)
                            <span class="text-slate-400 dark:text-slate-500 line-through">{{ __('Used') }}</span>
                        @else
                            <span class="text-cyan-600 dark:text-cyan-400">{{ __('Once') }}</span>
                        @endif
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">
                    {{ __('Expires At') }}</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono tracking-tighter">
                    {{ $gift->expires_at ? $gift->expires_at->format('Y-m-d H:i') : __('Permanent') }}
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-3">
            @if($gift->status === 'active' && (!$gift->expires_at || $gift->expires_at->isFuture()))
            <button type="button"
                @click="activeQrCode = '{{ $gift->code }}'; activeTicketUrl = '{{ $gift->ticket_url }}'; showQrModal = true"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest border border-slate-100 dark:border-slate-800 hover:text-emerald-500 hover:bg-emerald-500/5 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                </svg>
                {{ __('QR Code') }}
            </button>

            <button type="button" @click="openEditModal({
                    id: {{ $gift->id }},
                    code: '{{ $gift->code }}',
                    name: '{{ addslashes($gift->name) }}',
                    discount_type: '{{ $gift->discount_type }}',
                    discount_value: {{ $gift->discount_value }},
                    input_fold: {{ $gift->input_fold ?? 'null' }},
                    usage_type: '{{ $gift->usage_type }}',
                    usage_limit: {{ $gift->usage_limit ?? 'null' }},
                    expires_at: '{{ $gift->expires_at ? $gift->expires_at->format('Y-m-d\TH:i') : '' }}'
                })"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest border border-slate-100 dark:border-slate-800 hover:text-cyan-500 hover:bg-cyan-500/5 transition-all duration-300">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                {{ __('Edit') }}
            </button>
            @endif

            @if($gift->status === 'active')
            <form id="delete-form-mobile-{{ $gift->id }}"
                action="{{ route('admin.sales.store-gifts.destroy', $gift) }}" method="POST" class="flex-1">
                @csrf
                @method('DELETE')
                <button type="button" @click="confirmDelete('delete-form-mobile-{{ $gift->id }}')"
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
        <x-empty-state :message="__('No welcome gifts found')" />
    </div>
    @endforelse
</div>

{{-- Pagination --}}
<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $welcomeGifts->appends(request()->query())->links('vendor.pagination.luxury') }}
</div>
