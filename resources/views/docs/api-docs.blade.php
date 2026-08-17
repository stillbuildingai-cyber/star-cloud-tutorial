<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $docs['title'] ?? 'API Documentation' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

    <!-- CSS (Vite or CDN for layout) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --color-luxury-deep: #f8fafc;
            --color-luxury-card: #ffffff;
            --color-accent: #0891b2;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--color-luxury-deep);
            color: #1e293b;
        }

        .font-display {
            font-family: 'Outfit', sans-serif;
        }

        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            z-index: 50;
        }

        .main-content {
            margin-left: 280px;
            padding: 2.5rem;
            max-width: 1400px;
        }

        .param-table th {
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 14px;
            font-weight: 800;
            color: #64748b;
            background: #f1f5f9;
            border-bottom: 2px solid #e2e8f0;
        }

        .param-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 15px;
        }

        pre {
            background: #1e293b !important;
            color: #e2e8f0 !important;
            border-radius: 12px;
            padding: 1.25rem;
            font-family: 'ui-monospace', monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow-x: auto;
        }

        .luxury-nav-section {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
        }

        .luxury-nav-link {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.5rem;
            color: #475569;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .luxury-nav-link:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        .luxury-nav-link.active {
            color: var(--color-accent);
            background: #ecfeff;
            border-left-color: var(--color-accent);
        }

        .animate-luxury-in {
            animation: luxuryIn 0.5s ease-out forwards;
        }

        @keyframes luxuryIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body x-data="{ 
    currentTab: 'http', 
    activeSection: '{{ $docs['http_apis'][0]['apis'][0]['slug'] ?? '' }}' 
}">

    <!-- Sidebar -->
    <div class="sidebar shadow-sm flex flex-col">
        <div class="p-6 border-b border-slate-100 pb-4">
            <h1 class="font-display text-2xl font-black tracking-tighter text-slate-900 leading-none">
                STAR CLOUD <span class="text-cyan-600">API</span>
            </h1>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1 mb-4">通訊參考手冊 {{ $docs['version'] }}</p>
            
            <!-- Tab Controls -->
            <div class="flex gap-2 bg-slate-100 p-1 rounded-xl">
                <button @click="currentTab = 'http'; activeSection = '{{ $docs['http_apis'][0]['apis'][0]['slug'] ?? '' }}'" 
                        :class="currentTab === 'http' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 py-1.5 text-[11px] font-black uppercase tracking-widest rounded-lg transition-all">
                    HTTP
                </button>
                <button @click="currentTab = 'mqtt'; activeSection = '{{ $docs['mqtt_topics'][0]['topics'][0]['slug'] ?? '' }}'" 
                        :class="currentTab === 'mqtt' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="flex-1 py-1.5 text-[11px] font-black uppercase tracking-widest rounded-lg transition-all">
                    MQTT
                </button>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-2">
            <!-- HTTP Nav -->
            <div x-show="currentTab === 'http'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @foreach($docs['http_apis'] as $category)
                    <div class="luxury-nav-section">{{ $category['name'] }}</div>
                    @foreach($category['apis'] as $api)
                        <a href="#{{ $api['slug'] }}" class="luxury-nav-link"
                            :class="{ 'active': activeSection === '{{ $api['slug'] }}' }"
                            @click="activeSection = '{{ $api['slug'] }}'">
                            <div class="flex items-center gap-2 overflow-hidden w-full">
                                <span class="flex-shrink-0 text-[10px] w-10 text-center font-black px-1 py-0.5 rounded uppercase tracking-tighter {{ $api['method'] === 'GET' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : ($api['method'] === 'POST' ? 'bg-cyan-50 text-cyan-600 border border-cyan-200' : 'bg-amber-50 text-amber-600 border border-amber-200') }}">
                                    {{ $api['method'] }}
                                </span>
                                <span class="truncate">{{ $api['name'] }}</span>
                            </div>
                        </a>
                    @endforeach
                @endforeach
            </div>

            <!-- MQTT Nav -->
            <div x-show="currentTab === 'mqtt'" style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @foreach($docs['mqtt_topics'] as $category)
                    <div class="luxury-nav-section">{{ $category['name'] }}</div>
                    @foreach($category['topics'] as $topic)
                        <a href="#{{ $topic['slug'] }}" class="luxury-nav-link"
                            :class="{ 'active': activeSection === '{{ $topic['slug'] }}' }"
                            @click="activeSection = '{{ $topic['slug'] }}'">
                            <div class="flex items-center gap-2 overflow-hidden w-full">
                                <span class="flex-shrink-0 text-[10px] w-10 text-center font-black px-1 py-0.5 rounded uppercase tracking-tighter {{ $topic['action'] === 'PUB' ? 'bg-violet-50 text-violet-600 border border-violet-200' : 'bg-indigo-50 text-indigo-600 border border-indigo-200' }}">
                                    {{ $topic['action'] }}
                                </span>
                                <span class="truncate">{{ $topic['name'] }}</span>
                            </div>
                        </a>
                    @endforeach
                @endforeach
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content pb-24">
        <!-- Title Card -->
        <div class="mb-10 p-8 bg-white rounded-3xl border border-slate-100 shadow-sm">
            <h2 class="font-display text-4xl font-black tracking-tight text-slate-900 mb-4">{{ $docs['title'] }}</h2>
            <p class="text-slate-500 leading-relaxed text-lg max-w-3xl font-medium">
                {{ $docs['description'] }}
            </p>
        </div>

        <!-- HTTP Content -->
        <div x-show="currentTab === 'http'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
            @foreach($docs['http_apis'] as $category)
                @foreach($category['apis'] as $api)
                    <div id="{{ $api['slug'] }}" class="mb-16 animate-luxury-in scroll-mt-10" x-intersect.margin.-200px="activeSection = '{{ $api['slug'] }}'">
                        
                        <div class="flex items-center gap-4 mb-6">
                            <span class="px-3 py-1 text-sm font-black rounded-lg uppercase tracking-widest {{ $api['method'] === 'GET' ? 'bg-emerald-100 text-emerald-700' : ($api['method'] === 'POST' ? 'bg-cyan-100 text-cyan-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ $api['method'] }}
                            </span>
                            <h3 class="font-display text-3xl font-black text-slate-900 tracking-tight">{{ $api['name'] }}</h3>
                        </div>
                        <p class="text-slate-600 mb-8 text-lg font-medium">{!! nl2br(e($api['description'])) !!}</p>

                        <!-- Headers & URL -->
                        <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200">
                            <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 bg-cyan-500 text-white text-xs font-black rounded-lg uppercase letter-spacing-widest">{{ $api['method'] }}</span>
                                    <code class="text-slate-300 font-mono text-base break-all">{{ config('app.url') }}{{ $api['path'] }}</code>
                                </div>
                                <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest hidden sm:block">Endpoint URL</span>
                            </div>
                            @if(isset($api['headers']) && count($api['headers']) > 0)
                            <div class="bg-slate-50 p-6 border-t border-slate-200">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">請求標頭 (Headers)</h4>
                                <div class="space-y-4">
                                    @foreach($api['headers'] as $key => $val)
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                                        <span class="text-slate-500 font-bold min-w-[140px] text-sm font-mono tracking-tight">{{ $key }}</span>
                                        <div class="flex-1">
                                            <code class="text-cyan-700 font-bold bg-white px-3 py-1.5 rounded-lg border border-cyan-100 text-sm italic shadow-sm block sm:inline-block">{{ $val }}</code>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Parameters -->
                        @if(isset($api['parameters']) && count($api['parameters']) > 0)
                        <div class="mb-8">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">請求參數 (Parameters)</h4>
                            <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm bg-white">
                                <table class="w-full param-table">
                                    <thead>
                                        <tr>
                                            <th class="w-[25%]">欄位名稱</th>
                                            <th class="w-[15%]">型態</th>
                                            <th>說明</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($api['parameters'] as $name => $param)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="font-black text-slate-900 text-base">
                                                {{ $name }}
                                                @if($param['required'] ?? false)
                                                <span class="text-rose-500 ml-1">*</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="px-2 py-1 rounded bg-slate-100 text-xs font-black text-slate-600 uppercase tracking-wider">{{ $param['type'] }}</span>
                                            </td>
                                            <td class="text-slate-600 leading-relaxed font-semibold">
                                                {!! nl2br(e($param['description'])) !!}
                                                @if(isset($param['example']))
                                                <div class="mt-2 flex items-center gap-2">
                                                    <span class="text-[11px] font-black text-slate-400 uppercase tracking-tight">範例：</span>
                                                    <code class="text-sm text-cyan-600 font-bold">{{ is_array($param['example']) ? json_encode($param['example'], JSON_UNESCAPED_UNICODE) : $param['example'] }}</code>
                                                </div>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        <!-- Request & Response Examples -->
                        <div class="space-y-8 mt-10">
                            @if(isset($api['request']) && !empty($api['request']))
                            <div>
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">請求範例 (Request Body)</h4>
                                <pre><code>{{ json_encode($api['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </div>
                            @endif

                            @if(isset($api['response_parameters']) && count($api['response_parameters']) > 0)
                            <div>
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">回應參數說明 (Response Parameters)</h4>
                                <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm bg-white mb-6">
                                    <table class="w-full param-table">
                                        <thead>
                                            <tr>
                                                <th class="w-[25%]">欄位名稱</th>
                                                <th class="w-[15%]">型態</th>
                                                <th>說明</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($api['response_parameters'] as $name => $param)
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="font-black text-slate-900 text-sm italic">{{ $name }}</td>
                                                <td><span class="px-2 py-1 rounded bg-slate-100 text-[10px] font-black text-slate-600 uppercase tracking-wider">{{ $param['type'] }}</span></td>
                                                <td class="text-slate-600 font-semibold text-sm whitespace-pre-line">{{ $param['description'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            @if(isset($api['response']) && !empty($api['response']))
                            <div>
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">回應範例 (Response Body)</h4>
                                <pre><code>{{ json_encode($api['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </div>
                            @endif

                            @if(isset($api['error_responses']) && count($api['error_responses']) > 0)
                            <div>
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">錯誤回應 (Error Responses)</h4>
                                <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm bg-white mb-6">
                                    <table class="w-full param-table">
                                        <thead>
                                            <tr>
                                                <th class="w-[15%]">HTTP / code</th>
                                                <th class="w-[30%]">message</th>
                                                <th>說明</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($api['error_responses'] as $err)
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td><span class="px-2 py-1 rounded bg-rose-100 text-[10px] font-black text-rose-600 uppercase tracking-wider">{{ $err['code'] }}</span></td>
                                                <td class="font-black text-slate-900 text-sm">{{ $err['message'] }}</td>
                                                <td class="text-slate-600 font-semibold text-sm whitespace-pre-line">{{ $err['description'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            @if(isset($api['notes']))
                            <div class="p-6 bg-cyan-50/50 border border-cyan-100 rounded-3xl mt-6">
                                <h5 class="text-cyan-700 font-black mb-2 flex items-center gap-2 text-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    實作備註
                                </h5>
                                <p class="text-cyan-800 font-semibold leading-relaxed text-base">{{ $api['notes'] }}</p>
                            </div>
                            @endif
                        </div>
                        <div class="mt-16 mb-8 border-b border-slate-100"></div>
                    </div>
                @endforeach
            @endforeach
        </div>

        <!-- MQTT Content -->
        <div x-show="currentTab === 'mqtt'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
            @foreach($docs['mqtt_topics'] as $category)
                @foreach($category['topics'] as $topic)
                    <div id="{{ $topic['slug'] }}" class="mb-16 animate-luxury-in scroll-mt-10" x-intersect.margin.-200px="activeSection = '{{ $topic['slug'] }}'">
                        
                        <div class="flex items-center gap-4 mb-6">
                            <span class="px-3 py-1 text-sm font-black rounded-lg uppercase tracking-widest {{ $topic['action'] === 'PUB' ? 'bg-violet-100 text-violet-700' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ $topic['action'] }}
                            </span>
                            <h3 class="font-display text-3xl font-black text-slate-900 tracking-tight">{{ $topic['name'] }}</h3>
                        </div>
                        <p class="text-slate-600 mb-8 text-lg font-medium">{!! nl2br(e($topic['description'])) !!}</p>

                        <!-- Topic Block -->
                        <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200">
                            <div class="bg-slate-900 px-6 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 {{ $topic['action'] === 'PUB' ? 'bg-violet-500' : 'bg-indigo-500' }} text-white text-xs font-black rounded-lg uppercase letter-spacing-widest shadow-sm">{{ $topic['action'] }}</span>
                                    <code class="text-slate-300 font-mono text-base sm:text-lg break-all">{{ $topic['topic'] }}</code>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-slate-400 font-mono text-xs font-bold px-2 py-1 bg-slate-800 rounded">QoS {{ $topic['qos'] ?? 0 }}</span>
                                    <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest hidden sm:block">MQTT Topic</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payload Parameters -->
                        @if(isset($topic['payload_parameters']) && count($topic['payload_parameters']) > 0)
                        <div class="mb-8">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">資料載體 (Payload Parameters)</h4>
                            <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm bg-white">
                                <table class="w-full param-table">
                                    <thead>
                                        <tr>
                                            <th class="w-[25%]">欄位名稱</th>
                                            <th class="w-[15%]">型態</th>
                                            <th>說明</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topic['payload_parameters'] as $name => $param)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="font-black text-slate-900 text-base">
                                                {{ $name }}
                                                @if($param['required'] ?? true)
                                                <span class="text-rose-500 ml-1">*</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="px-2 py-1 rounded bg-slate-100 text-xs font-black text-slate-600 uppercase tracking-wider">{{ $param['type'] }}</span>
                                            </td>
                                            <td class="text-slate-600 leading-relaxed font-semibold">
                                                {!! nl2br(e($param['description'])) !!}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        <!-- Payload Example -->
                        @if(isset($topic['payload_example']) && !empty($topic['payload_example']))
                        <div class="mt-8">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                Payload JSON 範例
                            </h4>
                            <pre><code>{{ json_encode($topic['payload_example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>
                        @endif

                        @if(isset($topic['notes']))
                        <div class="p-6 bg-indigo-50/50 border border-indigo-100 rounded-3xl mt-8">
                            <h5 class="text-indigo-700 font-black mb-2 flex items-center gap-2 text-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                實作備註
                            </h5>
                            <p class="text-indigo-800 font-semibold leading-relaxed text-base whitespace-pre-line">{{ $topic['notes'] }}</p>
                        </div>
                        @endif

                        <div class="mt-16 mb-8 border-b border-slate-100"></div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>

</html>