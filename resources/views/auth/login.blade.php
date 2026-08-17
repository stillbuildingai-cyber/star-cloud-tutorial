<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        /* 懸浮卡片高奢進入動畫 */
        @keyframes luxury-in {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .animate-luxury-in {
            animation: luxury-in 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[#070B14]">
    
    <!-- 預覽模式高階提示橫條 -->
    @if(isset($isPreview) && $isPreview)
        <div class="fixed top-0 inset-x-0 bg-cyan-600/90 backdrop-blur-md text-white text-center py-2.5 px-4 text-xs font-black tracking-widest uppercase z-50 shadow-md flex items-center justify-center gap-2 select-none">
            <svg class="w-4 h-4 animate-pulse text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span>{{ __('Preview Mode - Interactive Vending Portal Mockup') }}</span>
        </div>
    @endif
    
    <!-- 全局科技星雲底圖 Canvas -->
    <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 md:p-10 relative overflow-hidden bg-[#070B14] {{ (isset($isPreview) && $isPreview) ? 'pt-16 sm:pt-20' : '' }}">
        
        <!-- 精美淡淡的科技不規則線條背景圖 (低透明度，優雅不花俏) / 自訂品牌大背景圖 -->
        @if(isset($company) && !empty($company->settings['login_bg_path']))
            <div class="absolute inset-0 bg-cover bg-center opacity-85 pointer-events-none" style="background-image: url('{{ Storage::disk('public')->url($company->settings['login_bg_path']) }}');"></div>
        @else
            <div class="absolute inset-0 bg-cover bg-center opacity-[0.18] pointer-events-none mix-blend-lighten" style="background-image: url('{{ asset('tech_lines_bg.png') }}');"></div>
        @endif
        
        <!-- 霓虹光暈 (Radial Glow) -->
        <div class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] rounded-full bg-indigo-600/10 blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[600px] h-[600px] rounded-full bg-blue-600/10 blur-[130px] pointer-events-none"></div>
        <div class="absolute top-[30%] left-[20%] w-[350px] h-[350px] rounded-full bg-cyan-600/5 blur-[100px] pointer-events-none"></div>
        
        <!-- 科技感精密網格線 (Grid Overlay) -->
        @if(!isset($company) || empty($company->settings['login_bg_path']))
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-[0.08] pointer-events-none"></div>
        @endif

        <!-- 懸浮珍珠白高奢卡片 (Unified Card - Option B: Solid Pristine White) -->
        <div class="w-full max-w-4xl bg-white rounded-[2.5rem] shadow-[0_30px_75px_-15px_rgba(0,0,0,0.65)] flex flex-col md:flex-row overflow-hidden relative z-10 animate-luxury-in">
            
            <!-- 左側品牌區 (Branding Area - Solid Premium Slate) -->
            <div class="w-full md:w-1/2 bg-[#F8FAFC] border-b md:border-b-0 md:border-r border-slate-100 flex flex-col justify-center items-center p-8 lg:p-12 relative overflow-hidden shrink-0 select-none min-h-[320px] md:min-h-0">
                
                <!-- 精美淡淡的智慧販賣機背景圖 / 自訂卡片左側背景圖 -->
                @if(isset($company) && !empty($company->settings['login_card_bg_path']))
                    <div class="absolute inset-0 bg-cover bg-center opacity-90 pointer-events-none" style="background-image: url('{{ Storage::disk('public')->url($company->settings['login_card_bg_path']) }}');"></div>
                @else
                    <div class="absolute inset-0 bg-cover bg-center opacity-[0.20] pointer-events-none mix-blend-multiply" style="background-image: url('{{ asset('tech_vending_bg.png') }}');"></div>
                @endif



                <div class="relative z-10 flex flex-col items-center justify-center space-y-12 py-6 md:py-12">
                    <!-- Logo -->
                    <img src="{{ isset($company) && $company->logo_url ? $company->logo_url : asset('starcloudlogo-Photoroom.png') }}" 
                        alt="{{ isset($company) ? $company->name : config('app.name') }} Logo" 
                        class="w-full max-w-[480px] md:max-w-[560px] h-auto max-h-[280px] object-contain drop-shadow-sm">
                    
                    <!-- 平台名稱 -->
                    <div class="text-center px-4">
                        <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight font-display break-words">
                            {{ (isset($company) && !empty($company->settings['login_main_title'])) ? $company->settings['login_main_title'] : (isset($company) ? $company->name : 'AIoT 自動化設備智慧營運平台') }}
                        </h2>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2 font-mono break-all">
                            {{ (isset($company) && !empty($company->settings['login_sub_title'])) ? $company->settings['login_sub_title'] : (isset($company) ? 'Smart Vending Portal' : 'Smart Device Management Hub') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 右側登入表單區 (Form Area - Solid Pure White) -->
            <div class="flex-1 bg-white flex flex-col justify-center p-8 sm:p-10 lg:p-12 relative overflow-hidden">
                


                <div class="max-w-sm w-full mx-auto relative z-10">
                    
                    <!-- 歡迎文案 -->
                    <div class="text-left mb-8">
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-800 tracking-tight font-display">
                            歡迎回來
                        </h1>
                        <p class="mt-2 text-sm font-semibold text-slate-500">
                            {{ isset($company) ? ($company->settings['login_title'] ?? '請輸入您的帳號與密碼，進入管理後台') : '請輸入您的帳號與密碼，進入管理後台' }}
                        </p>
                    </div>

                    <!-- Session 訊息狀態 -->
                    @if (session('status'))
                        <div class="mb-5 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-sm font-bold animate-luxury-in">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST"
                        action="{{ (isset($isPreview) && $isPreview) ? '#' : (isset($company) ? route('tenant.login.post', $company->code) : route('login')) }}"
                        x-data="{ isSubmitting: false, isPreview: {{ (isset($isPreview) && $isPreview) ? 'true' : 'false' }} }"
                        @submit="if (isPreview) { alert('{{ __('Preview Mode: Login functionality is disabled.') }}'); $event.preventDefault(); return; } if (isSubmitting) { $event.preventDefault(); return; } isSubmitting = true;"
                        class="space-y-6">
                        @csrf

                        <!-- 帳號輸入框 -->
                        <div class="space-y-2">
                            <label for="username" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">
                                帳號
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </span>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" 
                                    class="py-3 ps-11 pe-4 block w-full border border-slate-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none transition-all" 
                                    placeholder="請輸入您的帳號" required autofocus autocomplete="username">
                            </div>
                            @if ($errors->get('username'))
                                <p class="text-xs text-rose-500 font-bold mt-1.5" id="username-error">
                                    {{ $errors->first('username') }}
                                </p>
                            @endif
                        </div>

                        <!-- 密碼輸入框 -->
                        <div class="space-y-2" x-data="{ showPassword: false }">
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    密碼
                                </label>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </span>
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" 
                                    class="py-3 ps-11 pe-12 block w-full border border-slate-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none transition-all" 
                                    placeholder="請輸入您的密碼" required autocomplete="current-password">
                                <button type="button" @click="showPassword = !showPassword" 
                                    class="absolute inset-y-0 end-0 flex items-center z-20 px-4 cursor-pointer text-slate-400 hover:text-blue-600 transition-colors">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @if ($errors->get('password'))
                                <p class="text-xs text-rose-500 font-bold mt-1.5" id="password-error">
                                    {{ $errors->first('password') }}
                                </p>
                            @endif
                        </div>

                        <!-- 記住我 -->
                        <div class="flex items-center">
                            <input id="remember-me" name="remember" type="checkbox" 
                                class="shrink-0 mt-0.5 border-slate-200 rounded text-blue-600 focus:ring-blue-500/20 cursor-pointer">
                            <label for="remember-me" class="ms-3 text-sm font-semibold text-slate-600 cursor-pointer">
                                記住我
                            </label>
                        </div>

                        <!-- 提交按鈕 -->
                        <button type="submit"
                            :disabled="isSubmitting"
                            class="w-full py-3.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-extrabold rounded-xl border border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg shadow-blue-500/25 active:scale-[0.98] focus:outline-none transition-all cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed disabled:active:scale-100">
                            <svg x-show="isSubmitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span x-show="!isSubmitting">登入</span>
                            <span x-show="isSubmitting" x-cloak>登入中</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
