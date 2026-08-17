<div class="space-y-6">
    <div class="flex items-center justify-between mb-2">
        <div>
            <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight font-display">{{ __('Machine Distribution Map') }}</h3>
            <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ __('Visual overview of all machine locations') }}</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Online') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Offline') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Error') }}</span>
            </div>
        </div>
    </div>

    <div class="relative rounded-3xl overflow-hidden border border-slate-100 dark:border-slate-800 h-[600px] shadow-sm">
        <div id="tab-map" class="w-full h-full z-10"></div>
    </div>
</div>

<!-- Leaflet CSS & JS (Only load if not already present) -->
@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endonce

<style>
    .luxury-popup .leaflet-popup-content-wrapper {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        border-radius: 16px;
        padding: 0;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .dark .luxury-popup .leaflet-popup-content-wrapper {
        background: rgba(15, 23, 42, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .luxury-popup .leaflet-popup-content {
        margin: 0;
    }
    .luxury-popup .leaflet-popup-tip {
        background: rgba(255, 255, 255, 0.9);
    }
    .dark .luxury-popup .leaflet-popup-tip {
        background: rgba(15, 23, 42, 0.9);
    }
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

<script>
    (function() {
        const initMap = () => {
            const mapEl = document.getElementById('tab-map');
            if (!mapEl || mapEl._leaflet_id) return;

            const distributionMachines = @json($distributionMachines);
            const map = L.map('tab-map', {
                zoomControl: true
            }).setView([23.973875, 120.982024], 7); // Center of Taiwan

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            distributionMachines.forEach(machine => {
                if (machine.latitude && machine.longitude) {
                    const statusClass = `marker-${machine.status}`;
                    const icon = L.divIcon({
                        className: 'custom-marker',
                        html: `<div class="marker-dot ${statusClass}"></div>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    });

                    const marker = L.marker([machine.latitude, machine.longitude], { icon: icon }).addTo(map);
                    
                    const popupContent = `
                        <div class="p-4 min-w-[200px] bg-white dark:bg-slate-900">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 rounded-full ${machine.status === 'online' ? 'bg-emerald-500' : (machine.status === 'offline' ? 'bg-slate-400' : 'bg-rose-500')}"></div>
                                <span class="text-sm font-black text-slate-800 dark:text-white font-display">${machine.name}</span>
                            </div>
                            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-3">${machine.address || machine.location || ''}</p>
                            <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 pt-3">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">${machine.serial_no}</span>
                                <span class="text-[9px] font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-widest">${machine.status.toUpperCase()}</span>
                            </div>
                        </div>
                    `;
                    
                    marker.bindPopup(popupContent, {
                        maxWidth: 300,
                        className: 'luxury-popup'
                    });
                }
            });

            // Handle resize when tab becomes visible
            window.addEventListener('resize', () => {
                setTimeout(() => map.invalidateSize(), 100);
            });
            
            // Intersection Observer to handle initial render when tab is switched
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        setTimeout(() => map.invalidateSize(), 200);
                    }
                });
            }, { threshold: 0.1 });
            observer.observe(mapEl);
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMap);
        } else {
            initMap();
        }

        // Also hook into Alpine tab changes
        window.addEventListener('tab-changed', (e) => {
            if (e.detail.tab === 'distribution') {
                setTimeout(initMap, 100);
            }
        });
    })();
</script>
