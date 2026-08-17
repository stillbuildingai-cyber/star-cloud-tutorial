@extends('layouts.admin')

@section('content')
<div class="space-y-4 sm:space-y-6">
  <!-- Grid -->
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
    <!-- Card -->
    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
      <div class="p-4 md:p-5">
        <div class="flex items-center gap-x-2">
          <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
            總銷售額
          </p>
          <div class="hs-tooltip">
            <div class="hs-tooltip-toggle">
              <svg class="flex-shrink-0 w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
              <span class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-slate-700" role="tooltip">
                本月累計銷售總額
              </span>
            </div>
          </div>
        </div>

        <div class="mt-1 flex items-center gap-x-2">
          <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
            $72,540
          </h3>
          <span class="flex items-center gap-x-1 text-green-600">
            <svg class="inline-block w-4 h-4 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
            <span class="inline-block text-sm">
              1.7%
            </span>
          </span>
        </div>
      </div>
    </div>
    <!-- End Card -->

    <!-- Card -->
    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
      <div class="p-4 md:p-5">
        <div class="flex items-center gap-x-2">
          <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
            活躍機台
          </p>
        </div>

        <div class="mt-1 flex items-center gap-x-2">
          <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
            124
          </h3>
          <span class="flex items-center gap-x-1 text-red-600">
            <svg class="inline-block w-4 h-4 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
            <span class="inline-block text-sm">
              0.3%
            </span>
          </span>
        </div>
      </div>
    </div>
    <!-- End Card -->

    <!-- Card -->
    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
      <div class="p-4 md:p-5">
        <div class="flex items-center gap-x-2">
          <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
            庫存警告
          </p>
        </div>

        <div class="mt-1 flex items-center gap-x-2">
          <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
            12
          </h3>
        </div>
      </div>
    </div>
    <!-- End Card -->

    <!-- Card -->
    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
      <div class="p-4 md:p-5">
        <div class="flex items-center gap-x-2">
          <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
            本月新增會員
          </p>
        </div>

        <div class="mt-1 flex items-center gap-x-2">
          <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
            28
          </h3>
        </div>
      </div>
    </div>
    <!-- End Card -->
  </div>
  <!-- End Grid -->

  <div class="grid lg:grid-cols-2 gap-4 sm:gap-6">
    <!-- Card -->
    <div class="p-4 md:p-5 min-h-[410px] flex flex-col bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-sm text-gray-500 dark:text-gray-400">
            營收趨勢
          </h2>
          <p class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
             $123,450
          </p>
        </div>
        
        <div>
          <span class="py-[5px] px-1.5 inline-flex items-center gap-x-1 text-xs font-medium rounded-md bg-teal-100 text-teal-800 dark:bg-teal-500/10 dark:text-teal-500">
            <svg class="inline-block w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
            25%
          </span>
        </div>
      </div>
      <!-- End Header -->

      <div id="hs-multiple-bar-charts"></div>
    </div>
    <!-- End Card -->

    <!-- Card -->
    <div class="p-4 md:p-5 min-h-[410px] flex flex-col bg-white border shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-sm text-gray-500 dark:text-gray-400">
            訪客分析
          </h2>
          <p class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-gray-200">
            92,913
          </p>
        </div>

        <div>
           <span class="py-[5px] px-1.5 inline-flex items-center gap-x-1 text-xs font-medium rounded-md bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-500">
            <svg class="inline-block w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
            11%
          </span>
        </div>
      </div>
      <!-- End Header -->

      <div id="hs-single-area-chart"></div>
    </div>
    <!-- End Card -->
  </div>

  <!-- Skeleton Example (Option C) -->
  <div class="luxury-card p-8 animate-fade-up">
    <div class="flex items-center justify-between mb-10">
      <div>
        <div class="h-4 w-32 skeleton mb-2"></div>
        <div class="h-7 w-48 skeleton"></div>
      </div>
      <div class="h-10 w-24 skeleton"></div>
    </div>
    <div class="space-y-4">
      <div class="h-4 w-full skeleton"></div>
      <div class="h-4 w-11/12 skeleton"></div>
      <div class="h-4 w-4/5 skeleton"></div>
    </div>
    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex gap-x-3">
      <div class="h-10 w-28 skeleton"></div>
      <div class="h-10 w-28 skeleton"></div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
    window.addEventListener('load', () => {
        // Here you would initialize charts using ApexCharts or similar, 
        // as Preline examples often use ApexCharts.
        // For now, placeholders are sufficient.
    });
</script>
@endsection
