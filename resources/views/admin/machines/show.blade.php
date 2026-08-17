@extends('layouts.admin')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Machine Details') }}: {{ $machine->name }}
        </h2>
        <a href="{{ route('admin.machines.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to List') }}</a>
    </div>
@endsection

@section('content')
<div class="py-12">
    <div class="sm:px-6 lg:px-8 space-y-6">
        <!-- 基本資訊卡片 -->
        <div class="bg-white shadow sm:rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">{{ __('Basic Information') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs text-gray-500 uppercase">{{ __('Status') }}</p>
                    <p class="text-lg font-bold {{ $machine->status === 'online' ? 'text-green-600' : ($machine->status === 'error' ? 'text-red-600' : 'text-gray-600') }}">
                        {{ __($machine->status === 'online' ? 'Online' : ($machine->status === 'error' ? 'Error' : 'Offline')) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">{{ __('Location') }}</p>
                    <p class="text-sm">{{ $machine->location }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">{{ __('Last Heartbeat') }}</p>
                    <p class="text-sm">{{ $machine->last_heartbeat_at ?? __('N/A') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">{{ __('Current Page') }}</p>
                    <p class="text-sm font-bold shadow-sm inline-block px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        {{ $machine->current_page_label ?: '-' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- 日誌顯示區 -->
        <div class="bg-gray-900 shadow sm:rounded-lg overflow-hidden border border-gray-700">
            <div class="p-4 bg-gray-800 border-b border-gray-700 flex justify-between items-center">
                <h3 class="text-md font-medium text-gray-200">{{ __('Real-time Operation Logs (Last 50)') }}</h3>
                <span class="text-xs text-gray-400">{{ __('All Times System Timezone') }}</span>
            </div>
            <div class="p-0 max-h-[600px] overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-800 font-mono text-xs">
                    <thead class="bg-gray-800 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-500">{{ __('Time') }}</th>
                            <th class="px-4 py-2 text-left text-gray-500">{{ __('Level') }}</th>
                            <th class="px-4 py-2 text-left text-gray-500">{{ __('Message') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse ($machine->logs as $log)
                        <tr class="hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-400 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $levelClasses = [
                                        'info' => 'text-blue-400',
                                        'warning' => 'text-yellow-400',
                                        'error' => 'text-red-400 font-bold',
                                    ];
                                @endphp
                                <span class="{{ $levelClasses[$log->level] ?? 'text-gray-300' }}">
                                    [{{ strtoupper($log->level) }}]
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-200">
                                {{ $log->message }}
                                @if($log->context)
                                    <div class="text-[10px] text-gray-500 mt-1">
                                        {{ json_encode($log->context) }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-500 italic">{{ __('No logs found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
