@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.basic-settings.payment-configs.index') }}" class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-slate-500 hover:text-cyan-500 transition-all shadow-sm">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{ __('Create Payment Config') }}</h1>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-widest">{{ __('Define new third-party payment parameters') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
             <button type="submit" form="create-form" class="btn-luxury-primary px-8 flex items-center gap-2">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                <span>{{ __('Save Config') }}</span>
            </button>
        </div>
    </div>

    <form id="create-form" 
        x-data="{
            submitForm() {
                if (!this.$el.name.value.trim()) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: '{{ __("Please enter configuration name") }}', type: 'error' } 
                    }));
                    return;
                }
                this.$el.submit();
            }
        }"
        @submit.prevent="submitForm"
        action="{{ route('admin.basic-settings.payment-configs.store') }}" 
        method="POST" 
        class="space-y-6"
        novalidate>
        @csrf

        @if ($errors->any())
        <div class="p-5 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-2xl flex items-start gap-4 animate-luxury-in font-bold">
            <div class="w-8 h-8 bg-rose-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="text-sm tracking-wide">{{ __('Please check the following errors:') }}</p>
                <ul class="mt-1 text-xs list-disc list-inside opacity-80">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Column: Primary Info -->
            <div class="lg:col-span-12 space-y-6">
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in relative z-20">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Configuration Name') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required class="luxury-input w-full" placeholder="{{ __('e.g., Company Standard Pay') }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Belongs To Company') }}</label>
                            <x-searchable-select 
                                name="company_id" 
                                :selected="old('company_id')"
                                :placeholder="__('Select Company')"
                            >
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" 
                                        {{ old('company_id') == $company->id ? 'selected' : '' }}
                                        data-title="{{ $company->name }}{{ $company->code ? ' ('.$company->code.')' : '' }}">
                                        {{ $company->name }}{{ $company->code ? ' ('.$company->code.')' : '' }}
                                    </option>
                                @endforeach
                            </x-searchable-select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- provider groups -->
            <div class="lg:col-span-6 space-y-6">
                <!-- ECPay -->
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in group/card" style="animation-delay: 50ms">
                    <div class="flex items-center gap-5 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 flex items-center justify-center text-emerald-500 border border-emerald-500/20 shadow-lg shadow-emerald-500/5 group-hover/card:scale-110 transition-transform duration-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight font-display uppercase">{{ __('ECPay Invoice') }}</h3>
                        </div>
                    </div>

                    <div class="space-y-6" x-data="{ vals: { store_id: '', hash_key: '', hash_iv: '', email: '' }, get anyFilled() { return Object.values(this.vals).some(v => (v || '').trim() !== '') } }">
                        <p class="text-[11px] font-bold text-slate-400 leading-relaxed -mt-2">{{ __('Optional. If you fill in any field, all fields below become required.') }}</p>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Invoice Store ID') }}<span x-show="anyFilled" class="text-rose-500 ml-0.5">*</span></label>
                            <input type="text" name="settings[ecpay_invoice][store_id]" x-model="vals.store_id" class="luxury-input w-full">
                            @error('settings.ecpay_invoice.store_id')<p class="mt-1.5 text-[11px] font-bold text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Invoice HashKey') }}<span x-show="anyFilled" class="text-rose-500 ml-0.5">*</span></label>
                            <input type="text" name="settings[ecpay_invoice][hash_key]" x-model="vals.hash_key" class="luxury-input w-full">
                            @error('settings.ecpay_invoice.hash_key')<p class="mt-1.5 text-[11px] font-bold text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Invoice HashIV') }}<span x-show="anyFilled" class="text-rose-500 ml-0.5">*</span></label>
                            <input type="text" name="settings[ecpay_invoice][hash_iv]" x-model="vals.hash_iv" class="luxury-input w-full">
                            @error('settings.ecpay_invoice.hash_iv')<p class="mt-1.5 text-[11px] font-bold text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Invoice Notification Email') }}<span x-show="anyFilled" class="text-rose-500 ml-0.5">*</span></label>
                            <input type="text" name="settings[ecpay_invoice][email]" x-model="vals.email" class="luxury-input w-full">
                            @error('settings.ecpay_invoice.email')<p class="mt-1.5 text-[11px] font-bold text-rose-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <!-- E.SUN -->
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in group/card" style="animation-delay: 100ms">
                    <div class="flex items-center gap-5 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/10 to-blue-500/10 flex items-center justify-center text-indigo-500 border border-indigo-500/20 shadow-lg shadow-indigo-500/5 group-hover/card:scale-110 transition-transform duration-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.875 12h1.125a3.375 3.375 0 0 1 3.375 3.375v1.125m-11.25 4.5h1.125a3.375 3.375 0 0 0 3.375-3.375V16.5a3.375 3.375 0 0 1 3.375-3.375h1.125m-11.25 4.5h1.125a3.375 3.375 0 0 1 3.375 3.375v1.125m-3.375-1.125h.008v.008h-.008v-.008Zm3.375 3.375h.008v.008h-.008v-.008Zm0-3.375h.008v.008h-.008v-.008Zm-3.375-3.375h.008v.008h-.008v-.008Zm0 3.375h.008v.008h-.008v-.008Z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight font-display uppercase">{{ __('E.SUN QR Scan') }}</h3>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Scan Store ID') }}</label>
                            <input type="text" name="settings[esun_scan][store_id]" class="luxury-input w-full">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Scan Term ID') }}</label>
                            <input type="text" name="settings[esun_scan][term_id]" class="luxury-input w-full">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('Scan Key') }}</label>
                            <input type="text" name="settings[esun_scan][key]" class="luxury-input w-full">
                        </div>
                    </div>
                </div>

                <!-- LINE Pay Direct -->
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in group/card" style="animation-delay: 150ms">
                    <div class="flex items-center gap-5 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-lime-500/10 to-emerald-500/10 flex items-center justify-center text-emerald-500 border border-emerald-500/20 shadow-lg shadow-emerald-500/5 group-hover/card:scale-110 transition-transform duration-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m24.408 0a8.959 8.959 0 0 1 .284-2.253m0 2.253C20.46 17.1 16.485 19.5 12 19.5S3.538 17.1 2.284 14.253"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight font-display uppercase">{{ __('LINE Official Pay') }}</h3>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('LINE-ChannelId') }}</label>
                            <input type="text" name="settings[line_pay][channel_id]" class="luxury-input w-full">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('LINE-ChannelSecret') }}</label>
                            <input type="text" name="settings[line_pay][channel_secret]" class="luxury-input w-full">
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-6 space-y-6">
                <!-- Tappay -->
                <div class="luxury-card rounded-3xl p-8 animate-luxury-in group/card" style="animation-delay: 200ms">
                    <div class="flex items-center gap-5 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/10 to-violet-500/10 flex items-center justify-center text-indigo-500 border border-indigo-500/20 shadow-lg shadow-indigo-500/5 group-hover/card:scale-110 transition-transform duration-500">
                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight font-display uppercase">{{ __('TapPay Integration') }}</h3>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('PARTNER_KEY') }}</label>
                                <input type="text" name="settings[tappay][partner_key]" class="luxury-input w-full">
                            </div>
                            <div>
                                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('APP_ID') }}</label>
                                <input type="text" name="settings[tappay][app_id]" class="luxury-input w-full">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ __('APP_KEY') }}</label>
                            <input type="text" name="settings[tappay][app_key]" class="luxury-input w-full">
                        </div>
                        <div class="pt-6 border-t border-slate-100 dark:border-slate-800/80">
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 font-display">{{ __('Merchant IDs') }}</p>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('LINE_MERCHANT_ID') }}</label>
                                    <input type="text" name="settings[tappay][line_merchant_id]" class="luxury-input w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('JKO_MERCHANT_ID') }}</label>
                                    <input type="text" name="settings[tappay][jko_merchant_id]" class="luxury-input w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('PI_MERCHANT_ID') }}</label>
                                    <input type="text" name="settings[tappay][pi_merchant_id]" class="luxury-input w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('PS_MERCHANT_ID') }}</label>
                                    <input type="text" name="settings[tappay][ps_merchant_id]" class="luxury-input w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('EASY_MERCHANT_ID') }}</label>
                                    <input type="text" name="settings[tappay][easy_merchant_id]" class="luxury-input w-full">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
