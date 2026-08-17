@extends('layouts.admin')

@section('content')
<div class="space-y-2 pb-20">


    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.basic-settings.machines.index') }}" 
                class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-cyan-500 hover:border-cyan-500/20 transition-all shadow-sm group">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight items-center flex gap-3 outfit-font">
                    {{ __('Edit Machine') }}
                </h1>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1.5 flex items-center gap-2 uppercase tracking-widest leading-none">
                    <span class="inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 font-mono tracking-tighter">
                        {{ $machine->name }} / {{ $machine->serial_no }}
                    </span>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
             <button type="submit" form="edit-form" class="btn-luxury-primary flex items-center gap-2">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span>{{ __('Save Changes') }}</span>
            </button>
        </div>
    </div>

    <form id="edit-form" x-data="{
        imagePreviews: [
            {{ isset($machine->images[0]) ? "'" . Storage::disk('public')->url($machine->images[0]) . "'" : 'null' }},
            {{ isset($machine->images[1]) ? "'" . Storage::disk('public')->url($machine->images[1]) . "'" : 'null' }},
            {{ isset($machine->images[2]) ? "'" . Storage::disk('public')->url($machine->images[2]) . "'" : 'null' }}
        ],
        removeImages: [false, false, false],
        handleImageUpload(event, index) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreviews[index] = e.target.result;
                this.removeImages[index] = false;
            };
            reader.readAsDataURL(file);
        },
        removeImage(index) {
            this.imagePreviews[index] = null;
            this.removeImages[index] = true;
            if (this.$refs['imageInput_' + index]) this.$refs['imageInput_' + index].value = '';
        },
        submitForm() {
            if (!this.$el.name.value.trim()) {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { message: '{{ __("Enter machine name") }}', type: 'error' } 
                }));
                return;
            }
            if (!this.$el.serial_no.value.trim()) {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { message: '{{ __("Enter serial number") }}', type: 'error' } 
                }));
                return;
            }
            if (!this.$el.machine_model_id.value.trim()) {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { message: '{{ __("Please select a machine model") }}', type: 'error' } 
                }));
                return;
            }
            if (!this.$el.payment_config_id.value.trim()) {
                if (!confirm('{{ __("No payment config is selected (Not Used). Save this machine without any payment config?") }}')) {
                    return;
                }
            }
            this.$el.submit();
        }
    }" @submit.prevent="submitForm" action="{{ route('admin.basic-settings.machines.update', $machine) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        
        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="luxury-card p-6 bg-rose-500/5 border-rose-500/10 mb-8 animate-luxury-in">
                <div class="flex items-center gap-3 mb-4 text-rose-500">
                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <h4 class="font-black text-sm tracking-tight capitalize">{{ __('Some fields need attention') }}</h4>
                </div>
                <ul class="space-y-2">
                    @foreach ($errors->all() as $error)
                        <li class="text-xs font-bold text-rose-500/80 flex items-center gap-2">
                            <span class="w-1 h-1 rounded-full bg-rose-500/40"></span>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <div class="flex flex-col lg:flex-row gap-8 items-start">
            <!-- Main Content Area -->
            <div class="flex-1 space-y-8 order-2 lg:order-1 capitalize">
            <!-- Left: Basic info & Hardware -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="luxury-card rounded-3xl p-7 animate-luxury-in relative z-20">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 18.75 4.5H5.25a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Basic Information') }}</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Machine Name') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $machine->name) }}" class="luxury-input w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Machine Serial No') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="serial_no" value="{{ old('serial_no', $machine->serial_no) }}" class="luxury-input w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Location') }}</label>
                            <input type="text" name="location" value="{{ old('location', $machine->location) }}" class="luxury-input w-full" placeholder="{{ __('e.g., Taipei Station') }}">
                        </div>

                        <div class="relative focus-within:z-[30]">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Machine Model') }} <span class="text-rose-500">*</span></label>
                            <x-searchable-select 
                                name="machine_model_id" 
                                :selected="old('machine_model_id', $machine->machine_model_id)"
                            >
                                @foreach($models as $model)
                                    <option value="{{ $model->id }}" {{ old('machine_model_id', $machine->machine_model_id) == $model->id ? 'selected' : '' }}>{{ $model->name }}</option>
                                @endforeach
                            </x-searchable-select>
                        </div>
                        @if(auth()->user()->isSystemAdmin())
                        <div class="relative focus-within:z-[30]">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Company') }}</label>
                            <x-searchable-select 
                                name="company_id" 
                                :selected="old('company_id', $machine->company_id)"
                                :placeholder="__('No Company')"
                            >
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id', $machine->company_id) == $company->id ? 'selected' : '' }}
                                        data-title="{{ $company->name }}{{ $company->code ? ' (' . $company->code . ')' : '' }}">
                                        {{ $company->name }}{{ $company->code ? ' (' . $company->code . ')' : '' }}
                                    </option>
                                @endforeach
                            </x-searchable-select>
                            @error('company_id') <p class="mt-1 text-xs text-rose-500 font-bold uppercase tracking-wider">{{ $message }}</p> @enderror
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Key No') }}</label>
                            <input type="text" name="key_no" value="{{ old('key_no', $machine->key_no) }}" class="luxury-input w-full" placeholder="{{ __('Enter key number') }}">
                        </div>

                        <div x-data="machineGeocoding()" class="col-span-full grid grid-cols-1 md:grid-cols-4 gap-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Address') }}</label>
                                <div class="flex gap-2">
                                    <input type="text" name="address" x-model="address" class="luxury-input w-full" placeholder="{{ __('Enter full address') }}">
                                    <button type="button" @click="geocode()" :disabled="loading" class="btn-luxury-ghost py-2 px-3 flex items-center gap-2 whitespace-nowrap min-w-[120px] justify-center">
                                        <template x-if="!loading">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>{{ __('Resolve Coordinates') }}</span>
                                            </div>
                                        </template>
                                        <template x-if="loading">
                                            <div class="flex items-center gap-2">
                                                <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <span>{{ __('Searching...') }}</span>
                                            </div>
                                        </template>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Latitude') }}</label>
                                <input type="number" step="any" name="latitude" value="{{ old('latitude', $machine->latitude) }}" class="luxury-input w-full" placeholder="{{ __('e.g., 25.0330') }}">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Longitude') }}</label>
                                <input type="number" step="any" name="longitude" value="{{ old('longitude', $machine->longitude) }}" class="luxury-input w-full" placeholder="{{ __('e.g., 121.5654') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operational Parameters -->
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative z-10" style="animation-delay: 50ms">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 12h7.5"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Operational Parameters') }}</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Card Reader Seconds') }}</label>
                            <input type="number" name="card_reader_seconds" value="{{ $machine->card_reader_seconds }}" class="luxury-input w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Payment Buffer Seconds') }}</label>
                            <input type="number" name="payment_buffer_seconds" value="{{ $machine->payment_buffer_seconds }}" class="luxury-input w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Checkout Time 1') }}</label>
                            <x-luxury-time-input name="card_reader_checkout_time_1" value="{{ $machine->card_reader_checkout_time_1 }}" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Checkout Time 2') }}</label>
                            <x-luxury-time-input name="card_reader_checkout_time_2" value="{{ $machine->card_reader_checkout_time_2 }}" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Heating Start Time') }}</label>
                            <x-luxury-time-input name="heating_start_time" value="{{ $machine->heating_start_time }}" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Heating End Time') }}</label>
                            <x-luxury-time-input name="heating_end_time" value="{{ $machine->heating_end_time }}" />
                        </div>
                    </div>
                </div>

                <!-- hardware & slot types -->
                <div class="luxury-card rounded-3xl p-7 animate-luxury-in" style="animation-delay: 150ms">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5V18a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18V7.5m0-4.5h18M3 7.5h18M3 12h18M3 16.5h18"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Hardware & Slots') }}</h3>
                    </div>

                    <div class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Card Reader No') }}</label>
                                <input type="text" name="card_reader_no" value="{{ $machine->card_reader_no }}" class="luxury-input w-full">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-4">{{ __('Slot Mechanism (default: Conveyor, check for Spring)') }}</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                                @foreach([['1-10', 'is_spring_slot_1_10'], ['11-20', 'is_spring_slot_11_20'], ['21-30', 'is_spring_slot_21_30'], ['31-40', 'is_spring_slot_31_40'], ['41-50', 'is_spring_slot_41_50'], ['51-60', 'is_spring_slot_51_60']] as $slot)
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 cursor-pointer hover:border-amber-500/30 transition-all">
                                    <input type="hidden" name="{{ $slot[1] }}" value="0">
                                    <input type="checkbox" name="{{ $slot[1] }}" value="1" {{ $machine->{$slot[1]} ? 'checked' : '' }} class="w-4 h-4 rounded text-amber-500 focus:ring-amber-500/20">
                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ $slot[0] }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <!-- Right Column: Images & Primary System Settings -->
            <div class="w-full lg:w-96 space-y-8 order-1 lg:order-2 lg:sticky top-24">
                <!-- Machine Images -->
                <div class="luxury-card rounded-[2.5rem] p-8 animate-luxury-in">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Machine Images') }}</h3>
                    </div>

                    <div class="space-y-8">
                        @for($i = 0; $i < 3; $i++)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between px-1 text-xs font-black text-slate-400 uppercase tracking-widest">
                                <span>{{ __('Photo Slot') }} {{ $i + 1 }}</span>
                                <template x-if="imagePreviews[{{ $i }}]">
                                    <span class="text-emerald-500 flex items-center gap-1 lowercase tracking-normal font-bold">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        {{ __('set') }}
                                    </span>
                                </template>
                            </div>
                            
                            <div class="relative group">
                                <input type="hidden" name="remove_image_{{ $i }}" :value="removeImages[{{ $i }}] ? '1' : '0'">
                                
                                <template x-if="!imagePreviews[{{ $i }}]">
                                    <div @click="$refs.imageInput_{{ $i }}.click()" class="aspect-video rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col items-center justify-center gap-3 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:border-cyan-500/50 transition-all duration-300 group/upload">
                                        <div class="p-2.5 rounded-xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 group-hover/upload:scale-110 group-hover/upload:text-cyan-500 transition-all duration-300 text-slate-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-tighter">{{ __('Click to upload') }}</p>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="imagePreviews[{{ $i }}]">
                                    <div class="relative aspect-video rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-lg group/image">
                                        <img :src="imagePreviews[{{ $i }}]" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover/image:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                            <button type="button" @click="$refs.imageInput_{{ $i }}.click()" class="p-2.5 rounded-xl bg-white text-slate-800 hover:bg-cyan-500 hover:text-white transition-all duration-300 shadow-lg">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                            </button>
                                            <button type="button" @click="removeImage({{ $i }})" class="p-2.5 rounded-xl bg-white text-slate-800 hover:bg-rose-500 hover:text-white transition-all duration-300 shadow-lg">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <input type="file" name="image_{{ $i }}" x-ref="imageInput_{{ $i }}" class="hidden" accept="image/*" @change="handleImageUpload($event, {{ $i }})">
                            </div>
                        </div>
                        @endfor

                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5">
                            <p class="text-[10px] font-bold text-slate-400 leading-relaxed uppercase tracking-widest text-center">
                                {{ __('PNG, JPG, WEBP up to 10MB') }}
                            </p>
                        </div>
                    </div>
                </div>
                 <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative z-20" style="animation-delay: 200ms">
                     <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0-1.5a.75.75 0 0 1 .75.75v.75m-.75 0H3m.75 0h.75m-1.5 0a.75.75 0 0 1-.75-.75V3M3 10.5v1.5m10.5-3v-4.5m0 4.5h5.25m-5.25 0V10.5m0-1.5a.75.75 0 0 1 .75-.75h.75m-1.5 0H12m.75 0h.75m-1.5 0a.75.75 0 0 1-.75-.75V3.75M12 4.5H3.75a2.25 2.25 0 0 0-2.25 2.25v10.5a2.25 2.25 0 0 0 2.25 2.25h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 18.75 4.5Z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Payment & Invoice') }}</h3>
                    </div>

                    <div class="space-y-6">
                        <div>
                             <label class="block text-xs font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">{{ __('Payment Config') }}</label>
                             <x-searchable-select 
                                 name="payment_config_id" 
                                 :selected="old('payment_config_id', $machine->payment_config_id)"
                                 :placeholder="__('Not Used')"
                                 :hasSearch="false"
                             >
                                @foreach($paymentConfigs as $config)
                                    <option value="{{ $config->id }}" {{ $machine->payment_config_id == $config->id ? 'selected' : '' }}>{{ $config->name }}</option>
                                @endforeach
                            </x-searchable-select>
                        </div>
                    </div>
                 </div>

            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function machineGeocoding() {
    return {
        address: @js($machine->address ?? ''),
        loading: false,
        async geocode() {
            if (!this.address) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: @js(__('Please enter address first')), type: 'error' } }));
                return;
            }
            this.loading = true;
            try {
                const response = await fetch(@js(route('admin.basic-settings.machines.geocode')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ address: this.address })
                });
                const data = await response.json();
                if (data.success) {
                    document.querySelector('input[name=latitude]').value = parseFloat(data.lat).toFixed(6);
                    document.querySelector('input[name=longitude]').value = parseFloat(data.lon).toFixed(6);
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: @js(__('Location found!')), type: 'success' } }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: @js(__('Location not found')), type: 'error' } }));
                }
            } catch (error) {
                console.error('Geocoding error:', error);
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: @js(__('Error searching location')), type: 'error' } }));
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection

