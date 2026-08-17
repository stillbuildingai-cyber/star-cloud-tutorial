@extends('layouts.admin')

@section('content')
@php
    $logoUrl = !empty($company->settings['logo_path']) ? Storage::disk('public')->url($company->settings['logo_path']) : null;
    $bgUrl = !empty($company->settings['login_bg_path']) ? Storage::disk('public')->url($company->settings['login_bg_path']) : null;
    $cardBgUrl = !empty($company->settings['login_card_bg_path']) ? Storage::disk('public')->url($company->settings['login_card_bg_path']) : null;
@endphp

<div class="space-y-6 pb-20" 
     x-data="{ 
         logoPreview: @js($logoUrl), 
         clearLogo: false, 
         bgPreview: @js($bgUrl), 
         clearBg: false, 
         cardBgPreview: @js($cardBgUrl), 
         clearCardBg: false 
     }">

    <!-- Header -->
    <div class="flex items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.permission.companies.index') }}?tab=branding" 
               class="p-2.5 rounded-xl bg-white dark:bg-slate-900 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Brand Styling') }}</h1>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button type="submit" form="branding-form" class="btn-luxury-primary px-8 py-3 h-12">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span>{{ __('Save Changes') }}</span>
            </button>
        </div>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
    <div class="luxury-card p-4 rounded-xl border-rose-500/20 bg-rose-500/5 sm:max-w-xl animate-luxury-in">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-rose-500 shrink-0 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div class="space-y-1">
                <p class="text-sm font-black text-rose-500 uppercase tracking-widest">{{ __('Validation Error') }}</p>
                <ul class="text-xs font-bold text-rose-500/80 list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <form id="branding-form" action="{{ route('admin.permission.companies.branding.update', $company->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        
        <!-- Card 1: Company Profile -->
        <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative overflow-hidden" style="animation-delay: 50ms">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center border border-emerald-500/20 shadow-sm shrink-0">
                    <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white tracking-tight">{{ $company->name }}</h3>
                    <p class="text-xs font-mono font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ __('Code') }}: {{ $company->code }}</p>
                </div>
            </div>
        </div>

        <!-- Card 2: Text Title Configuration -->
        <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative overflow-hidden" style="animation-delay: 100ms">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center border border-indigo-500/20 shadow-sm shrink-0">
                    <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white tracking-tight">{{ __('Text Titles Configuration') }}</h3>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ __('Customize left-side main title, subtitle and right-side welcome description.') }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Left Side Main Title -->
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Custom Left Main Title') }}</label>
                        <input type="text" name="login_main_title" value="{{ old('login_main_title', $company->settings['login_main_title'] ?? '') }}" 
                               class="luxury-input w-full"
                               placeholder="{{ __('e.g. AIoT Intelligent Management Platform') }}">
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider pl-1 mt-1">
                            {{ __('Displayed below Logo on the left (defaults to customer name).') }}
                        </p>
                    </div>

                    <!-- Left Side Subtitle -->
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Custom Left Subtitle') }}</label>
                        <input type="text" name="login_sub_title" value="{{ old('login_sub_title', $company->settings['login_sub_title'] ?? '') }}" 
                               class="luxury-input w-full"
                               placeholder="{{ __('e.g. SMART DEVICE MANAGEMENT HUB') }}">
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider pl-1 mt-1">
                            {{ __('Displayed below main title on the left.') }}
                        </p>
                    </div>
                </div>

                <!-- Right Side Form Welcome Subtitle -->
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Custom Welcome Subtitle') }}</label>
                    <input type="text" name="login_title" value="{{ old('login_title', $company->settings['login_title'] ?? '') }}" 
                           class="luxury-input w-full"
                           placeholder="{{ __('e.g. Please enter your account and password to enter the admin dashboard.') }}">
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider pl-1 mt-1">
                        {{ __('Displayed below "Welcome Back" on the login portal.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Card 3: Branding Images & Backgrounds -->
        <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative overflow-hidden" style="animation-delay: 150ms">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center border border-cyan-500/20 shadow-sm shrink-0">
                    <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white tracking-tight">{{ __('Branding Images & Backgrounds') }}</h3>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-0.5">{{ __('Upload corporate logo, login portal overall background and card left side image.') }}</p>
                </div>
            </div>

            <div class="space-y-8">
                <!-- Company Logo -->
                <div class="space-y-3">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Upload Company Logo') }}</label>
                    <div class="relative group w-40 h-40">
                        <input type="hidden" name="clear_logo" :value="clearLogo ? '1' : '0'">
                        
                        <!-- Empty Logo / Upload Trigger -->
                        <template x-if="!logoPreview">
                            <div @click="$refs.logoInput.click()" 
                                 class="w-full h-full rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col items-center justify-center gap-2.5 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:border-cyan-500/50 transition-all duration-300 group">
                                <div class="p-2.5 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 group-hover:scale-110 group-hover:text-cyan-500 transition-all duration-300">
                                    <svg class="w-6 h-6 text-slate-400 dark:text-slate-500 group-hover:text-cyan-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                </div>
                                <div class="text-center px-2">
                                    <p class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Click to upload') }}</p>
                                    <p class="text-[8px] text-slate-400 dark:text-slate-500 mt-0.5 uppercase">Max 10MB</p>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Active Image Preview & Hover Actions -->
                        <template x-if="logoPreview">
                            <div class="relative w-full h-full rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl group/image flex items-center justify-center p-3">
                                <img :src="logoPreview" class="max-w-full max-h-full object-contain">
                                <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover/image:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                    <button type="button" @click="$refs.logoInput.click()" 
                                            class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-cyan-500 hover:text-white transition-all duration-300 shadow-lg">
                                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="logoPreview = null; clearLogo = true; $refs.logoInput.value = '';" 
                                            class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-rose-500 hover:text-white transition-all duration-300 shadow-lg">
                                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <input type="file" name="logo_file" x-ref="logoInput" class="hidden" accept="image/*" @change="
                            const file = $event.target.files[0];
                            if (file) {
                                clearLogo = false;
                                const reader = new FileReader();
                                reader.onload = (e) => { logoPreview = e.target.result; };
                                reader.readAsDataURL(file);
                            }
                        ">
                    </div>
                </div>

                <!-- Background Images Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4">
                    <!-- Overall Background -->
                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Overall Background Image') }}</label>
                        <div class="relative group aspect-video rounded-[2rem] overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm bg-slate-50/50 dark:bg-slate-900/50">
                            <input type="hidden" name="clear_login_bg" :value="clearBg ? '1' : '0'">
                            
                            <!-- Empty overall BG -->
                            <template x-if="!bgPreview">
                                <div @click="$refs.bgInput.click()" 
                                     class="w-full h-full flex flex-col items-center justify-center gap-3 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:border-cyan-500/50 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[2rem] transition-all duration-300 group">
                                    <div class="p-3 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 group-hover:scale-110 group-hover:text-cyan-500 transition-all duration-300">
                                        <svg class="w-7 h-7 text-slate-400 dark:text-slate-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </div>
                                    <div class="text-center px-4">
                                        <p class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Click to upload') }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">{{ __('1920x1080 (Max 10MB)') }}</p>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- BG Image & Hover mask -->
                            <template x-if="bgPreview">
                                <div class="relative w-full h-full group/image bg-white dark:bg-slate-900">
                                    <img :src="bgPreview" class="w-full h-full object-cover animate-luxury-in">
                                    <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover/image:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                        <button type="button" @click="$refs.bgInput.click()" 
                                                class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-cyan-500 hover:text-white transition-all duration-300 shadow-lg">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="bgPreview = null; clearBg = true; $refs.bgInput.value = '';" 
                                                class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-rose-500 hover:text-white transition-all duration-300 shadow-lg">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <input type="file" name="login_bg_file" x-ref="bgInput" class="hidden" accept="image/*" @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    clearBg = false;
                                    const reader = new FileReader();
                                    reader.onload = (e) => { bgPreview = e.target.result; };
                                    reader.readAsDataURL(file);
                                }
                            ">
                        </div>
                    </div>

                    <!-- Card Left Side Background -->
                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Card Side Background') }}</label>
                        <div class="relative group aspect-video rounded-[2rem] overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm bg-slate-50/50 dark:bg-slate-900/50">
                            <input type="hidden" name="clear_login_card_bg" :value="clearCardBg ? '1' : '0'">
                            
                            <!-- Empty Card BG -->
                            <template x-if="!cardBgPreview">
                                <div @click="$refs.cardBgInput.click()" 
                                     class="w-full h-full flex flex-col items-center justify-center gap-3 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:border-cyan-500/50 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[2rem] transition-all duration-300 group">
                                    <div class="p-3 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 group-hover:scale-110 group-hover:text-cyan-500 transition-all duration-300">
                                        <svg class="w-7 h-7 text-slate-400 dark:text-slate-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </div>
                                    <div class="text-center px-4">
                                        <p class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ __('Click to upload') }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">{{ __('Aspect Ratio 3:4 (Max 10MB)') }}</p>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Card BG Image & Hover mask -->
                            <template x-if="cardBgPreview">
                                <div class="relative w-full h-full group/image bg-white dark:bg-slate-900">
                                    <img :src="cardBgPreview" class="w-full h-full object-cover animate-luxury-in">
                                    <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover/image:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                        <button type="button" @click="$refs.cardBgInput.click()" 
                                                class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-cyan-500 hover:text-white transition-all duration-300 shadow-lg">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="cardBgPreview = null; clearCardBg = true; $refs.cardBgInput.value = '';" 
                                                class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-rose-500 hover:text-white transition-all duration-300 shadow-lg">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <input type="file" name="login_card_bg_file" x-ref="cardBgInput" class="hidden" accept="image/*" @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    clearCardBg = false;
                                    const reader = new FileReader();
                                    reader.onload = (e) => { cardBgPreview = e.target.result; };
                                    reader.readAsDataURL(file);
                                }
                            ">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar (Footer) -->
        <div class="pt-8 flex items-center justify-between border-t border-slate-100 dark:border-slate-800 animate-luxury-in" style="animation-delay: 200ms">
            <div>
                @if(!empty($company->code))
                    <a href="{{ route('tenant.login.preview', $company->code) }}" target="_blank" 
                       class="btn-luxury-secondary flex items-center gap-1.5 px-6 py-3 h-12 text-xs font-black tracking-widest uppercase shadow-lg shadow-slate-100 dark:shadow-none hover:shadow-cyan-500/10 hover:border-cyan-500/30 hover:text-cyan-600 dark:hover:text-cyan-400 transition-all">
                        <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>{{ __('Preview Login Page') }}</span>
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.permission.companies.index') }}?tab=branding" 
                   class="btn-luxury-ghost px-8 h-12 flex items-center justify-center transition-all">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="btn-luxury-primary px-12 h-14 shadow-xl shadow-cyan-500/20 transition-all">
                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <span>{{ __('Save Changes') }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
