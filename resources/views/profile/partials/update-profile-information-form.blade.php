<section>
    <header>
        <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm font-bold text-slate-400 dark:text-slate-400 uppercase tracking-widest">
            {{ __('Update your account\'s profile information and email address.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form id="profile-update-form" method="post" action="{{ route('profile.update') }}" class="mt-8 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <!-- Hidden Avatar Input -->
        <input type="file" name="avatar" class="hidden" accept="image/*" 
               @change="uploadAvatar($event)">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="name" :value="__('Name')" class="text-xs font-black text-slate-500 uppercase tracking-widest mb-2 ml-1" />
                <input id="name" name="name" type="text" class="py-3 px-4 block w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all outline-none" value="{{ old('name', $user->name) }}" required autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('Phone')" class="text-xs font-black text-slate-500 uppercase tracking-widest mb-2 ml-1" />
                <input id="phone" name="phone" type="text" class="py-3 px-4 block w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all outline-none" value="{{ old('phone', $user->phone) }}" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-xs font-black text-slate-500 uppercase tracking-widest mb-2 ml-1" />
            <input id="email" name="email" type="email" class="py-3 px-4 block w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all outline-none" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200 dark:border-amber-900/30">
                    <p class="text-xs font-bold text-amber-700 dark:text-amber-400 flex items-center gap-x-2">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {{ __('Your email address is unverified.') }}
                    </p>

                    <button form="send-verification" class="mt-2 text-xs font-black text-amber-600 dark:text-amber-500 hover:text-amber-700 underline underline-offset-4 decoration-amber-500/30 focus:outline-none transition-colors">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-xs font-black text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button type="submit" class="btn-luxury-primary px-8">
                <span>{{ __('Save') }}</span>
            </button>

        </div>
    </form>
</section>
