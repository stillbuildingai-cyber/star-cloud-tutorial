@extends('layouts.admin')

@php
$routeName = request()->route()->getName();
$baseRoute = 'admin.data-config.products';

$roleSelectConfig = [
"placeholder" => __('Select Category'),
"hasSearch" => true,
"searchPlaceholder" => __('Search Category...'),
"toggleClasses" => "hs-select-toggle luxury-select-toggle",
"dropdownClasses" => "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10
rounded-xl shadow-2xl mt-2 z-[100]",
"optionClasses" => "hs-select-option py-2.5 px-3 text-sm text-slate-800 dark:text-slate-300 cursor-pointer
hover:bg-slate-100 dark:hover:bg-white/5 rounded-lg flex items-center justify-between",
];
@endphp

@section('content')
<div class="space-y-2 pb-20" x-data="productManager" data-categories="{{ json_encode($categories->items()) }}"
    data-settings="{{ json_encode($companySettings) }}" data-errors="{{ json_encode($errors->any()) }}"
    data-store-url="{{ route($baseRoute . '.store') }}" data-index-url="{{ route($baseRoute . '.index') }}">

    {{-- Page Header --}}
    <x-page-header :title="__('Product Management')"
        :subtitle="__('Manage your catalog, prices, and multilingual details.')"
        container-class="flex flex-wrap md:flex-nowrap items-center justify-between gap-4"
        actions-class="contents md:flex md:items-center md:gap-3 md:shrink-0">
        <template x-if="activeTab === 'products'">
            <div class="contents md:flex md:items-center md:justify-end md:gap-3">
                <a href="{{ route($baseRoute . '.create') }}"
                    class="btn-luxury-primary whitespace-nowrap flex items-center justify-center gap-2 transition-all duration-300 shadow-lg shadow-emerald-500/10 shrink-0 order-1 md:order-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="text-sm sm:text-base font-black tracking-tight uppercase">{{ __('Add Product') }}</span>
                </a>
                <div class="order-2 w-full flex items-center gap-2 md:contents">
                    <button @click="syncToAllMachines()"
                        class="btn-luxury-ghost group whitespace-nowrap flex items-center justify-center gap-2 transition-all duration-300 py-2 sm:py-2.5 px-4 sm:px-6 rounded-2xl border-slate-200 dark:border-white/10 hover:border-cyan-500/50 hover:bg-cyan-500/5 flex-1 md:flex-none min-w-[150px] md:order-2">
                        <svg class="w-4 h-4 text-cyan-500 group-hover:rotate-180 transition-transform duration-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span class="text-sm font-black tracking-tight uppercase">{{ __('Sync to All Machines') }}</span>
                    </button>
                </div>
            </div>
        </template>
        <template x-if="activeTab === 'categories'">
            <button @click="openCategoryModal()" type="button"
                class="btn-luxury-primary whitespace-nowrap flex items-center gap-2 transition-all duration-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="text-sm sm:text-base">{{ __('Add Category') }}</span>
            </button>
        </template>
    </x-page-header>

    {{-- Tab Navigation --}}
    <x-tab-nav>
        <x-tab-nav-item value="products" :label="__('Product List')" />
        <x-tab-nav-item value="categories" :label="__('Category Management')" />
        <x-tab-nav-item value="logs" :label="__('Operation Logs')" />
    </x-tab-nav>

    {{-- Tab Contents --}}

    {{-- Products Tab --}}
    <div x-show="activeTab === 'products'" class="luxury-card rounded-3xl p-6 animate-luxury-in relative min-h-[300px]"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

        {{-- Loading Overlay (Products) --}}
        <x-luxury-spinner show="tabLoading === 'products'" z-index="z-20">
            <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-14v10l-8-4m16 4l-8 4m0-10l-8 4" />
            </svg>
        </x-luxury-spinner>

        <div id="tab-products-container" class="relative">
            @include('admin.products.partials.tab-products')
        </div>
    </div>

    {{-- Categories Tab --}}
    <div x-show="activeTab === 'categories'"
        class="luxury-card rounded-3xl p-6 animate-luxury-in relative min-h-[300px]"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

        {{-- Loading Overlay (Categories) --}}
        <x-luxury-spinner show="tabLoading === 'categories'" z-index="z-20">
            <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </x-luxury-spinner>

        <div id="tab-categories-container" class="relative">
            @include('admin.products.partials.tab-categories')
        </div>
    </div>

    {{-- Logs Tab --}}
    <div x-show="activeTab === 'logs'"
        class="luxury-card rounded-3xl p-6 animate-luxury-in relative min-h-[300px]"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

        {{-- Loading Overlay (Logs) --}}
        <x-luxury-spinner show="tabLoading === 'logs'" z-index="z-20">
            <svg class="w-6 h-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-luxury-spinner>

        <div id="tab-logs-container" class="relative">
            {{-- Content will be loaded via AJAX --}}
        </div>
    </div>

    {{-- Import Products Modal --}}
    <div x-show="isImportModalOpen" class="fixed inset-0 z-[110] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isImportModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                @click="isImportModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="isImportModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="inline-block w-full max-w-lg p-8 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-900 shadow-2xl rounded-3xl border border-slate-100 dark:border-white/10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">
                        {{ __('Import Products') }}
                    </h3>
                    <button @click="isImportModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitImportForm" class="space-y-6" enctype="multipart/form-data">
                    <div class="space-y-5 px-1">
                        @if(auth()->user()->isSystemAdmin())
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Affiliated Company') }} <span class="text-rose-500">*</span>
                            </label>
                            <x-searchable-select name="import_company_id" id="product-import-company-id" :options="$companies"
                                :placeholder="__('Select Company')"
                                @change="importFormFields.company_id = $event.target.value" required />
                        </div>
                        @endif

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Excel File') }} <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative group">
                                <input type="file" id="product-import-file" @change="importFormFields.file = $event.target.files[0]"
                                    accept=".xlsx" class="luxury-input w-full pr-12 cursor-pointer" required>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('Only .xlsx files are supported (Max 5MB)') }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                {{ __('Product Images (ZIP)') }}
                            </label>
                            <div class="relative group">
                                <input type="file" id="product-import-images" @change="importFormFields.images = $event.target.files[0]"
                                    accept=".zip" class="luxury-input w-full pr-12 cursor-pointer">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('Optional. Reference each filename in the image column, then upload them here as a .zip (Max 60MB)') }}
                            </p>
                        </div>

                        <div class="bg-slate-50 dark:bg-white/5 rounded-2xl p-4 border border-slate-100 dark:border-white/5">
                            <h4 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                                {{ __('Download Template') }}
                            </h4>
                            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                                {{ __('Please use our standard template to ensure data compatibility.') }}
                            </p>
                            <a href="{{ route($baseRoute . '.template') }}" download
                                @click="setTimeout(() => { const bar = document.getElementById('top-loading-bar'); if(bar) bar.classList.remove('loading'); }, 1000);"
                                class="inline-flex items-center gap-2 text-sm font-bold text-cyan-600 hover:text-cyan-700 transition-colors group">
                                <span>{{ __('Download Template') }}</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-12 pt-6 border-t border-slate-100 dark:border-white/5">
                        <button type="button" @click="isImportModalOpen = false"
                            class="px-6 py-2.5 text-sm font-black text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 uppercase tracking-widest transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn-luxury-primary px-10 py-3 shadow-lg bg-cyan-600 hover:bg-cyan-700 shadow-cyan-500/20"
                            :disabled="loading">
                            <template x-if="!loading">
                                <span>{{ __('Start Import') }}</span>
                            </template>
                            <template x-if="loading">
                                <div class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('Processing...') }}</span>
                                </div>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div x-show="isCategoryModalOpen" class="fixed inset-0 z-[110] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isCategoryModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"
                @click="isCategoryModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="isCategoryModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block w-full max-w-lg p-8 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-900 shadow-2xl rounded-3xl border border-slate-100 dark:border-white/10">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight"
                        x-text="categoryModalMode === 'create' ? '{{ __('Add Category') }}' : '{{ __('Edit Category') }}'">
                    </h3>
                    <button @click="isCategoryModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="categoryFormAction" method="POST" class="space-y-6">
                    @csrf
                    <template x-if="categoryModalMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="space-y-6">
                        <!-- 1. Company Selection (If Admin) -->
                        @if(auth()->user()->isSystemAdmin())
                        <div
                            class="p-6 bg-slate-50 dark:bg-slate-800/30 rounded-3xl border border-slate-100 dark:border-white/5 space-y-3">
                            <label class="block text-sm font-black text-slate-700 dark:text-slate-200 px-1">{{
                                __('Affiliated Company') }}</label>

                            <!-- Searchable Select Wrapper -->
                            <div id="category_company_select_wrapper" class="relative">
                                <!-- Will be hydrated by JS -->
                            </div>

                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">{{ __('Type to search or leave blank for system defaults.') }}</p>
                        </div>
                        @endif

                        <!-- 2. Multilingual Names -->
                        <div class="space-y-5 px-1">
                            <!-- zh_TW -->
                            <div class="space-y-2">
                                <label
                                    class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    {{ __('Category Name (zh_TW)') }} <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="names[zh_TW]" x-model="categoryFormFields.names.zh_TW"
                                    class="luxury-input w-full focus:ring-emerald-500/20 focus:border-emerald-500"
                                    placeholder="{{ __('e.g., Beverage') }}" required>
                            </div>

                            <!-- en -->
                            <div class="space-y-2">
                                <label
                                    class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    {{ __('Category Name (en)') }}
                                </label>
                                <input type="text" name="names[en]" x-model="categoryFormFields.names.en"
                                    class="luxury-input w-full" placeholder="{{ __('e.g., Drinks') }}">
                            </div>

                            <!-- ja -->
                            <div class="space-y-2">
                                <label
                                    class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    {{ __('Category Name (ja)') }}
                                </label>
                                <input type="text" name="names[ja]" x-model="categoryFormFields.names.ja"
                                    class="luxury-input w-full" placeholder="{{ __('e.g., お飲み物') }}">
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-4 mt-12 pt-6 border-t border-slate-100 dark:border-white/5">
                        <button type="button" @click="isCategoryModalOpen = false"
                            class="px-6 py-2.5 text-sm font-black text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 uppercase tracking-widest transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn-luxury-primary px-10 py-3 shadow-lg"
                            :class="categoryModalMode === 'create' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20' : 'bg-cyan-600 hover:bg-cyan-700 shadow-cyan-500/20'">
                            <span
                                x-text="categoryModalMode === 'create' ? '{{ __('Create') }}' : '{{ __('Save Changes') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Product Detail Slide-over -->
    <div x-show="isDetailOpen" class="fixed inset-0 z-[100] overflow-hidden" x-cloak role="dialog" aria-modal="true">

        <!-- Backdrop -->
        <div x-show="isDetailOpen" x-transition:enter="ease-in-out duration-500" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-500"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="isDetailOpen = false">
        </div>

        <div class="fixed inset-y-0 right-0 max-w-full flex">
            <!-- Panel -->
            <div x-show="isDetailOpen"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="relative w-screen max-w-md" @click.stop>

                <div
                    class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-white/10 overflow-y-auto luxury-scrollbar">
                    <div
                        class="px-6 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md z-20">
                        <div>
                            <h2 class="text-xl font-black text-slate-800 dark:text-white">{{ __('Product Details') }}
                            </h2>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1"
                                x-text="(selectedProduct?.localized_name || selectedProduct?.name) + ' (' + getCategoryName(selectedProduct?.category_id) + ')'">
                            </p>
                        </div>
                        <button @click="isDetailOpen = false"
                            class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-8 space-y-8 custom-scrollbar">
                        <!-- Header Status Info (Minimized) -->
                        <div class="flex items-center gap-3 animate-luxury-in">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest border transition-all duration-300"
                                :class="selectedProduct?.is_active ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 shadow-sm shadow-emerald-500/10' : 'bg-slate-500/10 text-slate-500 border-slate-500/20'">
                                <span
                                    x-text="selectedProduct?.is_active ? '{{ __('Active') }}' : '{{ __('Disabled') }}'"></span>
                            </span>
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-300"
                                x-text="'ID: #' + (selectedProduct?.id || '-')"></span>
                        </div>

                        <div class="space-y-8">
                            <!-- Image Section (Square) -->
                            <template x-if="selectedProduct?.image_url">
                                <section class="animate-luxury-in">
                                    <h3 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.3em] mb-4">
                                        {{ __('Product Image') }}</h3>
                                    <div @click="isImageZoomed = true"
                                        class="max-w-xs mx-auto aspect-square rounded-[2rem] bg-slate-50 dark:bg-slate-800 overflow-hidden border border-slate-100 dark:border-white/5 shadow-lg group relative cursor-zoom-in">
                                        <img :src="selectedProduct.image_url"
                                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-1000">
                                        <div
                                            class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                            <div
                                                class="p-3 rounded-full bg-white/20 backdrop-blur-md text-white border border-white/30 scale-50 group-hover:scale-100 transition-all duration-500 shadow-2xl">
                                                <svg class="w-6 h-6 shadow-glow" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </template>

                            <section class="space-y-4 animate-luxury-in" style="animation-delay: 100ms">
                                <h3 class="text-xs font-black text-cyan-500 uppercase tracking-[0.3em]">{{ __('Identity and Codes') }}</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div
                                        class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 group hover:border-cyan-500/30 transition-colors">
                                        <span
                                            class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Barcode') }}</span>
                                        <div class="text-[15px] font-mono font-bold text-slate-700 dark:text-slate-200"
                                            x-text="selectedProduct?.barcode || '-'"></div>
                                    </div>
                                    <div
                                        class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 group hover:border-cyan-500/30 transition-colors">
                                        <span
                                            class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Specification') }}</span>
                                        <div class="text-[15px] font-bold text-slate-700 dark:text-slate-200"
                                            x-text="selectedProduct?.spec || '-'"></div>
                                    </div>
                                    <template x-if="isProductFeatureEnabled('enable_material_code') && selectedProduct?.metadata?.material_code">
                                        <div
                                            class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 group hover:border-cyan-500/30 transition-colors">
                                            <span
                                                class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Material Code') }}</span>
                                            <div class="text-[15px] font-mono font-bold text-slate-700 dark:text-slate-200"
                                                x-text="selectedProduct?.metadata?.material_code"></div>
                                        </div>
                                    </template>
                                    <div
                                        class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 group hover:border-cyan-500/30 transition-colors">
                                        <span
                                            class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Manufacturer') }}</span>
                                        <div class="text-[15px] font-bold text-slate-700 dark:text-slate-200"
                                            x-text="selectedProduct?.manufacturer || '-'"></div>
                                    </div>
                                    <template x-if="selectedProduct?.company?.name">
                                        <div
                                            class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 group hover:border-cyan-500/30 transition-colors">
                                            <span
                                                class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Company') }}</span>
                                            <div class="text-[15px] font-bold text-slate-700 dark:text-slate-200"
                                                x-text="selectedProduct?.company?.name"></div>
                                        </div>
                                    </template>
                                </div>
                            </section>

                            <!-- Pricing Section -->
                            <section class="space-y-4 animate-luxury-in" style="animation-delay: 200ms">
                                <h3 class="text-xs font-black text-emerald-500 uppercase tracking-[0.3em]">{{ __('Pricing Information') }}</h3>
                                <div
                                    class="luxury-card divide-y divide-slate-50 dark:divide-white/5 overflow-hidden border border-slate-100 dark:border-white/5 shadow-sm">
                                    <div
                                        class="p-5 flex items-center justify-between group hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors">
                                        <span class="text-[15px] font-bold text-slate-500">{{ __('Sale Price') }}</span>
                                        <span class="text-lg font-black text-slate-800 dark:text-white">$<span
                                                x-text="formatNumber(selectedProduct?.price)"></span></span>
                                    </div>
                                    <div
                                        class="p-5 flex items-center justify-between group hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors">
                                        <span class="text-[15px] font-bold text-slate-500">{{ __('Member Price') }}</span>
                                        <span class="text-lg font-black text-emerald-500">$<span
                                                x-text="formatNumber(selectedProduct?.member_price)"></span></span>
                                    </div>
                                    <div
                                        class="p-5 flex items-center justify-between group hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors">
                                        <span class="text-[15px] font-bold text-slate-400">{{ __('Cost') }}</span>
                                        <span class="text-sm font-bold text-slate-500 tracking-tight">$<span
                                                x-text="formatNumber(selectedProduct?.cost)"></span></span>
                                    </div>
                                </div>
                            </section>

                            <!-- Storage & Limits -->
                            <section class="space-y-4 animate-luxury-in" style="animation-delay: 300ms">
                                <h3 class="text-xs font-black text-indigo-500 uppercase tracking-[0.3em]">{{ __('Channel Limits Config') }}</h3>
                                <div
                                    class="luxury-card p-6 border border-slate-100 dark:border-white/5 space-y-6 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-1">
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Track Limit') }}</p>
                                            <p class="text-3xl font-black text-indigo-500 tracking-tighter"
                                                x-text="selectedProduct?.track_limit || '0'"></p>
                                        </div>
                                        <div class="h-10 w-px bg-slate-100 dark:bg-white/10"></div>
                                        <div class="space-y-1 text-right">
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ __('Spring Limit') }}</p>
                                            <p class="text-3xl font-black text-amber-500 tracking-tighter"
                                                x-text="selectedProduct?.spring_limit || '0'"></p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Loyalty Points -->
                            <section x-show="isProductFeatureEnabled('enable_points')" class="space-y-4 animate-luxury-in" style="animation-delay: 400ms">
                                <h3 class="text-xs font-black text-rose-500 uppercase tracking-[0.3em]">{{ __('Marketing and Loyalty') }}</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div
                                        class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                        <span
                                            class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-1">{{ __('Full Points') }}</span>
                                        <div class="text-lg font-black text-rose-500 font-mono"
                                            x-text="selectedProduct?.metadata?.points_full || '0'"></div>
                                    </div>
                                    <div
                                        class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                        <span
                                            class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-1">{{ __('Half Points') }}</span>
                                        <div class="text-lg font-black text-indigo-500 font-mono"
                                            x-text="selectedProduct?.metadata?.points_half || '0'"></div>
                                    </div>
                                    <div
                                        class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                        <span
                                            class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-1">{{ __('Half Points Amount') }}</span>
                                        <div class="text-lg font-black text-emerald-500 font-mono"
                                            x-text="selectedProduct?.metadata?.points_half_amount || '0'"></div>
                                    </div>
                                </div>
                            </section>
                        </div>


                    </div>

                    <div
                        class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <button @click="isDetailOpen = false" class="w-full btn-luxury-ghost">{{ __('Close Panel') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <x-delete-confirm-modal :title="__('Confirm Deletion')"
            :message="__('Are you sure you want to delete this product or category? This action cannot be undone.')" />
        <x-status-confirm-modal :title="__('Confirm Status Change')"
            :message="__('Are you sure you want to change the status of this item? This will affect its visibility on vending machines.')" />

        <form x-ref="statusToggleForm" :action="toggleFormAction" method="POST" class="hidden">
            @csrf
            @method('PATCH')
        </form>

        <!-- Image Zoom Modal -->
        <div x-show="isImageZoomed" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-xl"
            @keydown.escape.window="isImageZoomed = false" x-cloak>

            <button @click="isImageZoomed = false"
                class="absolute top-6 right-6 p-3 rounded-full bg-white/10 text-white hover:bg-white/20 transition-all border border-white/10 active:scale-95">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="relative max-w-5xl w-full aspect-square md:aspect-auto md:max-h-[90vh] flex items-center justify-center"
                @click.away="isImageZoomed = false">
                <img :src="selectedProduct?.image_url"
                    class="max-w-full max-h-full rounded-[2.5rem] shadow-2xl border border-white/10 animate-luxury-in">

                <div class="absolute bottom-[-4rem] left-1/2 -translate-x-1/2 text-white/60 text-sm font-bold tracking-widest uppercase animate-luxury-in"
                    style="animation-delay: 200ms">
                    <span x-text="selectedProduct?.localized_name || selectedProduct?.name"></span>
                </div>
            </div>
        </div>

    <!-- Log Detail Side Panel -->
    <div
        class="fixed inset-0 z-[110] overflow-hidden"
        x-show="isLogPanelOpen"
        x-cloak
        @keydown.window.escape="isLogPanelOpen = false"
    >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
             x-show="isLogPanelOpen"
             x-transition:enter="ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="isLogPanelOpen = false"
        ></div>

        <!-- Panel Wrapper -->
        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div
                class="pointer-events-auto w-screen max-w-md"
                x-show="isLogPanelOpen"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                <div class="flex h-full flex-col overflow-y-auto bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-100 dark:border-slate-800">

                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md z-20">
                        <div>
                            <h2 class="text-xl font-black text-slate-800 dark:text-white">{{ __('Log Details') }}</h2>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1"
                                x-text="(selectedLog?.module || '') + ' / ' + (selectedLog?.action || '')">
                            </p>
                        </div>
                        <button @click="isLogPanelOpen = false"
                            class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 p-6 space-y-6">

                        <!-- Meta Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/80">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Time') }}</span>
                                <div class="text-sm font-black text-slate-700 dark:text-slate-200" x-text="selectedLog?.created_at"></div>
                            </div>
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/80">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Operator') }}</span>
                                <div class="text-sm font-black text-slate-700 dark:text-slate-200" x-text="selectedLog?.user?.name || 'System'"></div>
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/80">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">{{ __('Target') }}</span>
                            <div class="text-sm font-black text-slate-800 dark:text-slate-100" x-text="selectedLog?.translated_note"></div>
                        </div>

                        <!-- Data Changes -->
                        <div class="space-y-4">
                            <h3 class="text-xs font-black text-cyan-500 uppercase tracking-[0.3em]">{{ __('Data Changes') }}</h3>

                            <div class="space-y-3">
                                <template x-for="change in getLogChanges(selectedLog)" :key="change.key">
                                    <div class="p-5 rounded-2xl border border-slate-100 dark:border-white/5 bg-white dark:bg-slate-800/30">
                                        <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3" x-text="change.label"></div>
                                        <div class="space-y-2">
                                            <div>
                                                <span class="text-[10px] font-bold text-rose-400 uppercase tracking-tighter">{{ __('Old Value') }}</span>
                                                <div class="mt-1 text-xs font-bold text-rose-500 bg-rose-500/5 p-3 rounded-xl border border-rose-500/10 line-through decoration-rose-500/30" x-text="change.old"></div>
                                            </div>
                                            <div>
                                                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-tighter">{{ __('New Value') }}</span>
                                                <div class="mt-1 text-xs font-bold text-emerald-500 bg-emerald-500/5 p-3 rounded-xl border border-emerald-500/10" x-text="change.new"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="getLogChanges(selectedLog).length === 0">
                                    <div class="p-12 text-center">
                                        <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800/50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-slate-100 dark:border-slate-800">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-400">{{ __('No changes detected') }}</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <button @click="isLogPanelOpen = false" class="w-full btn-luxury-ghost">{{ __('Close') }}</button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal (Mirroring Remote Control) -->
    <template x-teleport="body">
        <div x-show="confirmModal.show" class="fixed inset-0 z-[120] overflow-y-auto" x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <!-- Background Backdrop -->
                <div x-show="confirmModal.show" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="confirmModal.show = false"></div>

                <!-- Modal Content -->
                <div x-show="confirmModal.show" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-[2.5rem] bg-white dark:bg-slate-900 p-8 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 dark:border-slate-800">

                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 border border-amber-500/20">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase">{{ __('Command Confirmation') }}</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ __('Please confirm the details below') }}</p>
                        </div>
                    </div>

                    <div
                        class="space-y-4 bg-slate-50 dark:bg-slate-950/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800/50 mb-8">
                        <div class="flex justify-between items-center px-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Command Type') }}</span>
                            <span class="text-sm font-black text-slate-800 dark:text-slate-200"
                                x-text="getCommandName(confirmModal.type)"></span>
                        </div>
                        <div class="flex justify-between items-center px-1 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                            <span class="text-[10px] font-black text-cyan-500 uppercase tracking-widest">{{ __('Target') }}</span>
                            <span class="text-sm font-black text-slate-800 dark:text-slate-200" 
                                x-text="confirmModal.company_id ? (companies.find(c => c.id == confirmModal.company_id)?.name || '{{ __('All Machines') }}') : '{{ __('All Machines') }}'"></span>
                        </div>
                        
                        @if(auth()->user()->isSystemAdmin())
                        <div class="space-y-2 px-1 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                            <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] ml-1">
                                {{ __('Select Target Company') }}
                            </label>
                            <div id="sync_company_select_wrapper" class="relative">
                                <!-- Searchable Select will be injected here -->
                            </div>
                        </div>
                        @endif

                        <div class="space-y-2 px-1 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                            <label
                                class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] ml-1">{{ __('Operation Note') }}</label>
                            <textarea x-model="confirmModal.note"
                                class="luxury-input w-full min-h-[100px] text-sm py-3 px-4 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus:border-cyan-500/50"
                                placeholder="{{ __('Reason for this sync...') }}"></textarea>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="confirmModal.show = false"
                            class="flex-1 px-6 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="executeSyncToAll()" :disabled="loading"
                            class="flex-1 px-6 py-4 rounded-2xl bg-cyan-600 text-white text-xs font-black uppercase tracking-widest hover:bg-cyan-500 shadow-lg shadow-cyan-500/20 active:scale-[0.98] transition-all disabled:opacity-50">
                            <span x-show="!loading">{{ __('Execute') }}</span>
                            <span x-show="loading" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Processing...') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productManager', () => ({
            isDeleteConfirmOpen: false,
            isDetailOpen: false,
            isImageZoomed: false,
            isStatusConfirmOpen: false,
            isCategoryModalOpen: false,
            isImportModalOpen: false,
            confirmModal: {
                show: false,
                type: 'sync_products',
                note: '',
                company_id: ''
            },
            activeTab: '{{ request("tab", "products") }}',
            tabLoading: null,
            loading: false,
            categoryModalMode: 'create',
            categoryFormAction: '',
            deleteFormAction: '',
            toggleFormAction: '',
            selectedProduct: null,
            selectedLog: null,
            isLogPanelOpen: false,
            expandedLogs: [],
            categories: [],
            companies: [],
            companySettings: {},
            translations: {},
            categoryFormFields: {
                names: { zh_TW: '', en: '', ja: '' },
                company_id: ''
            },
            importFormFields: { company_id: '{{ auth()->user()->company_id ?? "" }}', file: null, images: null },

            init() {
                this.categories = JSON.parse(this.$el.dataset.categories || '[]');
                this.companySettings = JSON.parse(this.$el.dataset.settings || '{}');
                this.companies = @js($companies);
                this.translations = @js([
                    'Sync Products' => __('Sync Products'),
                    'Connection error' => __('Connection error'),
                    'Failed to send command' => __('Failed to send command'),
                    'Select Target Company' => __('Select Target Company'),
                    'Search Company...' => __('Search Company...'),
                    'Field' => __('Field'),
                    'Old' => __('Old'),
                    'New' => __('New'),
                    'No changes detected' => __('No changes detected'),
                    'Close' => __('Close'),
                    'Log Details' => __('Log Details'),
                ]);

                this.showPendingImportToast();

                // Initial binding
                this.bindPaginationLinks('#tab-products-container', 'products');
                this.bindPaginationLinks('#tab-categories-container', 'categories');
                this.bindPaginationLinks('#tab-logs-container', 'logs');

                if (this.activeTab === 'logs') {
                    this.fetchTabData('logs');
                }

                this.$watch('confirmModal.show', (value) => {
                    if (value && document.getElementById('sync_company_select_wrapper')) {
                        this.$nextTick(() => {
                            this.updateSyncCompanySelect();
                        });
                    }
                });

                // Watch for category modal updates
                this.$watch('isCategoryModalOpen', (value) => {
                    if (value && document.getElementById('category_company_select_wrapper')) {
                        this.$nextTick(() => {
                            this.updateCategoryCompanySelect();
                        });
                    }
                });

                // Sync top loading bar (Removed for tab/pagination to reduce visual noise as requested)
                /*
                this.$watch('tabLoading', (val) => {
                    const bar = document.getElementById('top-loading-bar');
                    if (bar) {
                        if (val) bar.classList.add('loading');
                        else bar.classList.remove('loading');
                    }
                });
                */

                // Sync global loading to top bar as well
                this.$watch('loading', (val) => {
                    const bar = document.getElementById('top-loading-bar');
                    if (bar) {
                        if (val) bar.classList.add('loading');
                        else bar.classList.remove('loading');
                    }
                });
                // Sync tab to URL (Clean up parameters from other tabs when switching)
                this.$watch('activeTab', (val) => {
                    const url = new URL(window.location.origin + window.location.pathname);
                    url.searchParams.set('tab', val);
                    window.history.pushState({}, '', url);

                    // Auto fetch logs if empty
                    if (val === 'logs') {
                        const container = document.getElementById('tab-logs-container');
                        if (container && container.innerHTML.trim() === '') {
                            this.fetchTabData('logs');
                        }
                    }
                });
            },

            showPendingImportToast() {
                const payload = sessionStorage.getItem('productImportToast');
                if (!payload) return;

                sessionStorage.removeItem('productImportToast');

                try {
                    const toast = JSON.parse(payload);
                    setTimeout(() => {
                        if (window.showToast) {
                            window.showToast(toast.message, toast.type || 'success');
                        }
                    }, 300);
                } catch (error) {
                    console.error('Failed to parse import toast payload:', error);
                }
            },

            toggleLog(id) {
                const idx = this.expandedLogs.indexOf(id);
                if (idx > -1) {
                    this.expandedLogs.splice(idx, 1);
                } else {
                    this.expandedLogs.push(id);
                }
            },

            viewLogDetails(log) {
                console.log('Viewing log details:', log);
                this.selectedLog = log;
                this.isLogPanelOpen = true;
            },

            getLogChanges(log) {
                if (!log) return [];
                const oldVals = log.old_values || {};
                const newVals = log.new_values || {};
                const changes = [];
                const keys = new Set([...Object.keys(oldVals), ...Object.keys(newVals)]);
                
                // 排除不需顯示的欄位
                const excludeKeys = ['id', 'created_at', 'updated_at', 'deleted_at', 'name_dictionary_key', 'company_id', 'user_id', 'image_url'];
                
                keys.forEach(key => {
                    if (excludeKeys.includes(key)) return;
                    
                    const ov = oldVals[key];
                    const nv = newVals[key];
                    
                    if (JSON.stringify(ov) !== JSON.stringify(nv)) {
                        changes.push({
                            key: key,
                            label: this.getFieldLabel(key),
                            old: this.formatValue(ov),
                            new: this.formatValue(nv)
                        });
                    }
                });
                
                return changes;
            },

            getFieldLabel(key) {
                const labels = {
                    'name': '{{ __("Name") }}',
                    'price': '{{ __("Price") }}',
                    'cost': '{{ __("Cost") }}',
                    'member_price': '{{ __("Member Price") }}',
                    'barcode': '{{ __("Barcode") }}',
                    'spec': '{{ __("Spec") }}',
                    'manufacturer': '{{ __("Manufacturer") }}',
                    'track_limit': '{{ __("Track Limit") }}',
                    'spring_limit': '{{ __("Spring Limit") }}',
                    'is_active': '{{ __("Is Active") }}',
                    'category_id': '{{ __("Category") }}',
                    'metadata': '{{ __("Metadata") }}'
                };
                return labels[key] || key;
            },

            formatValue(val) {
                if (val === null || val === undefined) return '-';
                if (typeof val === 'boolean') return val ? '{{ __("Yes") }}' : '{{ __("No") }}';
                if (typeof val === 'object') return JSON.stringify(val);
                return val;
            },

            async fetchTabData(tab, url = null) {
                this.tabLoading = tab;

                const container = document.getElementById('tab-' + tab + '-container');

                // If no URL is provided, build one from the current tab's form
                if (!url) {
                    const form = container?.querySelector('form');
                    // Start with a clean set of parameters, only keeping the current tab
                    let params = new URLSearchParams();
                    params.set('tab', tab);
                    params.set('_ajax', '1');

                    if (form) {
                        const formData = new FormData(form);
                        formData.forEach((value, key) => {
                            if (value.trim() !== '') {
                                params.set(key, value);
                            }
                        });
                    }

                    url = `${window.location.pathname}?${params.toString()}`;
                } else {
                    // Ensure URL has tab and _ajax params
                    const urlObj = new URL(url, window.location.origin);
                    if (urlObj.host === window.location.host) {
                        urlObj.protocol = window.location.protocol;
                    }
                    urlObj.searchParams.set('tab', tab);
                    urlObj.searchParams.set('_ajax', '1');
                    url = urlObj.toString();
                }

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();

                    if (data.success) {
                        if (container) {
                            container.innerHTML = data.html;

                            // Re-init Alpine components in the dynamic content
                            if (window.Alpine) {
                                window.Alpine.initTree(container);
                            }

                            // Re-bind pagination events
                            this.bindPaginationLinks('#tab-' + tab + '-container', tab);
                        }

                        // Update browser URL (without _ajax)
                        const historyUrl = new URL(url, window.location.origin);
                        historyUrl.searchParams.delete('_ajax');
                        window.history.pushState({}, '', historyUrl);
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    if (window.showToast) window.showToast('{{ __("Failed to load data") }}', 'error');
                } finally {
                    this.tabLoading = null;
                }
            },

            handleFilterSubmit(tab) {
                // When submitting the filter form, we want to reset to page 1
                const container = document.getElementById('tab-' + tab + '-container');
                const form = container?.querySelector('form');
                let params = new URLSearchParams();

                if (form) {
                    const formData = new FormData(form);
                    for (let [key, value] of formData.entries()) {
                        if (value) params.set(key, value);
                    }
                }

                // Ensure tab and ajax markers are present
                params.set('tab', tab);
                params.set('_ajax', '1');

                // Explicitly remove page parameters to reset to page 1
                params.delete('product_page');
                params.delete('category_page');
                params.delete('log_page');

                const url = window.location.pathname + '?' + params.toString();
                this.fetchTabData(tab, url);
            },

            bindPaginationLinks(containerSelector, tab) {
                this.$nextTick(() => {
                    const container = document.querySelector(containerSelector);
                    if (!container) return;

                    // Re-init Preline components (Selects etc.)
                    if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                        window.HSStaticMethods.autoInit();
                    }

                    if (container._ajaxNavigateHandler) {
                        container.removeEventListener('ajax:navigate', container._ajaxNavigateHandler);
                    }

                    container._ajaxNavigateHandler = (e) => {
                        e.preventDefault();
                        const url = e.detail?.url;
                        if (url) {
                            this.fetchTabData(tab, url);
                        }
                    };

                    container.addEventListener('ajax:navigate', container._ajaxNavigateHandler);

                    // 1. Intercept Standard Links
                    container.querySelectorAll('nav a, .pagination a').forEach(link => {
                        // Prevent multiple bindings
                        if (link.dataset.ajaxBound) return;
                        link.dataset.ajaxBound = 'true';

                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            this.fetchTabData(tab, link.href);
                        });
                    });

                    // 2. Intercept Dropdown Changes (Per Page / Page Jump)
                    container.querySelectorAll('select[onchange]').forEach(sel => {
                        const originalOnchange = sel.getAttribute('onchange');
                        if (originalOnchange) {
                            sel.removeAttribute('onchange'); // Prevent default behavior

                            sel.addEventListener('change', (e) => {
                                let newUrl;
                                // Simple page jump: value is usually the URL
                                if (sel.value.includes('?')) {
                                    newUrl = sel.value;
                                } else {
                                    // Per page limit change: needs to build URL
                                    const params = new URLSearchParams(window.location.search);
                                    params.set('per_page', sel.value);
                                    const pageKey = tab === 'products' ? 'product_page' : (tab === 'categories' ? 'category_page' : 'log_page');
                                    params.delete(pageKey); // Reset to page 1
                                    newUrl = window.location.pathname + '?' + params.toString();
                                }
                                this.fetchTabData(tab, newUrl);
                            });
                        }
                    });
                });
            },

            updateCategoryCompanySelect() {
                const wrapper = document.getElementById('category_company_select_wrapper');
                if (!wrapper) return;
                wrapper.innerHTML = '';

                const selectEl = document.createElement('select');
                selectEl.name = 'company_id';
                const uniqueId = 'cat-company-' + Date.now();
                selectEl.id = uniqueId;
                selectEl.className = 'hidden';

                const config = {
                    "placeholder": "{{ __('Select Company (Default: System)') }}",
                    "hasSearch": true,
                    "searchPlaceholder": "{{ __('Search Company Title...') }}",
                    "isHidePlaceholder": false,
                    "searchClasses": "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
                    "searchWrapperClasses": "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                    "toggleClasses": "hs-select-toggle luxury-select-toggle",
                    "dropdownClasses": "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[100] animate-luxury-in",
                    "optionClasses": "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
                    "optionTemplate": '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>'
                };

                selectEl.setAttribute('data-hs-select', JSON.stringify(config));

                const defaultOpt = document.createElement('option');
                defaultOpt.value = "";
                defaultOpt.textContent = "{{ __('System Default (Common)') }}";
                defaultOpt.setAttribute('data-title', defaultOpt.textContent);
                if (!this.categoryFormFields.company_id) defaultOpt.selected = true;
                selectEl.appendChild(defaultOpt);

                this.companies.forEach(company => {
                    const opt = document.createElement('option');
                    opt.value = company.id;
                    opt.textContent = company.name;
                    opt.setAttribute('data-title', company.name);
                    if (String(this.categoryFormFields.company_id) === String(company.id)) opt.selected = true;
                    selectEl.appendChild(opt);
                });

                wrapper.appendChild(selectEl);

                selectEl.addEventListener('change', (e) => {
                    this.categoryFormFields.company_id = e.target.value;
                });

                this.$nextTick(() => {
                    if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                        window.HSStaticMethods.autoInit(['select']);
                    }
                });
            },



            viewProductDetail(product) {
                this.selectedProduct = product;
                this.isDetailOpen = true;
            },

            getSelectedProductSettings() {
                return this.selectedProduct?.company?.settings || this.companySettings || {};
            },

            isProductFeatureEnabled(key) {
                return Boolean(this.getSelectedProductSettings()?.[key]);
            },

            openCategoryModal(category = null) {
                if (category) {
                    this.categoryModalMode = 'edit';
                    this.categoryFormAction = `{{ url('admin/data-config/product-categories') }}/${category.id}`;
                    this.categoryFormFields.names = { zh_TW: category.name || '', en: category.name || '', ja: category.name || '' };
                    if (category.translations && category.translations.length > 0) {
                        category.translations.forEach(t => {
                            if (this.categoryFormFields.names.hasOwnProperty(t.locale)) {
                                this.categoryFormFields.names[t.locale] = t.value;
                            }
                        });
                    }
                    this.categoryFormFields.company_id = category.company_id || '';
                } else {
                    this.categoryModalMode = 'create';
                    this.categoryFormAction = `{{ route('admin.data-config.product-categories.store') }}`;
                    this.categoryFormFields.names = { zh_TW: '', en: '', ja: '' };
                    this.categoryFormFields.company_id = '';
                }
                this.isCategoryModalOpen = true;
            },

            confirmDelete(action) {
                this.deleteFormAction = action;
                this.isDeleteConfirmOpen = true;
            },

            toggleStatus(actionUrl) {
                this.toggleFormAction = actionUrl;
                this.isStatusConfirmOpen = true;
            },

            async submitImportForm() {
                if (!this.importFormFields.file) return;

                this.loading = true;
                const formData = new FormData();
                formData.append('file', this.importFormFields.file);
                if (this.importFormFields.company_id) {
                    formData.append('company_id', this.importFormFields.company_id);
                }
                if (this.importFormFields.images) {
                    formData.append('images', this.importFormFields.images);
                }

                try {
                    const response = await fetch(`{{ route($baseRoute . '.import') }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.isImportModalOpen = false;
                        this.importFormFields.file = null;
                        this.importFormFields.images = null;
                        const input = document.getElementById('product-import-file');
                        if (input) input.value = '';
                        const imagesInput = document.getElementById('product-import-images');
                        if (imagesInput) imagesInput.value = '';

                        sessionStorage.setItem('productImportToast', JSON.stringify({
                            message: data.message,
                            type: 'success'
                        }));

                        window.location.reload();
                    } else {
                        if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    }
                } catch (error) {
                    console.error('Product import error:', error);
                    if (window.showToast) window.showToast('{{ __("Import failed") }}', 'error');
                } finally {
                    this.loading = false;
                }
            },

            submitConfirmedForm() {
                if (this.isStatusConfirmOpen) {
                    this.isStatusConfirmOpen = false;
                    this.$refs.statusToggleForm.submit();
                }
            },



            getCategoryName(id) {
                const category = this.categories.find(c => c.id == id);
                return category ? (category.localized_name || category.name) : "{{ __('Uncategorized') }}";
            },

            formatNumber(val) {
                if (val === null || val === undefined) return '0';
                return new Intl.NumberFormat().format(val);
            },

            formatDate(dateStr) {
                if (!dateStr) return '-';
                const date = new Date(dateStr);
                return date.toLocaleString();
            },

            syncToAllMachines() {
                this.confirmModal.note = '';
                this.confirmModal.company_id = '{{ auth()->user()->company_id }}';
                this.confirmModal.show = true;
            },

            updateSyncCompanySelect() {
                const wrapper = document.getElementById('sync_company_select_wrapper');
                if (!wrapper) return;
                wrapper.innerHTML = '';

                const selectEl = document.createElement('select');
                selectEl.name = 'sync_company_id';
                const uniqueId = 'sync-company-' + Date.now();
                selectEl.id = uniqueId;
                selectEl.className = 'hidden';

                const config = {
                    "placeholder": this.translations['Select Target Company'],
                    "hasSearch": true,
                    "searchPlaceholder": this.translations['Search Company...'],
                    "isHidePlaceholder": false,
                    "searchClasses": "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
                    "searchWrapperClasses": "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                    "toggleClasses": "hs-select-toggle luxury-select-toggle",
                    "dropdownClasses": "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[100] animate-luxury-in",
                    "optionClasses": "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
                    "optionTemplate": '<div class="flex items-center justify-between w-full"><span data-title></span><span class="hs-select-active-indicator hidden text-cyan-500"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></div>'
                };

                selectEl.setAttribute('data-hs-select', JSON.stringify(config));

                this.companies.forEach((company, index) => {
                    const opt = document.createElement('option');
                    opt.value = company.id;
                    opt.textContent = company.name;
                    opt.setAttribute('data-title', company.name);
                    
                    // 如果目前尚未選擇公司 (例如系統管理員初次點開)，則自動預設選中第一個
                    if (!this.confirmModal.company_id && index === 0) {
                        this.confirmModal.company_id = company.id;
                        opt.selected = true;
                    } else if (String(company.id) === String(this.confirmModal.company_id)) {
                        opt.selected = true;
                    }
                    selectEl.appendChild(opt);
                });

                selectEl.addEventListener('change', (e) => {
                    this.confirmModal.company_id = e.target.value;
                });

                wrapper.appendChild(selectEl);

                // Initialize Preline Select
                this.$nextTick(() => {
                    if (window.HSStaticMethods && window.HSStaticMethods.autoInit) {
                        window.HSStaticMethods.autoInit();
                    }
                });
            },

            getCommandName(type) {
                const names = {
                    'sync_products': this.translations['Sync Products']
                };
                return names[type] || type;
            },

            async executeSyncToAll() {
                this.loading = true;
                this.confirmModal.show = false;
                try {
                    const response = await fetch('{{ route($baseRoute . ".sync-all") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            remark: this.confirmModal.note,
                            company_id: this.confirmModal.company_id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        if (window.showToast) window.showToast(data.message, 'success');
                        this.confirmModal.note = '';
                    } else {
                        if (window.showToast) window.showToast(data.message || this.translations['Failed to send command'], 'error');
                    }
                } catch (error) {
                    console.error('Sync error:', error);
                    if (window.showToast) window.showToast(this.translations['Connection error'], 'error');
                } finally {
                    this.loading = false;
                }
            }
        }));
    });
</script>
@endsection
