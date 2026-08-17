{{-- Models Tab Content (Partial) --}}
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
        <input type="text" x-model="modelSearch"
            @keydown.enter.prevent="searchInTab('models')"
            placeholder="{{ __('Search models...') }}"
            class="luxury-input py-2.5 pl-11 pr-4 block w-full sm:w-72 text-sm font-bold">
    </div>

    <div class="flex items-center gap-2 ml-auto sm:ml-0">
        {{-- 搜尋按鈕 --}}
        <button type="button" @click="searchInTab('models')"
            class="p-2.5 rounded-xl bg-cyan-500 text-white hover:bg-cyan-600 shadow-lg shadow-cyan-500/25 transition-all active:scale-95"
            title="{{ __('Search') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>

        {{-- 重置按鈕 --}}
        <button type="button"
            @click="modelSearch = ''; searchInTab('models')"
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
                <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Model Name') }}</th>
                <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Default Temp Alert Limits') }}</th>
                <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Machine Count') }}</th>
                <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">
                    {{ __('Last Updated') }}</th>
                <th class="px-6 py-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">
                    {{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            @forelse($models_list as $model)
            <tr class="group hover:bg-slate-50/50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                <td class="px-6 py-6">
                    <div class="flex items-center gap-x-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white group-hover:border-cyan-500 shadow-sm group-hover:shadow-cyan-500/50 transition-all duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div class="text-base font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors tracking-tight">
                            {{ $model->name }}</div>
                    </div>
                </td>
                <td class="px-6 py-6">
                    @if(isset($model->settings['temp_upper_limit']) || isset($model->settings['temp_lower_limit']))
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-950/30 text-sky-600 dark:text-sky-400 text-xs font-bold font-mono">
                                {{ isset($model->settings['temp_lower_limit']) ? (int)round($model->settings['temp_lower_limit']) : '-' }}°C
                            </span>
                            <span class="text-slate-400 text-xs font-bold">~</span>
                            <span class="px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 text-xs font-bold font-mono">
                                {{ isset($model->settings['temp_upper_limit']) ? (int)round($model->settings['temp_upper_limit']) : '-' }}°C
                            </span>
                        </div>
                    @else
                        <span class="text-xs text-slate-400 font-bold font-mono">-</span>
                    @endif
                </td>
                <td class="px-6 py-6">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest">
                        {{ $model->machines_count ?? 0 }} {{ __('Items') }}
                    </span>
                </td>
                <td class="px-6 py-6">
                    <div class="text-xs font-black text-slate-400 dark:text-slate-400/80 uppercase tracking-widest font-mono">
                        {{ $model->updated_at->format('Y/m/d H:i') }}
                    </div>
                </td>
                <td class="px-6 py-6 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button @click="currentModel = { name: @js($model->name), temp_upper_limit: @js(isset($model->settings['temp_upper_limit']) ? (string)(int)round($model->settings['temp_upper_limit']) : ''), temp_lower_limit: @js(isset($model->settings['temp_lower_limit']) ? (string)(int)round($model->settings['temp_lower_limit']) : '') }; modelActionUrl = '{{ route('admin.basic-settings.machine-models.update', $model) }}'; showEditModelModal = true"
                            class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-cyan-500 dark:hover:text-cyan-400 hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10 border border-transparent hover:border-cyan-500/20 transition-all"
                            title="{{ __('Edit') }}">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </button>
                        <form :id="'delete-model-form-' + {{ $model->id }}"
                            action="{{ route('admin.basic-settings.machine-models.destroy', $model) }}"
                            method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                @click="confirmDelete('{{ route('admin.basic-settings.machine-models.destroy', $model) }}')"
                                class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 hover:bg-rose-500/5 dark:hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 transition-all"
                                title="{{ __('Delete') }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <x-empty-state mode="table" :colspan="5" :message="__('No data available')" />
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Mobile / Tablet Card Grid (< xl) ── --}}
<div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4">
    @forelse($models_list as $model)
    <div class="luxury-card p-5 rounded-[2rem] border border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 transition-all duration-300 group">

        {{-- Card Header --}}
        <div class="flex items-center gap-3 mb-5">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border border-slate-200 dark:border-slate-700 group-hover:bg-cyan-500 group-hover:text-white transition-all duration-300 shadow-sm shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 truncate tracking-tight">
                    {{ $model->name }}
                </h3>
                <div class="flex flex-wrap items-center gap-x-2 mt-1">
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                        {{ $model->updated_at->format('Y/m/d H:i') }}
                    </p>
                    @if(isset($model->settings['temp_upper_limit']) || isset($model->settings['temp_lower_limit']))
                        <span class="text-xs text-slate-300 dark:text-slate-700 font-bold">•</span>
                        <span class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 font-mono">
                            {{ isset($model->settings['temp_lower_limit']) ? (int)round($model->settings['temp_lower_limit']) : '-' }}°C ~ {{ isset($model->settings['temp_upper_limit']) ? (int)round($model->settings['temp_upper_limit']) : '-' }}°C
                        </span>
                    @endif
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold border border-sky-100 dark:border-sky-900/30 bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 tracking-widest shrink-0">
                {{ $model->machines_count ?? 0 }} {{ __('Items') }}
            </span>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2">
            <button @click="currentModel = { name: @js($model->name), temp_upper_limit: @js(isset($model->settings['temp_upper_limit']) ? (string)(int)round($model->settings['temp_upper_limit']) : ''), temp_lower_limit: @js(isset($model->settings['temp_lower_limit']) ? (string)(int)round($model->settings['temp_lower_limit']) : '') }; modelActionUrl = '{{ route('admin.basic-settings.machine-models.update', $model) }}'; showEditModelModal = true"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931Z" />
                </svg>
                {{ __('Edit') }}
            </button>
            <button type="button"
                @click="confirmDelete('{{ route('admin.basic-settings.machine-models.destroy', $model) }}')"
                class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs hover:bg-rose-500 hover:text-white transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                {{ __('Delete') }}
            </button>
        </div>
    </div>
    @empty
    <div class="col-span-full">
        <x-empty-state :message="__('No data available')" />
    </div>
    @endforelse
</div>

<div class="mt-8 border-t border-slate-100/50 dark:border-slate-800/50 pt-6">
    {{ $models_list->appends(['tab' => 'models'])->links('vendor.pagination.luxury') }}
</div>
