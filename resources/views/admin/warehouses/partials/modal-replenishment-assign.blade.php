{{-- 指派人員 Modal --}}
<div x-show="showAssignModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak>
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showAssignModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div x-show="showAssignModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" class="inline-block px-4 pt-5 pb-4 overflow-visible text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-8 border border-slate-100 dark:border-white/10">
            <div class="mb-8">
                <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight uppercase">{{ __('Assign Personnel') }}</h3>
                <p class="text-sm font-bold text-slate-500 mt-2">{{ __('Select a team member to handle this replenishment order') }}</p>
            </div>
            <div class="space-y-3 mb-8">
                <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1">{{ __('Select Personnel') }}</label>
                <x-searchable-select 
                    id="assign-personnel-select"
                    name="assigned_to" 
                    :placeholder="__('Select Personnel')"
                    @change="assignUserId = $event.target.value"
                >
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" data-title="{{ $u->name }}">{{ $u->name }}</option>
                    @endforeach
                </x-searchable-select>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" @click="showAssignModal = false" class="btn-luxury-ghost px-6">{{ __('Cancel') }}</button>
                <button type="button" @click="executeAssign()" :disabled="loading || !assignUserId" class="btn-luxury-primary px-8">{{ __('Confirm') }}</button>
            </div>
        </div>
    </div>
</div>
