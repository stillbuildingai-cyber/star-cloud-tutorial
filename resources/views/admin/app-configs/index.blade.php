@extends('layouts.admin')

@section('content')
@php
    $inputText = $isLight ? 'text-gray-900' : 'text-gray-300';
@endphp
<div class="container mx-auto px-6 py-8">
    <h3 class="text-gray-900 dark:text-gray-200 text-3xl font-medium">APP 管理設定</h3>

    <div class="mt-8">
        <form action="{{ route('admin.app-configs.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- UI Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden p-6">
                    <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-200 mb-4">UI 元素設定</h4>
                    <div class="space-y-4">
                        <div>
                            <label for="ui_primary_color" class="block text-sm font-medium text-gray-600 dark:text-gray-400">主色調 (Hex)</label>
                            <input type="text" name="ui_primary_color" id="ui_primary_color" value="{{ $configs['ui']->where('key', 'ui_primary_color')->first()->value ?? '' }}" class="mt-1 block w-full bg-white dark:bg-gray-700 rounded-md shadow-sm py-2 px-3 {{ $inputText }} focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="ui_logo_url" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Logo URL</label>
                            <input type="text" name="ui_logo_url" id="ui_logo_url" value="{{ $configs['ui']->where('key', 'ui_logo_url')->first()->value ?? '' }}" class="mt-1 block w-full bg-white dark:bg-gray-700 rounded-md shadow-sm py-2 px-3 {{ $inputText }} focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- Helper Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden p-6">
                    <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-200 mb-4">小幫手設定</h4>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="hidden" name="helper_enabled" value="0">
                            <input type="checkbox" name="helper_enabled" id="helper_enabled" value="1" {{ ($configs['helper']->where('key', 'helper_enabled')->first()->value ?? '0') == '1' ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="helper_enabled" class="ml-2 block text-sm text-gray-900 dark:text-gray-200">啟用小幫手</label>
                        </div>
                    </div>
                </div>

                <!-- Game Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden p-6">
                    <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-200 mb-4">問卷與互動遊戲</h4>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="hidden" name="game_enabled" value="0">
                            <input type="checkbox" name="game_enabled" id="game_enabled" value="1" {{ ($configs['game']->where('key', 'game_enabled')->first()->value ?? '0') == '1' ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="game_enabled" class="ml-2 block text-sm text-gray-900 dark:text-gray-200">啟用互動遊戲</label>
                        </div>
                        <div>
                            <label for="questionnaire_url" class="block text-sm font-medium text-gray-600 dark:text-gray-400">問卷 URL</label>
                            <input type="text" name="questionnaire_url" id="questionnaire_url" value="{{ $configs['game']->where('key', 'questionnaire_url')->first()->value ?? '' }}" class="mt-1 block w-full bg-white dark:bg-gray-700 rounded-md shadow-sm py-2 px-3 {{ $inputText }} focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    儲存設定
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
