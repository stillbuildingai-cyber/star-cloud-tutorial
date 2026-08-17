@extends('layouts.admin')

@section('content')
@php
@endphp



<div class="px-6 py-8" x-data="{ isDeleteConfirmOpen: false, deleteFormAction: '' }">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-gray-900 dark:text-gray-200 text-3xl font-medium">會員等級設定</h3>
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="btn-luxury-primary">
            新增等級
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">名稱</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">年費</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">折扣</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">點數倍率</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">預設</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y border-gray-200 dark:border-gray-700">
                @forelse($tiers as $tier)
                <tr>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $tier->name }}</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $tier->annual_fee == 0 ? '免費' : '$'.number_format($tier->annual_fee) }}</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $tier->discount_rate * 100 }}%</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $tier->point_multiplier }}x</td>
                    <td class="px-6 py-4">
                        @if($tier->is_default)
                        <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 text-xs">預設</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <button type="button" @click="deleteFormAction = '{{ route('admin.membership-tiers.destroy', $tier) }}'; isDeleteConfirmOpen = true" class="text-red-600 hover:text-red-800">刪除</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">尚無資料</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create Modal --}}
<div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto" x-data @keydown.escape.window="document.getElementById('createModal').classList.add('hidden')">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        {{-- 背景遮罩 --}}
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
        
        {{-- Modal 內容 --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-gray-900 dark:text-gray-200 text-lg font-semibold">新增會員等級</h3>
            </div>
            <form action="{{ route('admin.membership-tiers.store') }}" method="POST">
                @csrf
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">名稱</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">年費</label>
                        <input type="number" name="annual_fee" value="0" step="0.01" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">折扣比例 (0.95 = 95折)</label>
                        <input type="number" name="discount_rate" value="1.00" step="0.01" min="0" max="1" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">點數倍率</label>
                        <input type="number" name="point_multiplier" value="1.00" step="0.01" min="0" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_default" value="1" id="is_default" class="mr-2 rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_default" class="text-gray-600 dark:text-gray-400 text-sm">設為預設等級</label>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="btn-luxury-secondary">取消</button>
                    <button type="submit" class="btn-luxury-primary">建立</button>
                </div>
            </form>
        </div>
    </div>
    <x-delete-confirm-modal :message="__('Are you sure you want to delete this tier?')" />
</div>
@endsection
