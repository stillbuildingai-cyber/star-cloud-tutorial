@extends('layouts.admin')

@section('content')
    <div x-data="{ 
            photoPreview: null, 
            isUploading: false,
            uploadAvatar(event) {
                const file = event.target.files[0];
                if (!file) return;
                
                // Size Check (5MB = 5 * 1024 * 1024 bytes)
                if (file.size > 5 * 1024 * 1024) {
                    alert('{{ __('The image is too large. Please upload an image smaller than 5MB.') }}');
                    return;
                }

                this.isUploading = true;
                
                // Show local preview immediately
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);

                const formData = new FormData();
                formData.append('avatar', file);
                formData.append('_token', '{{ csrf_token() }}');

                fetch('{{ route('profile.avatar') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.photoPreview = data.avatar_url;
                        // Dispatch global event for other components (like header)
                        window.dispatchEvent(new CustomEvent('avatar-updated', { 
                            detail: { url: data.avatar_url } 
                        }));
                    } else {
                        alert(data.message || 'Upload failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during upload');
                })
                .finally(() => {
                    this.isUploading = false;
                });
            }
         }" 
         class="space-y-8 animate-luxury-in text-slate-800 dark:text-white">
        <!-- Luxury Profile Banner -->
        <div class="luxury-card rounded-[2.5rem] p-1 border-luxury-accent/10 relative overflow-hidden group/banner">

            <div class="relative p-8 md:p-12 flex flex-col lg:flex-row items-center justify-between gap-12 z-10">
                <!-- Left: Profile Core Info -->
                <div class="flex flex-col md:flex-row items-center gap-8 flex-1">
                    <!-- Profile Avatar Area -->
                    <div class="relative cursor-pointer group/avatar" 
                         @click="document.querySelector('#profile-update-form input[name=avatar]').click()">
                        <div class="size-32 md:size-40 rounded-full overflow-hidden ring-4 ring-white dark:ring-slate-800 shadow-2xl transition-transform duration-500 group-hover/avatar:scale-105 relative">
                            <template x-if="!photoPreview">
                                <img src="{{ $user->avatar_url }}" class="size-full object-cover" alt="{{ $user->name }}">
                            </template>
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="size-full object-cover">
                            </template>

                            <!-- Loading Overlay -->
                            <div x-show="isUploading" 
                                 class="absolute inset-0 bg-black/60 flex items-center justify-center z-10"
                                 x-transition:enter="transition opacity-0"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100">
                                <svg class="animate-spin size-8 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover/avatar:opacity-100 transition-opacity">
                            <svg class="size-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="absolute -bottom-2 -right-2 size-10 rounded-xl bg-cyan-500 flex items-center justify-center text-white shadow-lg shadow-cyan-500/40 border-2 border-white dark:border-slate-800">
                            <svg x-show="!isUploading" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            <svg x-show="isUploading" class="animate-spin size-5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                    </div>

                    <div class="text-center md:text-left">
                        <div class="flex flex-col md:flex-row md:items-center gap-x-4 mb-2">
                            <h2 class="text-4xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ $user->name }}</h2>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 uppercase tracking-widest self-center md:self-auto mt-2 md:mt-0">
                                {{ __('Administrator') }}
                            </span>
                        </div>
                        <p class="text-slate-400 font-bold tracking-wide flex items-center justify-center md:justify-start gap-x-2">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v10a2 2 0 002 2z"/></svg>
                            {{ $user->email }}
                        </p>
                        <div class="mt-6 flex flex-wrap justify-center md:justify-start gap-4">
                            <div class="px-5 py-2.5 rounded-2xl bg-white/50 dark:bg-slate-900/50 border border-white dark:border-slate-800 backdrop-blur-sm transition-all hover:bg-white dark:hover:bg-slate-900 text-center">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ __('Joined') }}</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $user->created_at->format('Y/m/d') }}</p>
                            </div>
                            <div class="px-5 py-2.5 rounded-2xl bg-white/50 dark:bg-slate-900/50 border border-white dark:border-slate-800 backdrop-blur-sm transition-all hover:bg-white dark:hover:bg-slate-900 text-center">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ __('Recent Login') }}</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $user->loginLogs->first() ? $user->loginLogs->first()->login_at->diffForHumans() : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Stats Summary -->
                <div class="flex items-center gap-12 pr-4 lg:border-l lg:border-slate-200/50 dark:lg:border-slate-700/50 lg:pl-12">
                    <div class="text-center group/stat">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-hover/stat:text-cyan-500 transition-colors">{{ __('Total Logins') }}</p>
                        <div class="relative inline-block">
                            <span class="text-4xl font-black text-slate-800 dark:text-white">{{ $user->loginLogs()->count() }}</span>
                            <div class="absolute -bottom-1 left-0 w-full h-1 bg-cyan-500/20 rounded-full scale-x-0 group-hover/stat:scale-x-100 transition-transform origin-left"></div>
                        </div>
                    </div>
                    <div class="h-12 w-px bg-slate-100 dark:bg-slate-800 hidden sm:block"></div>
                    <div class="text-center group/stat">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-hover/stat:text-cyan-500 transition-colors">{{ __('Account Status') }}</p>
                        <div class="flex items-center gap-3">
                            <span class="relative flex size-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full size-3 bg-emerald-500"></span>
                            </span>
                            <span class="text-2xl font-black text-slate-800 dark:text-white tracking-tight uppercase">{{ __('Active') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            <!-- Left Side: Profile & Password -->
            <div class="space-y-8">
                <div class="luxury-card rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 dark:shadow-none">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="luxury-card rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 dark:shadow-none">
                    @include('profile.partials.update-password-form')
                </div>

            </div>

            <!-- Right Side: Login History (Wider) -->
            <div class="space-y-8">
                <div class="luxury-card rounded-[2rem] p-8 min-h-[600px] shadow-xl shadow-slate-200/50 dark:shadow-none">
                    @include('profile.partials.login-history')
                </div>
            </div>
        </div>
    </div>
@endsection
