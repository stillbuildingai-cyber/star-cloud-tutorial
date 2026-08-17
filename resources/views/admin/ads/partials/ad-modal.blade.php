<div x-show="isAdModalOpen" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isAdModalOpen = false"></div>

    <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
        <div
            class="relative inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full overflow-visible animate-luxury-in">

            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight"
                        x-text="adFormMode === 'add' ? '{{ __("Add Advertisement") }}'
                        : '{{ __("Edit Advertisement") }}'"></h3>
                    <p class=" text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ __('Manage your ad material details') }}</p>
                </div>
                <button @click="isAdModalOpen = false"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form x-ref="adFormEl" :action="adFormMode === 'add' ? urls.store : urls.update.replace(':id', adForm.id)"
                method="POST" enctype="multipart/form-data" @submit.prevent="submitAdForm" class="space-y-6">
                @csrf
                <template x-if="adFormMode === 'edit'">
                    @method('PUT')
                </template>

                @if(auth()->user()->isSystemAdmin())
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">
                        {{ __('Company Name') }}
                    </label>
                    <x-searchable-select name="company_id" id="ad_company_select" :has-search="true" :selected="null"
                        :options="['' => __('System Default (All Companies)')] + $companies->pluck('name', 'id')->toArray()"
                        @change="adForm.company_id = $event.target.value" />
                </div>
                @endif

                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">
                        {{ __('Material Name') }}
                        <span class="text-rose-500 ml-0.5">*</span>
                    </label>
                    <input type="text" name="name" x-model="adForm.name" required
                        class="w-full h-12 bg-slate-50 dark:bg-slate-800/50 border-none rounded-xl px-4 text-sm font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-cyan-500/20 transition-all placeholder:text-slate-400"
                        placeholder="{{ __('Enter ad material name') }}">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label
                            class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">
                            {{ __('Material Type') }}
                            <span class="text-rose-500 ml-0.5">*</span>
                        </label>
                        <x-searchable-select name="type" id="ad_type_select" :has-search="false" :selected="null"
                            :options="['image' => __('image'), 'video' => __('video')]"
                            @change="adForm.type = $event.target.value; removeMedia();" />
                    </div>

                    <!-- Duration -->
                    <div class="space-y-2">
                        <label
                            class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">
                            {{ __('Duration (Seconds)') }}
                            <span class="text-rose-500 ml-0.5">*</span>
                        </label>
                        <x-searchable-select name="duration" id="ad_duration_select" :has-search="false"
                            :selected="null"
                            :options="['15' => '15 ' . __('Seconds'), '30' => '30 ' . __('Seconds'), '60' => '60 ' . __('Seconds')]"
                            @change="adForm.duration = $event.target.value" />
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label
                            class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">
                            {{ __('Publish Time') }}
                        </label>
                        <x-luxury-datetime-input 
                            name="start_at" 
                            x-model="adForm.start_at"
                            placeholder="{{ __('Immediate') }}"
                        />
                    </div>

                    <div class="space-y-2">
                        <label
                            class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">
                            {{ __('Expired Time') }}
                        </label>
                        <x-luxury-datetime-input 
                            name="end_at" 
                            x-model="adForm.end_at"
                            placeholder="{{ __('Indefinite') }}"
                        />
                    </div>
                </div>

                <!-- File Upload (Luxury UI Pattern) -->
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">
                        <span x-text="adForm.type === 'image' ? '{{ __("Upload Image") }}' : '{{ __("Upload Video") }}'"></span>
                        <template x-if=" adFormMode==='add'">
                            <span class=" text-rose-500 ml-0.5">*</span>
                        </template>
                    </label>

                    <div class="relative group">
                        <template x-if="!mediaPreview">
                            <div @click="$refs.fileInput.click()"
                                class="aspect-video rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col items-center justify-center gap-4 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:border-cyan-500/50 transition-all duration-300 group">
                                <div
                                    class="p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 group-hover:scale-110 group-hover:text-cyan-500 transition-all duration-300">
                                    <svg class="w-8 h-8 text-slate-400 dark:text-slate-500 group-hover:text-cyan-500"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <div class="text-center px-4">
                                    <p
                                        class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-tighter">
                                        {{ __('Click to upload') }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase"
                                        x-text="adForm.type === 'image' ? 'JPG, PNG, WEBP ({{ __('Max 10MB') }})' : 'MP4, MOV ({{ __('Max 50MB') }})'">
                                    </p>
                                </div>
                            </div>
                        </template>

                        <template x-if="mediaPreview">
                            <div
                                class="relative aspect-video rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-xl group/media">
                                <template x-if="adForm.type === 'image'">
                                    <img :src="mediaPreview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="adForm.type === 'video'">
                                    <div class="w-full h-full bg-slate-900 flex items-center justify-center">
                                        <video :src="mediaPreview" class="max-w-full max-h-full object-contain"
                                            muted></video>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <div
                                                class="p-4 rounded-full bg-block/20 backdrop-blur-sm border border-white/10">
                                                <svg class="w-8 h-8 text-white/50" fill="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path d="M8 5.14v14l11-7-11-7z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div
                                    class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover/media:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                    <button type="button" @click="$refs.fileInput.click()"
                                        class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-cyan-500 hover:text-white transition-all duration-300 shadow-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </button>
                                    <button type="button"
                                        @click="openPreview(adFormMode === 'edit' ? (fileName ? {url: mediaPreview, type: adForm.type, name: fileName} : adForm) : {url: mediaPreview, type: adForm.type, name: fileName})"
                                        class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-emerald-500 hover:text-white transition-all duration-300 shadow-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="removeMedia"
                                        class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-rose-500 hover:text-white transition-all duration-300 shadow-lg"
                                        x-show="mediaPreview">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <input type="file" name="file" x-ref="fileInput"
                            :accept="adForm.type === 'image' ? 'image/jpeg,image/png,image/gif,image/webp' : 'video/mp4,video/quicktime,video/x-msvideo'"
                            class="hidden" @change="handleFileChange">
                    </div>
                </div>

                <!-- Status -->
                <div class="flex items-center gap-3 px-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" x-model="adForm.is_active"
                            :value="adForm.is_active ? '1' : '0'" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500">
                        </div>
                    </label>
                    <span class="text-sm font-black text-slate-700 dark:text-slate-200 tracking-tight">{{ __('Active Status') }}</span>
                </div>

                <!-- Action Footer -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="isAdModalOpen = false"
                        class="px-6 py-3 text-sm font-black text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors uppercase tracking-widest">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn-luxury-primary px-10 py-3 disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none transition-all"
                        :disabled="isSubmitting">
                        {{-- 正常狀態：勾勾圖示 --}}
                        <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        {{-- 上傳中：旋轉圖示 --}}
                        <svg x-show="isSubmitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="!isSubmitting">{{ __('Save Material') }}</span>
                        <span x-show="isSubmitting" x-cloak>{{ __('Uploading...') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>