<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
    x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StillBuilding Life') }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    <script>
        // Dark Mode Initialization (before Alpine loads)
        if (localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('darkMode', 'true');
        } else {
            document.documentElement.classList.remove('dark');
        }

        window.applyAdminSidebarLayout = (collapsed) => {
            document.documentElement.style.setProperty('--admin-sidebar-offset', collapsed ? '6rem' : '256px');
            document.documentElement.style.setProperty('--admin-sidebar-width', collapsed ? '60px' : '14rem');
        };
        window.applyAdminSidebarLayout(localStorage.getItem('sidebarCollapsed') === 'true');
    </script>
    <style>
        html.admin-page-transitioning .admin-content-shell {
            opacity: 0.45;
            filter: blur(1px);
            pointer-events: none;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-gray-50 dark:bg-[#0f172a] antialiased font-sans h-full overflow-x-clip selection:bg-indigo-100 dark:selection:bg-indigo-900/40"
    x-data="{ 
        sidebarOpen: false, 
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        userDropdownOpen: false 
    }" x-init="
        $watch('sidebarCollapsed', value => {
            localStorage.setItem('sidebarCollapsed', value);
            window.applyAdminSidebarLayout?.(value);
        })
    ">
    <!-- Option A: Loading Screen -->
    <x-loading-screen />

    <!-- Option B: Top Progress Bar -->
    <div id="top-loading-bar" class="top-loading-bar"></div>

    <script>
        (() => {
            const topLoadingBar = () => document.getElementById('top-loading-bar');
            const showNavigationLoading = () => {
                topLoadingBar()?.classList.add('loading');
                document.documentElement.classList.add('admin-page-transitioning');
            };
            const hideNavigationLoading = () => {
                topLoadingBar()?.classList.remove('loading');
                document.documentElement.classList.remove('admin-page-transitioning');
            };
            const shouldShowPageLoading = (event, link) => {
                if (
                    event.defaultPrevented ||
                    event.button !== 0 ||
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.altKey
                ) {
                    return false;
                }

                if (
                    !link.href ||
                    link.hasAttribute('download') ||
                    link.target && link.target !== '_self'
                ) {
                    return false;
                }

                const url = new URL(link.href, window.location.href);
                const isHashOnlyNavigation = url.pathname === window.location.pathname &&
                    url.search === window.location.search &&
                    url.hash;

                return url.origin === window.location.origin &&
                    url.href !== window.location.href &&
                    !isHashOnlyNavigation &&
                    !url.href.startsWith('javascript:');
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a[href]');
                if (!link || !shouldShowPageLoading(event, link)) {
                    return;
                }

                showNavigationLoading();
            });

            window.addEventListener('beforeunload', showNavigationLoading);
            window.addEventListener('pageshow', hideNavigationLoading);
        })();
    </script>

    <!-- Sidebar Overlay (Mobile) -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
        class="fixed inset-0 z-[55] bg-gray-900/50 lg:hidden" x-cloak></div>

    <!-- ========== HEADER ========== -->
    <header
        class="sticky top-0 inset-x-0 flex flex-wrap sm:justify-start sm:flex-nowrap z-[48] w-full bg-white/80 backdrop-blur-md border-b border-slate-200/50 text-sm py-2.5 sm:py-4 lg:pl-[var(--admin-sidebar-offset)] dark:bg-[#0f172a]/80 dark:border-slate-800/50 shadow-sm transition-all duration-300">
        <nav class="flex basis-full items-center w-full mx-auto pl-4 lg:pl-0 pr-4 sm:pr-6 md:pr-8" aria-label="Global">
            <div class="mr-2 lg:mr-0 lg:hidden text-center shrink-0">
                <a class="flex items-center gap-x-2 flex-none text-xl font-bold dark:text-white font-display tracking-tight"
                    href="{{ route('admin.dashboard') }}" aria-label="Brand">
                    <img src="{{ auth()->user()->company && auth()->user()->company->hasCustomBrandingEnabled() && auth()->user()->company->logo_url ? auth()->user()->company->logo_url : asset('starcloud_icon.png') }}" 
                        alt="{{ auth()->user()->company ? auth()->user()->company->name : config('app.name') }} Logo"
                        class="w-7 h-7 max-h-7 max-w-[28px] object-contain shrink-0">
                    <span class="hidden min-[400px]:inline">StillBuilding <span class="text-cyan-500">Life</span></span>
                </a>
            </div>

            <!-- Auto Refresh -->
            <div class="relative inline-flex shrink-0 ml-2 sm:ml-10 lg:ml-0" x-data="{
                refreshOpen: false,
                interval: localStorage.getItem('autoRefreshInterval') || 'Off',
                timeLeft: 0,
                timer: null,
                circumference: 2 * Math.PI * 9,
                init() {
                    this.resetTimer();
                    this.$watch('interval', (val) => {
                        localStorage.setItem('autoRefreshInterval', val);
                        this.resetTimer();
                    });
                },
                resetTimer() {
                    if (this.timer) clearInterval(this.timer);
                    if (this.interval === 'Off') {
                        this.timeLeft = 0;
                        return;
                    }
                    const totalSeconds = parseInt(this.interval);
                    this.timeLeft = totalSeconds;
                    this.timer = setInterval(() => {
                        if (this.timeLeft > 0) {
                            this.timeLeft--;
                        } else {
                            this.manualRefresh();
                        }
                    }, 1000);
                },
                manualRefresh() {
                    const loader = document.getElementById('top-loading-bar');
                    if (loader) loader.classList.add('loading');
                    window.location.reload();
                }
            }">
                <div
                    class="inline-flex items-center bg-white dark:bg-gray-800 rounded-full border border-slate-200/50 dark:border-slate-700/50 p-0.5 shadow-sm">
                    <!-- Manual Refresh Button with Progress -->
                    <button type="button" @click="manualRefresh()"
                        :title="interval === 'Off' ? '{{ __('Refresh Now') }}' : '{{ __('Refreshing in :seconds s') }}'.replace(':seconds', timeLeft)"
                        class="relative flex items-center justify-center size-9 rounded-full text-gray-500 hover:text-cyan-500 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all focus:outline-none">
                        <!-- Progress Ring -->
                        <svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 24 24">
                            <circle class="text-gray-100 dark:text-gray-700/50" stroke-width="2" stroke="currentColor"
                                fill="transparent" r="9" cx="12" cy="12" />
                            <circle class="text-cyan-500 transition-all duration-1000 ease-linear" stroke-width="2"
                                :stroke-dasharray="circumference"
                                :stroke-dashoffset="circumference - (interval === 'Off' ? 0 : ((parseInt(interval) - timeLeft) / parseInt(interval)) * circumference)"
                                stroke-linecap="round" stroke="currentColor" fill="transparent" r="9" cx="12" cy="12"
                                x-show="interval !== 'Off'" />
                        </svg>
                        <!-- Refresh Icon -->
                        <svg class="size-4 relative z-10" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                            <path d="M21 3v5h-5" />
                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                            <path d="M3 21v-5h5" />
                        </svg>
                    </button>

                    <!-- Interval Selector -->
                    <button type="button" @click="refreshOpen = !refreshOpen" @click.away="refreshOpen = false"
                        class="flex items-center gap-x-1.5 h-8 px-2 rounded-full text-[11px] font-bold text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 transition-all border-l border-slate-100 dark:border-slate-700/50 ml-0.5 focus:outline-none md:min-w-[3.5rem] justify-center">
                        <span class="hidden md:inline whitespace-nowrap"
                            x-text="interval === 'Off' ? '{{ __('Off') }}' : interval"></span>
                        <svg class="size-3 text-gray-400 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                </div>

                <!-- Dropdown -->
                <div x-show="refreshOpen" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute left-0 top-full mt-2 min-w-[9rem] bg-white shadow-xl rounded-2xl p-2 dark:bg-gray-800 dark:border dark:border-gray-700 z-50 border border-slate-100"
                    x-cloak>
                    <div class="px-3 py-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                        {{ __('Auto Refresh') }}
                    </div>
                    <template x-for="opt in ['Off', '10s', '30s', '60s']">
                        <button @click="interval = opt; refreshOpen = false"
                            class="flex items-center justify-between w-full py-2 px-3 rounded-xl text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors"
                            :class="interval === opt ? 'bg-gray-50 dark:bg-gray-900/50 text-cyan-600 dark:text-cyan-400' : ''">
                            <span class="font-medium" x-text="opt === 'Off' ? '{{ __('Off') }}' : opt"></span>
                            <svg x-show="interval === opt" class="size-4 text-cyan-500"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>

            <div class="w-full flex items-center justify-end ml-auto sm:gap-x-3 sm:order-3">

                <div class="flex flex-row items-center justify-end gap-x-1 sm:gap-x-3">
                    <!-- Language Switcher -->
                    <div class="relative inline-flex" x-data="{ langOpen: false }">
                        <button type="button" @click="langOpen = !langOpen" @click.away="langOpen = false"
                            class="inline-flex flex-shrink-0 justify-center items-center gap-2 h-[2.375rem] px-3 rounded-full font-medium bg-white text-gray-700 align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-white transition-all text-xs dark:bg-gray-800 dark:hover:bg-slate-800 dark:text-gray-400 dark:hover:text-white dark:focus:ring-gray-700 dark:focus:ring-offset-gray-800">
                            <span class="flex items-center gap-x-2">
                                @if(app()->getLocale() == 'zh_TW')
                                <span class="text-base">🇹🇼</span>
                                <span class="hidden md:inline font-bold">繁體中文</span>
                                @elseif(app()->getLocale() == 'ja')
                                <span class="text-base">🇯🇵</span>
                                <span class="hidden md:inline font-bold">日本語</span>
                                @else
                                <span class="text-base">🇬🇧</span>
                                <span class="hidden md:inline font-bold">English</span>
                                @endif
                            </span>
                            <svg class="size-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div x-show="langOpen" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 top-full mt-2 min-w-[10rem] bg-white shadow-xl rounded-2xl p-2 dark:bg-gray-800 dark:border dark:border-gray-700 z-50 border border-slate-100"
                            x-cloak>
                            <a href="{{ route('lang.switch', 'zh_TW') }}"
                                class="flex items-center gap-x-3 py-2.5 px-3 rounded-xl text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors {{ app()->getLocale() == 'zh_TW' ? 'bg-gray-50 dark:bg-gray-900/50' : '' }}">
                                <span class="text-lg">🇹🇼</span>
                                <span class="font-bold">繁體中文</span>
                                @if(app()->getLocale() == 'zh_TW')
                                <svg class="ml-auto size-4 text-cyan-500" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                @endif
                            </a>
                            <a href="{{ route('lang.switch', 'en') }}"
                                class="flex items-center gap-x-3 py-2.5 px-3 rounded-xl text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors {{ app()->getLocale() == 'en' ? 'bg-gray-50 dark:bg-gray-900/50' : '' }}">
                                <span class="text-lg">🇬🇧</span>
                                <span class="font-bold">English</span>
                                @if(app()->getLocale() == 'en')
                                <svg class="ml-auto size-4 text-cyan-500" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                @endif
                            </a>
                            <a href="{{ route('lang.switch', 'ja') }}"
                                class="flex items-center gap-x-3 py-2.5 px-3 rounded-xl text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors {{ app()->getLocale() == 'ja' ? 'bg-gray-50 dark:bg-gray-900/50' : '' }}">
                                <span class="text-lg">🇯🇵</span>
                                <span class="font-bold">日本語</span>
                                @if(app()->getLocale() == 'ja')
                                <svg class="ml-auto size-4 text-cyan-500" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                @endif
                            </a>
                        </div>
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button type="button"
                        @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode); document.documentElement.classList.toggle('dark', darkMode)"
                        class="inline-flex flex-shrink-0 justify-center items-center gap-2 h-[2.375rem] w-[2.375rem] rounded-full font-medium bg-white text-gray-700 align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-white transition-all text-xs dark:bg-gray-800 dark:hover:bg-slate-800 dark:text-gray-400 dark:hover:text-white dark:focus:ring-gray-700 dark:focus:ring-offset-gray-800">
                        <svg x-show="!darkMode" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16"
                            height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278zM4.858 1.311A7.269 7.269 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.316 7.316 0 0 0 5.205-2.162c-.337.042-.68.063-1.029.063-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286z" />
                        </svg>
                        <svg x-show="darkMode" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" viewBox="0 0 16 16">
                            <path
                                d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z" />
                        </svg>
                    </button>

                    <!-- Profile Dropdown -->
                    <div class="relative inline-flex" x-data="{ 
                        open: false, 
                        avatarUrl: '{{ Auth::user()->avatar_url }}' 
                    }" @avatar-updated.window="avatarUrl = $event.detail.url">
                        <button type="button" @click="open = !open" @click.away="open = false"
                            class="inline-flex flex-shrink-0 justify-center items-center gap-2 h-[2.375rem] w-[2.375rem] rounded-full font-medium bg-white text-gray-700 align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-white transition-all text-xs dark:bg-gray-800 dark:hover:bg-slate-800 dark:text-gray-400 dark:hover:text-white dark:focus:ring-gray-700 dark:focus:ring-offset-gray-800">
                            <img class="inline-block h-[2.375rem] w-[2.375rem] rounded-full ring-2 ring-white dark:ring-gray-800 object-cover"
                                :src="avatarUrl" alt="{{ Auth::user()->name }}">
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 top-full mt-2 min-w-[15rem] bg-white shadow-xl rounded-2xl p-2 dark:bg-gray-800 dark:border dark:border-gray-700 z-50 border border-slate-100"
                            x-cloak>
                            <div
                                class="py-3 px-5 -m-2 bg-slate-50 rounded-t-2xl dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700">
                                <p
                                    class="text-[11px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                    {{ __('Signed in as') }}</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">{{
                                    Auth::user()->name }}</p>
                                <p class="text-xs font-medium text-slate-500 truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <div class="mt-2 py-2">
                                <a class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-xl text-sm font-bold text-slate-700 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-slate-300 dark:hover:bg-gray-700 dark:hover:text-white transition-colors"
                                    href="{{ route('profile.edit') }}">
                                    <svg class="flex-shrink-0 size-4 text-cyan-500" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    {{ __('Account Settings') }}
                                </a>

                                @if(session()->has('impersonated_by'))
                                <div class="my-2 border-t border-slate-100 dark:border-slate-700"></div>
                                <form method="POST" action="{{ route('admin.impersonate.leave') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full flex items-center gap-x-3.5 py-2.5 px-3 rounded-xl text-sm font-bold text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-500/10 transition-colors">
                                        <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                            <polyline points="16 17 21 12 16 7"></polyline>
                                            <line x1="21" y1="12" x2="9" y2="12"></line>
                                        </svg>
                                        {{ __('退出切換 (返回)') }}
                                    </button>
                                </form>
                                @elseif(auth()->user()->isSystemAdmin())
                                <div class="my-2 border-t border-slate-100 dark:border-slate-700"></div>
                                <button type="button" @click="open = false; $dispatch('open-impersonate-modal')"
                                    class="w-full flex items-center gap-x-3.5 py-2.5 px-3 rounded-xl text-sm font-bold text-slate-700 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-slate-300 dark:hover:bg-gray-700 dark:hover:text-white transition-colors">
                                    <svg class="flex-shrink-0 size-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                    {{ __('切換帳號') }}
                                </button>
                                @endif

                                <div class="my-2 border-t border-slate-100 dark:border-slate-700"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full flex items-center gap-x-3.5 py-2.5 px-3 rounded-xl text-sm font-bold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors">
                                        <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                            <polyline points="16 17 21 12 16 7" />
                                            <line x1="21" x2="9" y1="12" y2="12" />
                                        </svg>
                                        {{ __('Logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- ========== END HEADER ========== -->

    <!-- ========== MAIN CONTENT ========== -->
    <!-- Sidebar Toggle (Mobile) -->
    <div
        class="sticky top-[3.75rem] inset-x-0 z-20 bg-white border-y px-4 sm:px-6 md:px-8 lg:hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center py-4">
            <!-- Navigation Toggle -->
            <button type="button" class="text-gray-500 hover:text-gray-600" @click="sidebarOpen = !sidebarOpen">
                <span class="sr-only">Toggle Navigation</span>
                <svg class="w-5 h-5" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z" />
                </svg>
            </button>
            <!-- End Navigation Toggle -->

            <!-- Breadcrumb -->
            <x-breadcrumbs class="ms-3" />
            <!-- End Breadcrumb -->
        </div>
    </div>
    <!-- End Sidebar Toggle -->

    <!-- Sidebar -->
    <div id="application-sidebar"
        class="fixed top-0 left-0 bottom-0 z-[60] w-[var(--admin-sidebar-width)] bg-white dark:bg-[#0f172a] pt-5 pb-10 border-e border-slate-200 dark:border-none overflow-y-auto overflow-x-hidden transition-all duration-300 transform lg:translate-x-0 shadow-xl dark:shadow-2xl flex flex-col"
        :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
         ]" x-init="$nextTick(() => {
            const activeItem = $el.querySelector('.active, .bg-slate-100, .dark\\:bg-white\\/5');
            if (activeItem) {
                activeItem.scrollIntoView({ block: 'center', behavior: 'instant' });
            }
        })">

        <!-- Close Button (Mobile) -->
        <button type="button" @click="sidebarOpen = false"
            class="absolute top-4 right-4 text-slate-500 hover:text-slate-800 lg:hidden dark:text-slate-400 dark:hover:text-slate-200">
            <span class="sr-only">Close sidebar</span>
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Sidebar Header (Logo & Toggle) -->
        <div class="mb-8 mt-2 flex items-center h-8 transition-all duration-300 shrink-0 w-full"
            :class="sidebarCollapsed ? 'px-[14px]' : 'px-4'">

            <!-- Logo (Hidden when collapsed to save space) -->
            <a class="flex items-center gap-x-2 text-xl font-bold text-slate-900 dark:text-white font-display tracking-tight whitespace-nowrap overflow-hidden transition-all duration-300"
                href="{{ route('admin.dashboard') }}" aria-label="Brand"
                :class="sidebarCollapsed ? 'max-w-0 opacity-0' : 'max-w-[200px] opacity-100'">
                <img src="{{ auth()->user()->company && auth()->user()->company->hasCustomBrandingEnabled() && auth()->user()->company->logo_url ? auth()->user()->company->logo_url : asset('starcloud_icon.png') }}" 
                    alt="{{ auth()->user()->company ? auth()->user()->company->name : config('app.name') }} Logo"
                    class="w-8 h-8 max-h-8 max-w-[32px] object-contain shrink-0">
                <span>StillBuilding <span class="text-cyan-500">Life</span></span>
            </a>

            <div class="flex-1 transition-all duration-300"></div>

            <!-- Unified Toggle Button (Inside Sidebar) -->
            <button type="button" @click="sidebarCollapsed = !sidebarCollapsed" x-cloak
                class="hidden lg:flex items-center justify-center size-8 rounded-xl bg-slate-50 dark:bg-slate-800/80 text-slate-400 hover:bg-cyan-500 hover:text-white border border-slate-200 dark:border-slate-700 transition-all shrink-0">
                <svg class="size-4.5 transition-transform duration-500" :class="sidebarCollapsed ? 'rotate-180' : ''"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m11 17-5-5 5-5M18 17l-5-5 5-5" />
                </svg>
            </button>
        </div>

        <nav class="px-4 py-2 w-full flex flex-col flex-wrap transition-all duration-300 flex-1"
            :class="sidebarCollapsed ? 'px-2' : 'px-5'">
            <ul class="space-y-1.5">
                @include('layouts.partials.sidebar-menu')
            </ul>
        </nav>
    </div>
    <!-- End Sidebar -->

    <!-- Content -->
    <div class="admin-content-shell w-full pt-4 lg:pt-5 pb-12 px-4 sm:px-6 md:px-8 lg:pl-[var(--admin-sidebar-offset)] transition-opacity duration-200">
        <x-breadcrumbs class="mb-4 hidden lg:flex" />
        <main>
            @yield('content')
        </main>
    </div>
    <!-- End Content -->

    @if(auth()->user() && auth()->user()->isSystemAdmin())
        @include('admin.impersonate.modal')
    @endif

    <x-toast />

    @yield('scripts')
</body>

</html>
