<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Machine Distribution Map') }} | StillBuilding Life</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', 'Outfit', sans-serif; }
        #map { height: 100%; width: 100%; z-index: 1; }
        .luxury-shadow { box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.15); }
        .glass-panel {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        /* Custom Marker Style */
        .custom-marker {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .marker-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        .marker-online { background-color: #10b981; }
        .marker-offline { background-color: #64748b; }
        .marker-error { background-color: #ef4444; }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <div class="flex h-full relative">
        <!-- Sidebar -->
        <div class="hidden md:flex flex-col w-96 glass-panel border-r border-slate-200/50 dark:border-slate-800/50 z-20 overflow-hidden">
            <div class="p-8 border-b border-slate-200/50 dark:border-slate-800/50">
                <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight outfit-font">
                    StillBuilding Life
                </h1>
                <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.4em] mt-2">
                    {{ __('Machine Distribution') }}
                </p>
            </div>

            <!-- Model Statistics -->
            @php
                $groupedByModel = $machines->groupBy(fn($m) => $m->machineModel?->name ?? __('Unknown Model'));
                $modelColors = [
                    'U4' => '#EAB308', // Yellow
                    'U3' => '#15803D', // Green
                    'U1' => '#F97316', // Orange
                    'U4S' => '#0D9488', // Teal
                    'I1' => '#854D0E', // Olive
                    'U4SSS' => '#EF4444', // Red
                    'Y1' => '#F97316', // Orange
                    'U4SS' => '#3B82F6', // Blue
                    'U4S(短)' => '#1E3A8A', // Dark Blue
                    'U4XXXS' => '#64748B', // Slate
                    '易豐主機' => '#22C55E', // Bright Green
                    'I10' => '#A855F7', // Purple
                    'N1' => '#BEF264', // Lime
                    'Y2' => '#DC2626', // Red
                    'U4_Android9_test' => '#10b981', // Emerald
                ];
                
                // Unify fallback color logic with JS
                $getFallbackColor = function($name) {
                    $hash = 0;
                    $len = mb_strlen($name);
                    for ($i = 0; $i < $len; $i++) {
                        $char = mb_substr($name, $i, 1);
                        $code = mb_ord($char);
                        $hash = $code + (($hash << 5) - $hash);
                        $hash = $hash & 0xFFFFFFFF; // Keep it 32-bit
                    }
                    return sprintf('#%06X', $hash & 0xFFFFFF);
                };
            @endphp
            
            <div class="p-4 border-b border-slate-200/50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-y-auto max-h-64 custom-scrollbar">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Statistics: Model') }}</h3>
                </div>
                <div class="grid grid-cols-1 gap-2">
                    @foreach($groupedByModel as $modelName => $group)
                        @php $color = $modelColors[$modelName] ?? $getFallbackColor($modelName); @endphp
                        <div class="flex items-center justify-between group cursor-pointer" onclick="filterByModel('{{ $modelName }}')">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shadow-sm" style="background-color: {{ $color }}"></span>
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 group-hover:text-cyan-500 transition-colors">{{ $modelName }}</span>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400">({{ $group->count() }})</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Search Box -->
            <div class="px-4 pb-4 border-b border-slate-200/50 dark:border-slate-800/50 bg-slate-50/30 dark:bg-slate-900/30 pt-4">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" onkeyup="updateSearchFilter(this.value)" placeholder="{{ __('Search name or serial...') }}" 
                           class="machine-search-input w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all shadow-sm">
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar" id="machine-list">
                @foreach($machines as $machine)
                @php 
                    $modelName = $machine->machineModel?->name ?? __('Unknown Model');
                    $color = $modelColors[$modelName] ?? $getFallbackColor($modelName);
                @endphp
                <div class="machine-item p-4 rounded-2xl bg-white/50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-cyan-500/30 transition-all cursor-pointer group"
                     data-model="{{ $modelName }}"
                     data-id="{{ $machine->id }}"
                     onclick="focusMachine({{ $machine->latitude }}, {{ $machine->longitude }}, {{ $machine->id }})">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" style="background-color: {{ $color }}"></span>
                            <span class="text-sm font-black text-slate-800 dark:text-white group-hover:text-cyan-500 transition-colors">
                                {{ $machine->name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full {{ $machine->status === 'online' ? 'bg-emerald-500' : ($machine->status === 'offline' ? 'bg-slate-400' : 'bg-rose-500') }}"></span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ __($machine->status) }}</span>
                        </div>
                    </div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 leading-relaxed truncate">
                        {{ $machine->address ?: $machine->location ?: __('No address information') }}
                    </p>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-[10px] font-mono text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded shadow-sm">
                            {{ $machine->serial_no }}
                        </span>
                        <span class="text-[9px] font-black text-slate-300 dark:text-slate-600 uppercase">{{ $modelName }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="p-6 border-t border-slate-200/50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center justify-between text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <span>{{ __('Total Machines') }}</span>
                    <span class="text-slate-800 dark:text-white">{{ $machines->count() }}</span>
                </div>
                <button onclick="resetFilter()" class="mt-4 w-full py-2 rounded-xl border border-slate-200 dark:border-slate-800 text-[10px] font-black text-slate-400 hover:text-cyan-500 hover:border-cyan-500/30 transition-all uppercase tracking-widest">
                    {{ __('Show All') }}
                </button>
            </div>
        </div>

        <!-- Map Container -->
        <div class="flex-1 relative h-full">
            <div id="map"></div>
            
            <!-- Floating Top Header (Mobile) -->
            <div class="md:hidden absolute top-4 left-4 right-4 z-[1000]">
                <div class="glass-panel p-3 rounded-2xl luxury-shadow border border-slate-200/50 dark:border-slate-800/50 flex items-center justify-between shadow-lg">
                    <div>
                        <h1 class="text-lg font-black text-slate-800 dark:text-white tracking-tight outfit-font line-clamp-1">
                            {{ __('Machine Distribution Map') }}
                        </h1>
                    </div>
                    <button @click="mobileMenuOpen = true" 
                            class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-500 border border-cyan-500/20 hover:bg-cyan-500 hover:text-white transition-all active:scale-95 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer -->
    <div x-show="mobileMenuOpen" 
         class="md:hidden fixed inset-0 z-[2000] overflow-hidden"
         x-cloak>
        <!-- Backdrop -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

        <!-- Drawer Content -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="absolute inset-y-0 right-0 w-[85%] max-w-sm glass-panel shadow-2xl border-l border-white/20 dark:border-slate-800/50 flex flex-col">
            
            <!-- Drawer Header -->
            <div class="p-6 border-b border-slate-200/50 dark:border-slate-800/50 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight outfit-font">
                        {{ __('Machine List') }}
                    </h2>
                    <p class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-widest mt-1">
                        {{ $machines->count() }} {{ __('Units') }}
                    </p>
                </div>
                <button @click="mobileMenuOpen = false" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Drawer Search -->
            <div class="p-4 bg-slate-50/30 dark:bg-slate-900/30 border-b border-slate-200/50 dark:border-slate-800/50">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" onkeyup="updateSearchFilter(this.value)" placeholder="{{ __('Search...') }}" 
                           class="machine-search-input w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-700 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all shadow-sm">
                </div>
            </div>

            <!-- Drawer Body -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                <!-- Reusing model filters -->
                <div class="mb-6 p-4 rounded-2xl bg-slate-100/50 dark:bg-slate-800/50">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">{{ __('Filter: Model') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($groupedByModel as $modelName => $group)
                            @php $color = $modelColors[$modelName] ?? $getFallbackColor($modelName); @endphp
                            <button onclick="filterByModel('{{ $modelName }}')" 
                                    class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center gap-2 hover:border-cyan-500 transition-all">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $color }}"></span>
                                <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300">{{ $modelName }}</span>
                            </button>
                        @endforeach
                        <button onclick="resetFilter()" class="px-3 py-1.5 rounded-full bg-slate-200 dark:bg-slate-700 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest hover:bg-cyan-500 hover:text-white transition-all">
                            {{ __('All') }}
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach($machines as $machine)
                    @php 
                        $modelName = $machine->machineModel?->name ?? __('Unknown Model');
                        $color = $modelColors[$modelName] ?? $getFallbackColor($modelName);
                    @endphp
                    <div class="machine-item p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-cyan-500/30 transition-all cursor-pointer group"
                         data-model="{{ $modelName }}"
                         data-id="{{ $machine->id }}"
                         onclick="focusMachine({{ $machine->latitude }}, {{ $machine->longitude }}, {{ $machine->id }})">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $color }}"></span>
                                <span class="text-sm font-black text-slate-800 dark:text-white group-hover:text-cyan-500 transition-colors">
                                    {{ $machine->name }}
                                </span>
                            </div>
                            <x-status-badge :status="$machine->status" size="sm" />
                        </div>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 leading-relaxed truncate">
                            {{ $machine->address ?: $machine->location ?: __('No address information') }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        const machines = @json($machines);
        const modelColors = @json($modelColors);
        
        function getFallbackColor(name) {
            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }
            const color = '#' + (hash & 0x00FFFFFF).toString(16).toUpperCase().padStart(6, '0');
            return color;
        }

        const map = L.map('map', {
            zoomControl: false
        }).setView([23.973875, 120.982024], 8); // Center of Taiwan

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);

        const markers = {};
        const markerGroups = {};

        machines.forEach(machine => {
            if (machine.latitude && machine.longitude) {
                const modelName = machine.machine_model?.name || '{{ __("Unknown Model") }}';
                const color = modelColors[modelName] || getFallbackColor(modelName);
                
                const icon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="marker-dot" style="background-color: ${color}; border-color: white;"></div>`,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });

                const marker = L.marker([machine.latitude, machine.longitude], { icon: icon }).addTo(map);
                
                const popupContent = `
                    <div class="p-2 min-w-[200px]">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: ${color}"></div>
                                <span class="text-sm font-black text-slate-800">${machine.name}</span>
                            </div>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">${modelName}</span>
                        </div>
                        <p class="text-[11px] font-medium text-slate-500 mb-2">${machine.address || machine.location || ''}</p>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-2 mt-2">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">${machine.serial_no}</span>
                            <div class="flex items-center gap-1">
                                <div class="w-1.5 h-1.5 rounded-full ${machine.status === 'online' ? 'bg-emerald-500' : (machine.status === 'offline' ? 'bg-slate-400' : 'bg-rose-500')}"></div>
                                <span class="text-[9px] font-bold text-cyan-600 uppercase tracking-widest">${machine.status.toUpperCase()}</span>
                            </div>
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent, {
                    maxWidth: 300,
                    className: 'luxury-popup'
                });
                
                markers[machine.id] = marker;
                
                if (!markerGroups[modelName]) markerGroups[modelName] = [];
                markerGroups[modelName].push(marker);
            }
        });

        function focusMachine(lat, lng, id) {
            // Close mobile menu if open
            if (window.innerWidth < 768) {
                try {
                    // Access Alpine state via data-stack
                    const body = document.querySelector('body');
                    if (body && body._x_dataStack) {
                        body._x_dataStack[0].mobileMenuOpen = false;
                    }
                } catch (e) { console.log('Alpine sync error:', e); }
            }

            map.flyTo([lat, lng], 15, {
                duration: 1.5
            });
            setTimeout(() => {
                if (markers[id]) {
                    markers[id].openPopup();
                }
            }, 1600);
        }

        let currentModelFilter = null;
        let currentSearchQuery = '';

        function applyFilters() {
            const visibleMarkers = [];
            
            machines.forEach(machine => {
                const modelName = machine.machine_model?.name || '{{ __("Unknown Model") }}';
                const matchesModel = !currentModelFilter || modelName === currentModelFilter;
                const matchesSearch = !currentSearchQuery || 
                                     machine.name.toLowerCase().includes(currentSearchQuery) ||
                                     machine.serial_no.toLowerCase().includes(currentSearchQuery);
                
                const isVisible = matchesModel && matchesSearch;
                
                if (markers[machine.id]) {
                    if (isVisible) {
                        markers[machine.id].addTo(map);
                        visibleMarkers.push(markers[machine.id]);
                    } else {
                        markers[machine.id].remove();
                    }
                }
                
                const item = document.querySelector(`.machine-item[data-id="${machine.id}"]`);
                if (item) {
                    if (isVisible) item.classList.remove('hidden');
                    else item.classList.add('hidden');
                }
            });

            // If we have visible markers and a model filter is active, adjust bounds
            if (currentModelFilter && visibleMarkers.length > 0) {
                const group = new L.featureGroup(visibleMarkers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }

        function filterByModel(modelName) {
            currentModelFilter = modelName;
            applyFilters();
        }

        function updateSearchFilter(query) {
            currentSearchQuery = query.toLowerCase().trim();
            
            // Sync all search inputs
            document.querySelectorAll('.machine-search-input').forEach(input => {
                if (input.value.toLowerCase().trim() !== currentSearchQuery) {
                    input.value = query;
                }
            });

            applyFilters();
        }

        function resetFilter() {
            currentModelFilter = null;
            currentSearchQuery = '';
            document.querySelectorAll('.machine-search-input').forEach(input => input.value = '');
            applyFilters();
            map.setView([23.973875, 120.982024], 8);
        }

        // Adjust map on window resize
        window.addEventListener('resize', () => {
            map.invalidateSize();
        });
    </script>
</body>
</html>
