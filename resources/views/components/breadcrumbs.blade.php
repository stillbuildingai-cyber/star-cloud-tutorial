@props(['links' => null])

@php
    if (is_null($links)) {
        $routeName = Route::currentRouteName();
        $links = [];

        // 預設首頁 (Dashboard)
        $links[] = [
            'label' => __('Dashboard'),
            'url' => route('admin.dashboard'),
            'active' => $routeName === 'admin.dashboard'
        ];

        if ($routeName && $routeName !== 'admin.dashboard') {
            // 定義大模組映射表 (路由前綴 => [Label, IndexRoute])
            $moduleMap = [
                'profile' => [__('Profile Settings'), 'profile.edit'],
                'admin.members' => [__('Member Management'), 'admin.members.index'],
                'admin.membership-tiers' => [__('Member Management'), 'admin.membership-tiers.index'],
                'admin.deposit-bonus-rules' => [__('Member Management'), 'admin.deposit-bonus-rules.index'],
                'admin.point-rules' => [__('Member Management'), 'admin.point-rules.index'],
                'admin.gift-definitions' => [__('Member Management'), 'admin.gift-definitions.index'],
                'admin.machines' => [__('Machine Management'), 'admin.machines.index'],
                'admin.app' => [__('APP Management'), 'admin.app-configs.index'],
                'admin.warehouses' => [__('Warehouse Management'), 'admin.warehouses.index'],
                'admin.sales' => [__('Sales Management'), 'admin.sales.index'],
                'admin.analysis' => [__('Analysis Management'), 'admin.analysis.machine-reports'],
                'admin.audit' => [__('Audit Management'), 'admin.audit.purchases'],
                'admin.data-config' => [__('Data Configuration'), 'admin.data-config.products.index'],
                'admin.remote' => [__('Remote Management'), 'admin.remote.index'],
                'admin.line' => [__('Line Management'), 'admin.line.official-account'],
                'admin.reservation' => [__('Reservation System'), 'admin.reservation.reservations'],
                'admin.special-permission' => [__('Special Permission'), 'admin.special-permission.clear-stock'],
                'admin.permission' => [__('Permission Settings'), 'admin.permission.accounts'],
                'admin.basic-settings' => [__('Basic Settings'), 'admin.basic-settings.machines.index'],
                'admin.maintenance' => [__('Machine Management'), 'admin.maintenance.index'],
            ];

            // 1. 找出所屬大模組
            $foundModule = null;
            foreach ($moduleMap as $prefix => $data) {
                if (str_starts_with($routeName, $prefix)) {
                    $label = $data[0];
                    $indexRoute = $data[1];
                    
                    $url = '#';
                    try {
                        if ($indexRoute && Route::has($indexRoute)) {
                            $url = route($indexRoute);
                        }
                    } catch (\Exception $e) {}

                    $foundModule = [
                        'label' => $label, 
                        'url' => $url,
                        'active' => false
                    ];
                    break;
                }
            }

            if ($foundModule) {
                $links[] = $foundModule;
            }

            // 2. 處理具體頁面
            $segments = explode('.', $routeName);
            $lastSegment = end($segments);
            
            $pageLabel = match($lastSegment) {
                'index' => match($segments[1] ?? '') {
                    'members' => __('Member List'),
                    'machines' => __('Machine List'),
                    'warehouses' => __('Warehouse Overview'),
                    'sales' => __('Sales Records'),
                    'analysis' => __('Analysis Management'),
                    'audit' => __('Audit Management'),
                    'maintenance' => __('Maintenance Records'),
                    'data-config' => match($segments[2] ?? '') {
                        'products' => __('Product Management'),
                        'advertisements' => __('Advertisement Management'),
                        default => null,
                    },
                    'remote' => __('Command Center'),
                    'basic-settings' => match($segments[2] ?? '') {
                        'machines' => __('Machine Settings'),
                        'payment-configs' => __('Payment Config'),
                        'machine-models' => __('Machine Models'),
                        'discord-notifications' => __('Discord Notifications'),
                        default => null,
                    },
                    'permission' => match($segments[2] ?? '') {
                        'companies' => __('Customer Management'),
                        'accounts' => __('Account Management'),
                        'roles' => __('Roles'),
                        default => null,
                    },
                    default => null,
                },
                'edit' => str_starts_with($routeName, 'profile') ? __('Profile') : __('Edit'),
                'create' => __('Create'),
                'show' => __('Detail'),
                'logs' => __('Machine Logs'),
                'permissions' => __('Machine Permissions'),
                'utilization' => __('Utilization Rate'),
                'pricing' => __('Machine Pricing'),
                'expiry' => __('Expiry Management'),
                'maintenance' => __('Maintenance Records'),
                'ui-elements' => __('UI Elements'),
                'helper' => __('Helper'),
                'questionnaire' => __('Questionnaire'),
                'games' => __('Games'),
                'timer' => __('Timer'),
                'inventory' => __('Inventory Management'),
                'machine-inventory' => __('Machine Inventory'),
                'transfers' => __('Transfer Orders'),
                'replenishments' => __('Machine Replenishment'),
                'pickup-codes' => __('Pickup Codes'),
                'orders' => __('Orders'),
                'promotions' => __('Promotions'),
                'pass-codes' => __('Pass Codes'),
                'store-gifts' => __('Store Gifts'),
                'pharmacy-pickup' => __('menu.sales.pharmacy-pickup'),
                'change-stock' => __('Change Stock'),
                'machine-reports' => __('Machine Reports'),
                'product-reports' => __('Product Reports'),
                'survey-analysis' => __('Survey Analysis'),
                'products' => __('Product Management'),
                'advertisements' => __('Advertisement Management'),
                'accounts' => __('Account Management'),
                'sub-accounts' => __('Sub Accounts'),
                'sub-account-roles' => __('Sub Account Roles'),
                'points' => __('Point Settings'),
                'badges' => __('Badge Settings'),
                'official-account' => __('Line Official Account'),
                'coupons' => __('Line Coupons'),
                'stores' => __('Store Management'),
                'time-slots' => __('Time Slots'),
                'venues' => __('Venue Management'),
                'reservations' => __('Reservations'),
                'clear-stock' => __('Clear Stock'),
                'apk-versions' => __('APK Versions'),
                'discord-notifications' => __('Discord Notifications'),
                'roles' => __('Roles'),
                'stock' => __('Stock & Expiry'),
                default => null,
            };

            if ($pageLabel) {
                $links[] = [
                    'label' => $pageLabel,
                    'active' => true
                ];
            }

            // 確保最後一個 link 是 active 的，並過濾重複標籤
            if (!empty($links)) {
                // 檢查是否有相鄰重複的 Label (例如 Dashboard > Dashboard 或 Warehouse Management > Warehouse Overview)
                // 如果後面的標籤與前面相同，則移除前面的，保留具體的
                $newLinks = [];
                foreach ($links as $link) {
                    if (!empty($newLinks) && end($newLinks)['label'] === $link['label']) {
                        array_pop($newLinks);
                    }
                    $newLinks[] = $link;
                }
                $links = $newLinks;
                
                // 重置所有 active 為 false，最後一個為 true
                foreach ($links as &$l) $l['active'] = false;
                $links[count($links) - 1]['active'] = true;
            }
        }
    }
@endphp

<nav {{ $attributes->merge(['class' => 'flex', 'aria-label' => 'Breadcrumb']) }}>
    <ol class="flex items-center whitespace-nowrap min-w-0 w-full">
        @foreach($links as $link)
            @php
                $isActive = $link['active'] ?? false;
                $url = $link['url'] ?? '#';
            @endphp
            <li class="flex items-center text-sm {{ $isActive ? 'font-semibold text-slate-800 dark:text-slate-200' : 'text-slate-500 dark:text-slate-400' }} {{ !$loop->last ? 'shrink-0' : 'truncate' }}">
                @if(!$isActive && $url !== '#')
                    <a class="hover:text-cyan-600 transition-colors" href="{{ $url }}">
                        {{ $link['label'] }}
                    </a>
                @else
                    {{ $link['label'] }}
                @endif
                
                @if(!$loop->last)
                    <svg class="flex-shrink-0 mx-3 overflow-visible h-2.5 w-2.5 text-slate-500 dark:text-slate-400" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 1L10.6869 7.16086C10.8637 7.35239 10.8637 7.64761 10.6869 7.83914L5 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
