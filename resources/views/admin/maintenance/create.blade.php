@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-20">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <!-- Return Button (Ref: Expiry Management) -->
            <a href="{{ route('admin.maintenance.index') }}"
                class="p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{ __('Add Maintenance Record') }}</h1>
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{ __('Fill in the device repair or maintenance details') }}</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="luxury-card bg-rose-500/10 border-rose-500/20 p-6 rounded-2xl animate-luxury-in">
            <div class="flex items-center gap-3 text-rose-500 mb-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-black uppercase tracking-widest text-sm">{{ __('Please check the following errors:') }}</span>
            </div>
            <ul class="list-disc list-inside text-sm font-bold text-rose-600/80 dark:text-rose-400/80 space-y-1 ml-8">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.maintenance.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6"
        x-data="{ 
            selectedFiles: [null, null, null],
            handleFileChange(e, index) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.selectedFiles[index] = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },
            removeFile(index) {
                this.selectedFiles[index] = null;
                const input = document.getElementById('photo-input-' + index);
                if (input) input.value = '';
            },
            validate() {
                const machineId = this.$el.querySelector('[name=machine_id]')?.value;
                const category = this.$el.querySelector('[name=category]:checked');
                const maintenanceAt = this.$el.querySelector('[name=maintenance_at]')?.value;
                const content = this.$el.querySelector('[name=content]')?.value;
                const isConfirmed = this.$el.querySelector('[name=is_confirmed]')?.checked;
                
                if (!machineId || machineId.trim() === '') {
                    window.Alpine.store('toast').show('請選擇機台', 'error');
                    return false;
                }
                if (!category) {
                    window.Alpine.store('toast').show('請選擇維修類別', 'error');
                    return false;
                }
                if (!maintenanceAt) {
                    window.Alpine.store('toast').show('請選擇維修日期', 'error');
                    return false;
                }
                if (!content || content.trim() === '') {
                    window.Alpine.store('toast').show('請填寫維修內容', 'error');
                    return false;
                }
                if (!isConfirmed) {
                    window.Alpine.store('toast').show('請勾選確認已告知客戶並取得簽名', 'error');
                    return false;
                }
                return true;
            }
        }"
        @submit.prevent="if(validate()) $el.submit()">
        @csrf
        
        <div class="luxury-card rounded-3xl p-8 animate-luxury-in space-y-8">
            <!-- Machine Selection -->
            <div class="space-y-4">
                
                @if($machine)
                    <div class="flex items-center gap-4 p-5 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm shadow-slate-200/50 dark:shadow-none">
                        <div class="w-12 h-12 rounded-xl bg-cyan-500 flex items-center justify-center text-white shadow-lg shadow-cyan-500/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-base font-black text-slate-800 dark:text-white">{{ $machine->name }}</div>
                            <div class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ $machine->serial_no }}</div>
                            <input type="hidden" name="machine_id" value="{{ $machine->id }}">
                        </div>
                        <div class="ml-auto">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 border border-sky-100/50 dark:border-sky-900/30 tracking-widest">
                                {{ $machine->company->name ?? __('None') }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="relative z-50">
                        <label class="block text-sm font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-3">{{ __('Select Machine') }}</label>
                        <x-searchable-select name="machine_id" required :placeholder="__('Search serial no or name...')">
                            @foreach($machines as $m)
                                <option value="{{ $m->id }}" data-title="{{ $m->serial_no }} - {{ $m->name }}" {{ old('machine_id') == $m->id ? 'selected' : '' }}>
                                    {{ $m->serial_no }} - {{ $m->name }}
                                </option>
                            @endforeach
                        </x-searchable-select>
                        @error('machine_id')
                            <p class="text-xs font-bold text-rose-500 mt-2 uppercase tracking-widest">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            <!-- Record Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <label class="block text-sm font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">{{ __('Category') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['Repair', 'Installation', 'Removal', 'Maintenance'] as $cat)
                            <label class="relative flex items-center justify-center p-3 rounded-2xl border-2 {{ $errors->has('category') ? 'border-rose-500/50' : 'border-slate-100 dark:border-slate-800' }} bg-slate-50/50 dark:bg-slate-900/50 cursor-pointer hover:border-cyan-500/30 transition-all group">
                                <input type="radio" name="category" value="{{ $cat }}" class="hidden peer" {{ old('category') === $cat ? 'checked' : '' }}>
                                <span class="text-sm font-black text-slate-600 dark:text-slate-400 peer-checked:text-cyan-500 transition-colors uppercase tracking-widest">{{ __($cat) }}</span>
                                <div class="absolute inset-0 rounded-2xl border-2 border-transparent peer-checked:border-cyan-500/50 peer-checked:bg-cyan-500/5 pointer-events-none transition-all"></div>
                            </label>
                        @endforeach
                    </div>
                    @error('category')
                        <p class="text-xs font-bold text-rose-500 mt-1 uppercase tracking-widest">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-4">
                    <label class="block text-sm font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">{{ __('Maintenance Date') }}</label>
                    <input type="datetime-local" name="maintenance_at" value="{{ old('maintenance_at', now()->format('Y-m-d\TH:i')) }}" required class="luxury-input w-full {{ $errors->has('maintenance_at') ? 'border-rose-500/50 ring-4 ring-rose-500/10' : '' }}">
                    @error('maintenance_at')
                        <p class="text-xs font-bold text-rose-500 mt-2 uppercase tracking-widest">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-4">
                <label class="block text-sm font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">{{ __('Maintenance Content') }}</label>
                <textarea name="content" rows="4" class="luxury-input w-full p-6 text-sm {{ $errors->has('content') ? 'border-rose-500/50 ring-4 ring-rose-500/10' : '' }}" placeholder="{{ __('Describe the repair or maintenance status...') }}">{{ old('content') }}</textarea>
                @error('content')
                    <p class="text-xs font-bold text-rose-500 mt-2 uppercase tracking-widest">{{ $message }}</p>
                @enderror
            </div>

            <!-- Photos -->
            <div class="space-y-4">
                <h3 class="text-sm font-black text-indigo-500 uppercase tracking-wider">{{ __('Maintenance Photos') }} ({{ __('Max 3') }})</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <template x-for="i in [0, 1, 2]" :key="i">
                        <div class="relative group aspect-square rounded-3xl overflow-hidden border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 hover:border-cyan-500/50 transition-all flex flex-col items-center justify-center">
                            <input type="file" name="photos[]" :id="'photo-input-' + i" class="hidden" accept="image/*" @change="handleFileChange($event, i)">
                            
                            <template x-if="!selectedFiles[i]">
                                <label :for="'photo-input-' + i" class="flex flex-col items-center gap-3 cursor-pointer w-full h-full justify-center">
                                    <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 shadow-sm border border-slate-100 dark:border-slate-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest" x-text="'{{ __('Slot') }} ' + (i + 1)"></span>
                                </label>
                            </template>

                            <template x-if="selectedFiles[i]">
                                <div class="absolute inset-0 w-full h-full">
                                    <img :src="selectedFiles[i]" class="absolute inset-0 w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                        <label :for="'photo-input-' + i" class="p-2.5 rounded-xl bg-white text-cyan-600 shadow-xl transform hover:scale-110 transition-all cursor-pointer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </label>
                                        <button type="button" @click="removeFile(i)" class="p-2.5 rounded-xl bg-rose-500 text-white shadow-xl transform hover:scale-110 transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Confirmation Checkbox -->
        <div class="px-4">
            <label class="relative flex items-center group cursor-pointer w-fit">
                <input type="checkbox" name="is_confirmed" value="1" class="peer h-6 w-6 rounded-lg border-2 {{ $errors->has('is_confirmed') ? 'border-rose-500/50' : 'border-slate-200 dark:border-slate-800' }} text-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all cursor-pointer bg-white dark:bg-slate-900 appearance-none checked:bg-cyan-500 checked:border-cyan-500">
                <svg class="absolute h-4 w-4 text-white left-1 opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <div class="ml-4">
                    <span class="text-sm font-black {{ $errors->has('is_confirmed') ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200' }} uppercase tracking-widest group-hover:text-cyan-600 transition-colors">
                        {{ __('已確認告知客戶維修內容並取得現場簽名確認') }}
                    </span>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-0.5">{{ __('Confirmed notification to customer and obtained signature') }}</p>
                </div>
            </label>
            @error('is_confirmed')
                <p class="text-xs font-bold text-rose-500 mt-2 uppercase tracking-widest ml-10">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end gap-3 px-4">
            <button type="button" onclick="history.back()" class="btn-luxury-ghost px-10">{{ __('Cancel') }}</button>
            <button type="submit" class="btn-luxury-primary px-16 py-4 text-base">{{ __('Submit Record') }}</button>
        </div>
    </form>
</div>
@endsection
