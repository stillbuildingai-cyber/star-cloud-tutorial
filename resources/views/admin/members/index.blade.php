@extends('layouts.admin')

@section('content')
@php
@endphp
<div class="px-6 py-8">
    <h3 class="text-gray-900 dark:text-gray-200 text-3xl font-medium">會員列表</h3>

    <div class="mt-8">
        {{-- 搜尋與篩選 (預留空間) --}}
        
        <div class="flex flex-col mt-4">
            <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 dark:border-gray-700">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 text-left text-xs leading-4 font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    UUID
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 text-left text-xs leading-4 font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    姓名
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 text-left text-xs leading-4 font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 text-left text-xs leading-4 font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    手機
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 text-left text-xs leading-4 font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    狀態
                                </th>
                                <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 text-left text-xs leading-4 font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    註冊時間
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                            @forelse ($members as $member)
                            <tr>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                    <div class="text-sm leading-5 font-medium text-gray-600 dark:text-gray-400">{{ $member->uuid }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                    <div class="text-sm leading-5 font-bold text-gray-900 dark:text-gray-200">{{ $member->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                    <div class="text-sm leading-5 text-gray-900 dark:text-gray-200">{{ $member->email ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                    <div class="text-sm leading-5 text-gray-900 dark:text-gray-200">{{ $member->phone ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                    @if($member->is_active)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            啟用
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            停用
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700 text-sm leading-5 text-gray-600 dark:text-gray-400">
                                    {{ $member->created_at->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700 text-center text-gray-600 dark:text-gray-400">
                                    尚無會員資料
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $members->links() }}
        </div>
    </div>
</div>
@endsection
