@extends('layouts.admin')

@section('content')
<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center border border-gray-200 dark:border-gray-700">
        <div class="mb-6">
            <svg class="mx-auto h-24 w-24 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $title ?? '功能頁面' }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6 text-lg">{{ $description ?? '此功能正在開發中' }}</p>
        <div class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold">
            🚧 功能開發中
        </div>
        
        @if(isset($features) && count($features) > 0)
        <div class="mt-8 text-left max-w-2xl mx-auto">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">規劃功能：</h3>
            <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                @foreach($features as $feature)
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-blue-500 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $feature }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
