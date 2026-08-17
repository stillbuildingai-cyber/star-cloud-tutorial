@extends('layouts.admin')

@section('content')
@php    
    $typeLabels = [
        'points' => '點數',
        'coupon' => '優惠券',
        'product' => '商品',
        'discount' => '折扣',
        'cash' => '現金',
    ];
    
    $triggerLabels = [
        'register' => '註冊',
        'birthday' => '生日',
        'annual' => '年度',
        'upgrade' => '升級',
        'manual' => '手動',
    ];
@endphp



<div class="px-6 py-8" x-data="{ isDeleteConfirmOpen: false, deleteFormAction: '' }">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-gray-900 dark:text-gray-200 text-3xl font-medium">禮品設定</h3>
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="btn-luxury-primary">
            新增禮品
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">名稱</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">類型</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">數值</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">適用等級</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">觸發條件</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">狀態</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y border-gray-200 dark:border-gray-700">
                @forelse($gifts as $gift)
                <tr>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $gift->name }}</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $typeLabels[$gift->type] ?? $gift->type }}</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $gift->value }}</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $gift->tier?->name ?? '全部' }}</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-gray-200">{{ $triggerLabels[$gift->trigger] ?? $gift->trigger }}</td>
                    <td class="px-6 py-4">
                        @if($gift->is_active)
                        <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 text-xs">啟用</span>
                        @else
                        <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-800 text-xs">停用</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <button type="button" @click="deleteFormAction = '{{ route('admin.gift-definitions.destroy', $gift) }}'; isDeleteConfirmOpen = true" class="text-red-600 hover:text-red-800">刪除</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">尚無資料</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create Modal --}}
<div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="document.getElementById('createModal').classList.add('hidden')">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-gray-900 dark:text-gray-200 text-lg font-semibold">新增禮品</h3>
            </div>
            <form action="{{ route('admin.gift-definitions.store') }}" method="POST">
                @csrf
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">名稱</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">類型</label>
                        <select name="type" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                            @foreach($typeLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">數值</label>
                        <input type="number" name="value" value="0" step="0.01" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">適用等級</label>
                        <select name="tier_id" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                            <option value="">全部</option>
                            @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">觸發條件</label>
                        <select name="trigger" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                            @foreach($triggerLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-600 dark:text-gray-400 text-sm block mb-1">有效天數</label>
                        <input type="number" name="validity_days" value="30" min="1" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 border rounded-md text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked id="is_active" class="mr-2 rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-gray-600 dark:text-gray-400 text-sm">啟用</label>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="btn-luxury-secondary">取消</button>
                    <button type="submit" class="btn-luxury-primary">建立</button>
                </div>
            </form>
        </div>
    </div>
    <x-delete-confirm-modal :message="__('Are you sure you want to delete this gift?')" />
</div>
@endsection
