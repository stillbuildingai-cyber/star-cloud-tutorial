@extends('layouts.admin')

@section('content')
@php
    // 顯示語系 Tab = 公司所有機台已開語系的聯集（見 docs/API/product_multilingual_spec.md §4.2）
    $localeLabels = config('locales.supported', ['zh_TW' => '繁體中文']);
    $locales = \App\Models\System\Company::activeLocalesFor($product->company_id);

    // 保險：併入「此商品已有翻譯」的語系，避免公司聯集縮減時藏掉既有翻譯而無法編輯。
    $existingLocales = $product->translations->pluck('locale')
        ->merge($product->specTranslations->pluck('locale'))
        ->unique()->all();
    $order = array_keys($localeLabels); // 依白名單順序穩定排序
    $locales = collect($locales)->merge($existingLocales)->unique()
        ->sortBy(fn ($l) => ($i = array_search($l, $order)) === false ? 999 : $i)
        ->values()->all();

    $names = [];
    $specs = [];
    foreach ($locales as $locale) {
        $names[$locale] = $product->translations->where('locale', $locale)->first()?->value ?? '';
        $specs[$locale] = $product->specTranslations->where('locale', $locale)->first()?->value ?? '';
    }
    // zh_TW 翻譯為空時回退至主欄位
    if (empty($names['zh_TW'])) {
        $names['zh_TW'] = $product->name;
    }
    if (empty($specs['zh_TW'])) {
        $specs['zh_TW'] = $product->spec;
    }
@endphp

<div class="space-y-6" x-data="productForm({
    categories: @js($categories),
    companies: @js($companies),
    companySettings: @js($companySettings),
    product: @js($product),
    names: @js($names),
    specs: @js($specs),
    locales: @js($locales),
    localeLabels: @js($localeLabels),
    isEditing: true
})">
    <!-- Header -->
    <div class="flex items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.data-config.products.index') }}" class="p-2.5 rounded-xl bg-white dark:bg-slate-900 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors border border-slate-200/50 dark:border-slate-700/50">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            </a>
            <div>
                <h1 class="text-3xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Edit Product') }}</h1>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button type="submit" form="product-form" class="btn-luxury-primary px-8 py-3 h-12">
                <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                <span>{{ __('Save Changes') }}</span>
            </button>
        </div>
    </div>

    @if ($errors->any())
    <div class="luxury-card p-4 rounded-xl border-rose-500/20 bg-rose-500/5 sm:max-w-xl animate-luxury-in">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-rose-500 shrink-0 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
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

    <form id="product-form" action="{{ route('admin.data-config.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col lg:flex-row gap-8 items-start">
        @csrf
        @method('PUT')

        <!-- Side Column (Status & Company) -->
        <aside class="w-full lg:w-80 lg:sticky top-24 z-10 space-y-8">
                <!-- Product Image -->
                <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative z-[60]" style="animation-delay: 50ms">
                    <div class="space-y-6">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Product Image') }}</label>
                        
                        <div class="relative group">
                            <input type="hidden" name="remove_image" :value="formData.remove_image ? '1' : '0'">
                            
                            <template x-if="!imagePreview">
                                <div @click="$refs.imageInput.click()" class="aspect-square rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col items-center justify-center gap-4 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:border-cyan-500/50 transition-all duration-300 group">
                                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 group-hover:scale-110 group-hover:text-cyan-500 transition-all duration-300">
                                        <svg class="w-8 h-8 text-slate-400 dark:text-slate-500 group-hover:text-cyan-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-tighter">{{ __('Click to upload') }}</p>
                                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mt-1">{{ __('PNG, JPG, WEBP up to 10MB') }}</p>
                                        <p class="text-[10px] font-black text-cyan-500/80 dark:text-cyan-400/80 mt-1 uppercase tracking-tighter">{{ __('Recommended: 320x320 (Auto-cropped)') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="imagePreview">
                                <div class="relative aspect-square rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-xl group/image">
                                    <img :src="imagePreview" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover/image:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                        <button type="button" @click="$refs.imageInput.click()" class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-cyan-500 hover:text-white transition-all duration-300 shadow-lg">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                        </button>
                                        <button type="button" @click="removeImage" class="p-3 rounded-2xl bg-white text-slate-800 hover:bg-rose-500 hover:text-white transition-all duration-300 shadow-lg">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <input type="file" name="image" x-ref="imageInput" class="hidden" accept="image/*" @change="handleImageUpload">
                        </div>
                    </div>
                </div>

                <!-- Status & Company -->
                <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative z-[70]" style="animation-delay: 100ms">
                    <div class="space-y-6">
                        <div class="space-y-3">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Active Status') }}</label>
                            <div class="flex items-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" x-model="formData.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-800 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500 transition-colors"></div>
                                    <span class="ml-3 text-sm font-bold text-slate-600 dark:text-slate-300" x-text="formData.is_active ? '{{ __('Active') }}' : '{{ __('Disabled') }}'"></span>
                                </label>
                            </div>
                        </div>

                        @if(auth()->user()->isSystemAdmin())
                        <div class="h-px bg-slate-100 dark:bg-slate-800"></div>
                        <div class="space-y-3">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Company') }}</label>
                            <x-searchable-select id="product-company" name="company_id" x-model="formData.company_id" @change="updateCompanySettings($event.target.value)" :placeholder="__('Select Company')">
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ $product->company_id == $company->id ? 'selected' : '' }} data-title="{{ $company->name }}">{{ $company->name }}</option>
                                @endforeach
                            </x-searchable-select>
                        </div>
                        @else
                            <input type="hidden" name="company_id" value="{{ $product->company_id }}">
                        @endif
                    </div>
                </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 w-full space-y-8">
                <!-- Translation Section: Product Name (Multilingual) -->
                <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative z-[40]">
                    <div class="mb-6">
                        <h2 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Product Name (Multilingual)') }}</h2>
                    </div>
                    <x-product-locale-tabs field="names" active="activeNameLocale" />
                </div>

                <!-- Translation Section: Details (Multilingual) -->
                <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative z-[35]" style="animation-delay: 50ms">
                    <div class="mb-6">
                        <h2 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Details (Multilingual)') }}</h2>
                    </div>
                    <x-product-locale-tabs field="specs" active="activeSpecLocale" :required="false" multiline />
                </div>

                <!-- Basic Specs Section -->
                <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative z-[30]" style="animation-delay: 100ms">
                    <div class="mb-8">
                        <h2 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Basic Specifications') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="grid grid-cols-1 gap-6">
                            <div class="space-y-3">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Barcode') }}</label>
                                <input type="text" name="barcode" x-model="formData.barcode" class="luxury-input shadow-sm">
                            </div>
                            <div class="space-y-3">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Manufacturer') }}</label>
                                <input type="text" name="manufacturer" x-model="formData.manufacturer" class="luxury-input shadow-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="space-y-3">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Category') }}</label>
                                <x-searchable-select id="product-category" name="category_id" x-model="formData.category_id" :placeholder="__('Uncategorized')">
                                    <option value="">{{ __('Uncategorized') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }} data-title="{{ $category->localized_name }}">{{ $category->localized_name }}</option>
                                    @endforeach
                                </x-searchable-select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing Information -->
                <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative z-[20]" style="animation-delay: 200ms">
                    <div class="mb-8">
                        <h2 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Pricing Information') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 2xl:grid-cols-3 gap-8">
                        <div class="space-y-3">
                            <label class="text-xs font-black text-emerald-500 uppercase tracking-widest pl-1">{{ __('Sale Price') }} <span class="text-rose-500">*</span></label>
                            <div class="flex items-center h-14 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-emerald-500/20 transition-all overflow-hidden">
                                <button type="button" @click="formData.price = Math.max(0, parseInt(formData.price || 0) - 1)" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                </button>
                                <div class="flex-1 min-w-[72px]">
                                    <input type="number" name="price" x-model="formData.price" required class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 text-lg [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                                <button type="button" @click="formData.price = parseInt(formData.price || 0) + 1" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black text-cyan-500 dark:text-cyan-400 uppercase tracking-widest pl-1">{{ __('Member Price') }} <span class="text-rose-500">*</span></label>
                            <div class="flex items-center h-14 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all overflow-hidden">
                                <button type="button" @click="formData.member_price = Math.max(0, parseInt(formData.member_price || 0) - 1)" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                </button>
                                <div class="flex-1 min-w-[72px]">
                                    <input type="number" name="member_price" x-model="formData.member_price" required class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 text-lg [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                                <button type="button" @click="formData.member_price = parseInt(formData.member_price || 0) + 1" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest pl-1">{{ __('Cost') }} <span class="text-rose-500">*</span></label>
                            <div class="flex items-center h-14 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-slate-500/20 transition-all overflow-hidden">
                                <button type="button" @click="formData.cost = Math.max(0, parseInt(formData.cost || 0) - 1)" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                </button>
                                <div class="flex-1 min-w-[72px]">
                                    <input type="number" name="cost" x-model="formData.cost" required class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 text-lg [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                                <button type="button" @click="formData.cost = parseInt(formData.cost || 0) + 1" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Channel Limits -->
                <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in relative z-[10]" style="animation-delay: 300ms">
                    <div class="mb-8">
                        <h2 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Channel Limits') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <label class="text-xs font-black text-indigo-500 uppercase tracking-widest pl-1">{{ __('Track Channel Limit') }} <span class="text-rose-500">*</span></label>
                            <div class="flex items-center h-14 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all overflow-hidden">
                                <button type="button" @click="formData.track_limit = Math.max(0, parseInt(formData.track_limit || 0) - 1)" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-indigo-500 hover:bg-indigo-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                </button>
                                <div class="flex-1 min-w-[72px]">
                                    <input type="number" name="track_limit" x-model="formData.track_limit" required class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 text-lg [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                                <button type="button" @click="formData.track_limit = parseInt(formData.track_limit || 0) + 1" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-indigo-500 hover:bg-indigo-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="text-xs font-black text-amber-500 uppercase tracking-widest pl-1">{{ __('Spring Channel Limit') }} <span class="text-rose-500">*</span></label>
                            <div class="flex items-center h-14 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-amber-500/20 transition-all overflow-hidden">
                                <button type="button" @click="formData.spring_limit = Math.max(0, parseInt(formData.spring_limit || 0) - 1)" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                </button>
                                <div class="flex-1 min-w-[72px]">
                                    <input type="number" name="spring_limit" x-model="formData.spring_limit" required class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 text-lg [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                                <button type="button" @click="formData.spring_limit = parseInt(formData.spring_limit || 0) + 1" class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-amber-500 hover:bg-amber-500/5 active:scale-90 transition-all">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Features -->
                <div class="luxury-card p-8 rounded-[2.5rem] animate-luxury-in overflow-hidden" 
                     x-show="companySettings.enable_points || companySettings.enable_material_code"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 max-h-0"
                     x-transition:enter-end="opacity-100 max-h-screen"
                     style="animation-delay: 400ms">
                    <div class="mb-8">
                        <h2 class="text-xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Feature Toggles') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <template x-if="companySettings.enable_material_code">
                            <div class="space-y-3 animate-luxury-in">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest pl-1">{{ __('Material Code') }}</label>
                                <input type="text" name="metadata[material_code]" x-model="formData.metadata.material_code" class="luxury-input shadow-sm">
                            </div>
                        </template>

                        <template x-if="companySettings.enable_points">
                            <div class="contents">
                                <div class="space-y-3 animate-luxury-in">
                                    <label class="text-xs font-black text-cyan-500 uppercase tracking-widest pl-1">{{ __('Full Points') }}</label>
                                    <div class="flex items-center h-14 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all overflow-hidden">
                                        <button type="button" @click="formData.metadata.points_full = Math.max(0, parseInt(formData.metadata.points_full || 0) - 1)" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="flex-1 min-w-[60px]">
                                            <input type="number" name="metadata[points_full]" x-model="formData.metadata.points_full" class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 text-lg [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </div>
                                        <button type="button" @click="formData.metadata.points_full = parseInt(formData.metadata.points_full || 0) + 1" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-3 animate-luxury-in">
                                    <label class="text-xs font-black text-cyan-500 uppercase tracking-widest pl-1">{{ __('Half Points') }}</label>
                                    <div class="flex items-center h-14 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all overflow-hidden">
                                        <button type="button" @click="formData.metadata.points_half = Math.max(0, parseInt(formData.metadata.points_half || 0) - 1)" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="flex-1 min-w-[60px]">
                                            <input type="number" name="metadata[points_half]" x-model="formData.metadata.points_half" class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 text-lg [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </div>
                                        <button type="button" @click="formData.metadata.points_half = parseInt(formData.metadata.points_half || 0) + 1" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-3 animate-luxury-in">
                                    <label class="text-xs font-black text-cyan-500 uppercase tracking-widest pl-1">{{ __('Half Points Amount') }}</label>
                                    <div class="flex items-center h-14 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all overflow-hidden">
                                        <button type="button" @click="formData.metadata.points_half_amount = Math.max(0, parseInt(formData.metadata.points_half_amount || 0) - 1)" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="flex-1 min-w-[60px]">
                                            <input type="number" name="metadata[points_half_amount]" x-model="formData.metadata.points_half_amount" class="w-full bg-transparent border-none text-center font-black text-slate-800 dark:text-white focus:ring-0 text-lg [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                        </div>
                                        <button type="button" @click="formData.metadata.points_half_amount = parseInt(formData.metadata.points_half_amount || 0) + 1" class="shrink-0 w-10 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Action Bar (Footer) -->
                <div class="pt-8 flex items-center justify-end gap-4 border-t border-slate-100 dark:border-slate-800 animate-luxury-in" style="animation-delay: 500ms">
                    <a href="{{ route('admin.data-config.products.index') }}" class="btn-luxury-ghost px-8 h-12">{{ __('Cancel') }}</a>
                    <button type="submit" form="product-form" class="btn-luxury-primary px-12 h-14 shadow-xl shadow-cyan-500/20">
                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        <span>{{ __('Save Changes') }}</span>
                    </button>
                </div>
            </div>
    </form>
</div>
@push('styles')
<style>
    /* 移除 Chrome, Safari, Edge, Opera 的原生加減按鈕 */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        appearance: none;
        margin: 0;
    }
    /* 移除 Firefox 的原生加減按鈕 */
    input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>
@endpush

@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productForm', (config) => ({
            categories: config.categories,
            companies: config.companies,
            companySettings: config.companySettings,
            isEditing: config.isEditing,
            imagePreview: config.product.image_url || null,
            locales: config.locales,
            localeLabels: config.localeLabels,
            activeNameLocale: (config.locales && config.locales[0]) || 'zh_TW',
            activeSpecLocale: (config.locales && config.locales[0]) || 'zh_TW',
            formData: {
                ...config.product,
                names: config.names,
                specs: config.specs,
                remove_image: false,
                metadata: {
                    material_code: config.product.metadata?.material_code || '',
                    points_full: config.product.metadata?.points_full || 0,
                    points_half: config.product.metadata?.points_half || 0,
                    points_half_amount: config.product.metadata?.points_half_amount || 0
                },
                is_active: config.product.is_active ? true : false
            },

            handleImageUpload(event) {
                const file = event.target.files[0];
                if (file) {
                    this.formData.remove_image = false;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.imagePreview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            removeImage() {
                this.imagePreview = null;
                this.formData.remove_image = true;
                this.$refs.imageInput.value = '';
            },

            updateCompanySettings(companyId) {
                if (!companyId) {
                    this.companySettings = { enable_material_code: false, enable_points: false };
                    return;
                }
                const company = this.companies.find(c => c.id == companyId);
                if (company) {
                    this.companySettings = {
                        enable_material_code: company.settings?.enable_material_code || false,
                        enable_points: company.settings?.enable_points || false
                    };
                }
            }
        }));
    });
</script>
@endsection
