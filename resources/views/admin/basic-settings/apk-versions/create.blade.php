@extends('layouts.admin')

@section('content')
<div class="space-y-4 pb-20">
    <!-- Header -->
    <x-page-header
        :title="__('Upload New APK')"
        :subtitle="__('Register a new firmware version for OTA deployment')"
    >
        <x-slot name="back">
            <a href="{{ route('admin.basic-settings.apk-versions.index') }}" class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-slate-500 hover:text-cyan-500 transition-all shadow-sm flex items-center justify-center">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
        </x-slot>
        <button type="submit" form="create-apk-form" class="btn-luxury-primary px-8 flex items-center gap-2">
            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            <span>{{ __('Save Version') }}</span>
        </button>
    </x-page-header>

    <form id="create-apk-form"
        x-data="apkVersionForm()"
        @submit.prevent="submitForm"
        action="{{ route('admin.basic-settings.apk-versions.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-6"
        novalidate>
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            {{-- 左欄：版本基本資訊 --}}
            <div class="lg:col-span-7 space-y-6">
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in group/card">
                    <div class="flex items-center gap-5 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500/10 to-teal-500/10 flex items-center justify-center text-cyan-500 border border-cyan-500/20 shadow-lg shadow-cyan-500/5 group-hover/card:scale-110 transition-transform duration-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight font-display uppercase">{{ __('Version Information') }}</h3>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ __('Firmware identification details') }}</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- 版本名稱 --}}
                            <div>
                                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Version Name') }} <span class="text-rose-500">*</span></label>
                                <input type="text" name="version_name" x-model="versionName" required class="luxury-input w-full" placeholder="{{ __('e.g., 10_08_6_R') }}">
                                <p class="mt-1.5 text-[10px] font-bold text-slate-400/70 dark:text-slate-500/70 tracking-wide">{{ __('Format: Major_Minor_Patch_R') }}</p>
                            </div>
                            {{-- 版本代碼 --}}
                            <div>
                                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Version Code') }} <span class="text-rose-500">*</span></label>
                                <div class="flex items-center h-12 rounded-2xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all">
                                    <button type="button" @click="adjustVersionCode(-1)"
                                        class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                    </button>
                                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                    <input type="text" inputmode="numeric" name="version_code" x-model="versionCode" required
                                        class="flex-1 min-w-0 h-full bg-transparent border-none text-center text-lg font-black text-slate-800 dark:text-white focus:ring-0 px-3"
                                        placeholder="{{ __('e.g., 100806') }}">
                                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                    <button type="button" @click="adjustVersionCode(1)"
                                        class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/></svg>
                                    </button>
                                </div>
                                <p class="mt-1.5 text-[10px] font-bold text-slate-400/70 dark:text-slate-500/70 tracking-wide">{{ __('Must be strictly greater than previous version') }}</p>
                            </div>
                        </div>

                        {{-- 機台風味 --}}
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Machine Flavor') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="flavor" x-model="flavor" required
                                list="apk-flavor-options"
                                class="luxury-input w-full"
                                placeholder="{{ __('e.g., S_12_XY_U_General') }}">
                            {{-- 常用建議值（非強制，可自由輸入任意 flavor） --}}
                            <datalist id="apk-flavor-options">
                                <option value="S_12_XY_U_Chengwai">S_12_XY_U_Chengwai（晟崴客製 / S12 / XY主板）</option>
                                <option value="S_12_XY_U_General">S_12_XY_U_General（通用主幹 / S12 / XY主板）</option>
                                <option value="S_9_XY_U_Cmuh">S_9_XY_U_Cmuh（中國醫客製 / S9 / XY主板）</option>
                            </datalist>
                            <p class="mt-1.5 text-[10px] font-bold text-slate-400/70 dark:text-slate-500/70 tracking-wide">{{ __('Target hardware flavor for this APK') }}</p>
                        </div>

                        {{-- 更新說明 --}}
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Release Notes') }}</label>
                            <textarea name="release_notes" rows="4" class="luxury-input w-full resize-none" placeholder="{{ __('Describe the changes in this version...') }}">{{ old('release_notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 右欄：檔案上傳與更新模式 --}}
            <div class="lg:col-span-5 space-y-6">
                {{-- 上傳模式卡片 --}}
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in group/card" style="animation-delay: 50ms">
                    <div class="flex items-center gap-5 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500/10 to-indigo-500/10 flex items-center justify-center text-violet-500 border border-violet-500/20 shadow-lg shadow-violet-500/5 group-hover/card:scale-110 transition-transform duration-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight font-display uppercase">{{ __('APK Source') }}</h3>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ __('Upload file or provide URL') }}</p>
                        </div>
                    </div>

                    {{-- 上傳模式切換 --}}
                    <div class="flex rounded-xl bg-slate-100 dark:bg-slate-800/80 p-1 mb-6">
                        <button type="button"
                                @click="uploadMode = 'file'"
                                :class="uploadMode === 'file' ? 'bg-white dark:bg-slate-700 text-cyan-600 dark:text-cyan-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="flex-1 py-2.5 text-xs font-black uppercase tracking-widest rounded-lg transition-all duration-300 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            {{ __('File Upload') }}
                        </button>
                        <button type="button"
                                @click="uploadMode = 'url'"
                                :class="uploadMode === 'url' ? 'bg-white dark:bg-slate-700 text-cyan-600 dark:text-cyan-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="flex-1 py-2.5 text-xs font-black uppercase tracking-widest rounded-lg transition-all duration-300 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                            {{ __('External URL') }}
                        </button>
                    </div>

                    {{-- 檔案上傳區域 --}}
                    <div x-show="uploadMode === 'file'" x-transition>
                        <div class="relative border-2 border-dashed rounded-2xl p-8 text-center transition-all duration-300 cursor-pointer"
                             :class="isDragging ? 'border-cyan-500 bg-cyan-500/5 dark:bg-cyan-500/10 scale-[1.02]' : 'border-slate-200 dark:border-slate-700 hover:border-cyan-400 dark:hover:border-cyan-600 hover:bg-slate-50/50 dark:hover:bg-slate-800/50'"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="handleFileDrop($event)"
                             @click="$refs.apkFileInput.click()">

                            <input type="file"
                                   name="apk_file"
                                   x-ref="apkFileInput"
                                   accept=".apk"
                                   class="hidden"
                                   @change="handleFileSelect($event)">

                            <div class="flex flex-col items-center gap-4">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center transition-all duration-300"
                                     :class="isDragging ? 'bg-cyan-500 text-white scale-110' : fileName ? 'bg-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                                    <svg x-show="!fileName" class="w-8 h-8 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0017.25 4.5H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z"/></svg>
                                    <svg x-show="fileName" class="w-8 h-8 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>

                                <div>
                                    <p x-show="!fileName" class="text-sm font-extrabold text-slate-600 dark:text-slate-300">
                                        {{ __('Drag & drop your APK here') }}
                                    </p>
                                    <p x-show="fileName" class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400" x-text="fileName"></p>
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-2">
                                        {{ __('Maximum file size: 100MB') }} · .apk
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 外連 URL 輸入區域 --}}
                    <div x-show="uploadMode === 'url'" x-transition>
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Download URL') }}</label>
                        <input type="url" name="download_url" value="{{ old('download_url') }}" class="luxury-input w-full" placeholder="https://example.com/release/app-v10.8.6.apk">
                        <p class="mt-1.5 text-[10px] font-bold text-slate-400/70 dark:text-slate-500/70 tracking-wide">{{ __('Provide a publicly accessible direct download link') }}</p>
                    </div>
                </div>

                {{-- 注意事項卡片 --}}
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in group/card bg-amber-500/5 dark:bg-amber-500/5 border border-amber-500/15" style="animation-delay: 100ms">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500 border border-amber-500/20 shadow-lg shadow-amber-500/5 shrink-0">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        </div>
                        <div class="text-xs font-bold text-amber-800 dark:text-amber-300 tracking-wide leading-relaxed">
                            <p class="text-sm font-black uppercase tracking-widest mb-1.5 flex items-center gap-1 font-display">{{ __('Important Notice') }}</p>
                            <p class="font-bold opacity-90 leading-relaxed">{{ __('Version Code must be strictly greater than the previous release, otherwise the OTA silent installation on the device will fail with INSTALL_FAILED_ALREADY_EXISTS.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function apkVersionForm() {
        return {
            uploadMode: 'file',
            fileName: '',
            isDragging: false,
            versionName: '{{ old('version_name', '') }}',
            versionCode: '{{ old('version_code', '') }}',
            flavor: '{{ old('flavor', '') }}',

            adjustVersionCode(delta) {
                const current = parseInt(this.versionCode) || 0;
                const next = Math.max(1, current + delta);
                this.versionCode = next.toString();
            },

            submitForm() {
                const form = this.$el;
                const versionName = this.versionName.trim();
                const versionCode = this.versionCode.trim();

                if (!versionName) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __('Please enter version name') }}', type: 'error' }
                    }));
                    return;
                }
                if (!versionCode || isNaN(versionCode) || parseInt(versionCode) <= 0) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __('Version code must be a positive integer') }}', type: 'error' }
                    }));
                    return;
                }
                if (!this.flavor.trim()) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __('Please enter machine flavor') }}', type: 'error' }
                    }));
                    return;
                }

                if (this.uploadMode === 'file') {
                    const fileInput = form.querySelector('[name=apk_file]');
                    if (!fileInput.files.length) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: '{{ __('Please select an APK file to upload') }}', type: 'error' }
                        }));
                        return;
                    }
                } else {
                    const urlInput = form.querySelector('[name=download_url]').value.trim();
                    if (!urlInput) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: '{{ __('Please provide a download URL') }}', type: 'error' }
                        }));
                        return;
                    }
                }

                form.submit();
            },

            parseApkName(name) {
                const baseName = name.replace(/\.apk$/i, '');
                
                // Match "app-{flavor}-{versionName}" pattern
                const pattern = /^app-([^-]+)-(\d+_\d+_\d+_[a-zA-Z0-9]+)/i;
                const match = baseName.match(pattern);
                
                if (match) {
                    const flavor = match[1];
                    const versionName = match[2];
                    
                    // 1. Fill Version Name (Alpine.js model binding)
                    this.versionName = versionName;
                    
                    // 2. Parse & Calculate Version Code (e.g. 10_01_6_R -> 10 * 10000 + 1 * 100 + 6 = 100106)
                    const parts = versionName.split('_');
                    if (parts.length >= 3) {
                        const major = parseInt(parts[0]) || 0;
                        const minor = parseInt(parts[1]) || 0;
                        const patch = parseInt(parts[2]) || 0;
                        this.versionCode = (major * 10000 + minor * 100 + patch).toString();
                    }
                    
                    // 3. Fill Flavor (free-text input — 任意 flavor 皆可填入)
                    this.flavor = flavor;
                    
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __('已自動解析 APK 檔名並填入欄位！') }}', type: 'success' }
                    }));
                }
            },

            handleFileDrop(event) {
                this.isDragging = false;
                const files = event.dataTransfer.files;
                if (files.length > 0) {
                    const fileInput = this.$refs.apkFileInput;
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    fileInput.files = dt.files;
                    this.fileName = files[0].name;
                    this.parseApkName(this.fileName);
                }
            },

            handleFileSelect(event) {
                if (event.target.files.length > 0) {
                    this.fileName = event.target.files[0].name;
                    this.parseApkName(this.fileName);
                }
            }
        };
    }
</script>
@endsection
