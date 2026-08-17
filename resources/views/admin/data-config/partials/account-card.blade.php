{{-- Mobile Card: 帳號 - 嚴格遵守 SKILL Mobile Card 三區結構 --}}
<div class="luxury-card p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">

    {{-- 區域一：Card Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-4 min-w-0">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 overflow-hidden shadow-sm shrink-0">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                @endif
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors tracking-tight cursor-default">{{ $user->name }}</h3>
                <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest truncate">{{ $user->username }}</p>
            </div>
        </div>
        <x-status-badge :status="$user->status ? 'active' : 'disabled'" size="sm" />
    </div>

    {{-- 區域二：Info Grid --}}
    <div class="grid grid-cols-2 gap-y-4 mb-6 border-y border-slate-100 dark:border-slate-800/50 py-4">
        @if(auth()->user()->isSystemAdmin())
        <div>
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Affiliation') }}</p>
            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                @if($user->company){{ $user->company->name }}
                @else<span class="text-cyan-500">{{ __('SYSTEM') }}</span>
                @endif
            </p>
        </div>
        @endif
        <div>
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Role') }}</p>
            <div class="flex flex-wrap gap-1">
                @forelse($user->roles as $role)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 uppercase tracking-widest">{{ $role->name }}</span>
                @empty
                    <span class="text-xs text-slate-400">{{ __('No Role') }}</span>
                @endforelse
            </div>
        </div>
        <div class="col-span-2">
            <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">{{ __('Contact Info') }}</p>
            <div class="flex flex-col gap-0.5">
                @if($user->phone)<p class="text-sm font-bold text-slate-700 dark:text-slate-300 tracking-widest">{{ $user->phone }}</p>@endif
                @if($user->email)<p class="text-sm font-bold text-slate-600 dark:text-slate-400 tracking-wide">{{ $user->email }}</p>@endif
                @if(!$user->phone && !$user->email)<p class="text-sm font-bold text-slate-300 dark:text-slate-600">—</p>@endif
            </div>
        </div>
    </div>

    {{-- 區域三：Action Buttons --}}
    <div class="flex items-center gap-3">
        @if(!$user->hasRole('super-admin') || auth()->user()->hasRole('super-admin'))
            <button type="button" @click="openEditModal(@js($user))"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                {{ __('Edit') }}
            </button>
            @if($user->status)
            <button type="button" @click="toggleFormAction = '{{ route($baseRoute . '.status.toggle', $user->id) }}'; statusToggleSource = 'list'; isStatusConfirmOpen = true"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-amber-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/></svg>
                {{ __('Disable') }}
            </button>
            @else
            <button type="button" @click="toggleFormAction = '{{ route($baseRoute . '.status.toggle', $user->id) }}'; $nextTick(() => $refs.statusToggleForm.submit())"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-emerald-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347c-.75.412-1.667-.13-1.667-.986V5.653z"/></svg>
                {{ __('Enable') }}
            </button>
            @endif
            <button type="button" @click="confirmDelete('{{ route($baseRoute . '.destroy', $user->id) }}')"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-rose-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                {{ __('Delete') }}
            </button>
        @else
            <span class="flex-1 text-center text-[10px] font-black text-slate-300 dark:text-slate-600 uppercase tracking-[0.15em]">{{ __('Protected') }}</span>
        @endif
    </div>
</div>
