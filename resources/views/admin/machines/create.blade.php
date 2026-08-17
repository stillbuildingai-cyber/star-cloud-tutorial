@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-6 py-8">
    <h3 class="text-gray-900 dark:text-gray-300 text-3xl font-medium">{{ __('Create Machine') }}</h3>

    <div class="mt-8">
        <form action="{{ route('admin.machines.store') }}" method="POST"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Machine Name') }}</label>
                <input type="text" name="name" id="name"
                    class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    required>
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{
                    __('Location') }}</label>
                <input type="text" name="location" id="location"
                    class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('location') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Status')
                    }}</label>
                <select name="status" id="status"
                    class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="offline">{{ __('Offline') }}</option>
                    <option value="online">{{ __('Connecting...') }}</option>
                    <option value="error">{{ __('Error') }}</option>
                </select>
                @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="temperature" class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{
                    __('Temperature') }} (°C)</label>
                <input type="number" step="0.1" name="temperature" id="temperature"
                    class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('temperature') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="firmware_version" class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{
                    __('Firmware Version') }}</label>
                <input type="text" name="firmware_version" id="firmware_version"
                    class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('firmware_version') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end">
                <a href="{{ route('admin.machines.index') }}"
                    class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-800 dark:text-white font-bold py-2 px-4 rounded mr-2">{{
                    __('Cancel') }}</a>
                <button type="submit" class="btn-luxury-primary">{{ __('Create') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection