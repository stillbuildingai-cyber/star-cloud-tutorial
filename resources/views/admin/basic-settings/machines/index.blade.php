@extends('layouts.admin')

@section('content')
<div class="space-y-2 pb-20" x-data="{ 
    tab: '{{ $tab }}',
    tabLoading: false,
    machineSearch: '',
    machineCompanyId: @js(request('company_id')),
    modelSearch: '',
    permissionSearch: '',
    permissionCompanyId: @js(request('company_id')),
    isUpdatingSetting: false,
    async toggleSystemSetting(machineId, field, value) {
        if (this.isUpdatingSetting) return;
        this.isUpdatingSetting = true;
        try {
            const res = await fetch(`/admin/basic-settings/machines/${machineId}/update-system-settings`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ field, value })
            });
            const data = await res.json();
            if (data.success) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'success' } }));
            } else {
                throw new Error(data.message || 'Update failed');
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
            // Refresh content to reset toggle state on UI
            this.searchInTab('system_settings');
        } finally {
            this.isUpdatingSetting = false;
        }
    },
    // ── 同步系統設定 (通知機台 APP 回抓 B014) ──
    showSyncSettingsModal: false,
    isSyncingSettings: false,
    syncSettingsMachineId: null,
    syncSettingsMachineLocked: false,
    syncSettingsNote: '',
    syncMachineSearch: '',
    syncMachineDropdownOpen: false,
    allMachinesList: @js($allMachines ?? []),
    openSyncSettingsModal(machineId = null) {
        this.syncSettingsMachineId = machineId ? Number(machineId) : null;
        this.syncSettingsMachineLocked = machineId !== null;
        this.syncSettingsNote = '';
        this.syncMachineSearch = '';
        this.syncMachineDropdownOpen = false;
        this.showSyncSettingsModal = true;
    },
    get filteredSyncMachines() {
        const kw = this.syncMachineSearch.trim().toLowerCase();
        if (!kw) return this.allMachinesList;
        return this.allMachinesList.filter(m =>
            (m.name || '').toLowerCase().includes(kw) ||
            (m.serial_no || '').toLowerCase().includes(kw)
        );
    },
    selectSyncMachine(id) {
        this.syncSettingsMachineId = Number(id);
        this.syncMachineDropdownOpen = false;
        this.syncMachineSearch = '';
    },
    syncSelectedMachineLabel() {
        const m = this.allMachinesList.find(x => x.id == this.syncSettingsMachineId);
        return m ? `${m.name} (${m.serial_no})` : '';
    },
    async executeSyncSettings() {
        if (this.isSyncingSettings) return;
        if (!this.syncSettingsMachineId) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Please select a machine first') }}', type: 'error' } }));
            return;
        }
        this.isSyncingSettings = true;
        try {
            const res = await fetch(`/admin/basic-settings/machines/${this.syncSettingsMachineId}/sync-settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ note: this.syncSettingsNote })
            });
            const data = await res.json();
            if (data.success) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'success' } }));
                this.showSyncSettingsModal = false;
            } else {
                throw new Error(data.message || 'Sync failed');
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
        } finally {
            this.isSyncingSettings = false;
        }
    },
    async submitMachineSettings() {
        if (this.isUpdatingSetting || !this.currentMachine) return;
        this.isUpdatingSetting = true;
        try {
            const res = await fetch(`/admin/basic-settings/machines/${this.currentMachine.id}/update-system-settings`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ settings: this.machineSettings })
            });
            const data = await res.json();
            if (data.success) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'success' } }));
                this.showMachineSettingsModal = false;
                this.searchInTab('system_settings');
            } else {
                throw new Error(data.message || '{{ __('Update failed') }}');
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
        } finally {
            this.isUpdatingSetting = false;
        }
    },
    showCreateMachineModal: false,
    showPhotoModal: false,
    showDetailDrawer: false,
    currentMachine: null,
    showCreateModelModal: false,
    showEditModelModal: false,
    newModel: { name: '', temp_upper_limit: '', temp_lower_limit: '' },
    currentModel: { name: '', temp_upper_limit: '', temp_lower_limit: '' },
    modelActionUrl: '',
    adjustModelTemp(modelObj, field, delta, fallbackVal) {
        let current = modelObj[field];
        if (current === undefined || current === null || current === '') {
            current = parseInt(fallbackVal);
        } else {
            current = parseInt(current);
        }
        if (isNaN(current)) current = 0;
        let newValue = current + delta;
        if (newValue < -50) newValue = -50;
        if (newValue > 100) newValue = 100;
        modelObj[field] = newValue.toString();
    },
    selectedFileCount: 0,
    selectedFiles: [null, null, null],
    deletedPhotos: [false, false, false],
    showImageLightbox: false,
    lightboxImageUrl: '',
    showMaintenanceQrModal: false,
    maintenanceQrMachineName: '',
    maintenanceQrUrl: '',
    permissionSearchQuery: '',
    showMachineSettingsModal: false,
    machineSettings: {},
    openMachineSettingsModal(machine) {
        this.currentMachine = machine;
        const settings = machine.settings || {};
        this.machineSettings = {
            shopping_mode: settings.shopping_mode || 'basic',
            tax_invoice_enabled: machine.tax_invoice_enabled === true || machine.tax_invoice_enabled === 1 || machine.tax_invoice_enabled === '1',
            
            // 刷卡機支付
            card_terminal_enabled: machine.card_terminal_enabled === true || machine.card_terminal_enabled === 1 || machine.card_terminal_enabled === '1',
            credit_card_enabled: settings.credit_card_enabled === true || settings.credit_card_enabled === 1 || settings.credit_card_enabled === '1',
            mobile_pay_enabled: settings.mobile_pay_enabled === true || settings.mobile_pay_enabled === 1 || settings.mobile_pay_enabled === '1',
            card_pay_enabled: settings.card_pay_enabled === true || settings.card_pay_enabled === 1 || settings.card_pay_enabled === '1',

            // 掃碼支付
            scan_pay_enabled: settings.scan_pay_enabled === true || settings.scan_pay_enabled === 1 || settings.scan_pay_enabled === '1',
            scan_pay_esun_enabled: machine.scan_pay_esun_enabled === true || machine.scan_pay_esun_enabled === 1 || machine.scan_pay_esun_enabled === '1',
            scan_pay_tappay_enabled: settings.scan_pay_tappay_enabled === true || settings.scan_pay_tappay_enabled === 1 || settings.scan_pay_tappay_enabled === '1',
            tappay_linepay: settings.tappay_linepay === true || settings.tappay_linepay === 1 || settings.tappay_linepay === '1',
            tappay_jkopay: settings.tappay_jkopay === true || settings.tappay_jkopay === 1 || settings.tappay_jkopay === '1',
            tappay_pipay: settings.tappay_pipay === true || settings.tappay_pipay === 1 || settings.tappay_pipay === '1',
            tappay_pluspay: settings.tappay_pluspay === true || settings.tappay_pluspay === 1 || settings.tappay_pluspay === '1',
            tappay_easywallet: settings.tappay_easywallet === true || settings.tappay_easywallet === 1 || settings.tappay_easywallet === '1',

            // Line 官方支付 (LINE Pay 官方直連)
            scan_pay_linepay_enabled: settings.scan_pay_linepay_enabled === true || settings.scan_pay_linepay_enabled === 1 || settings.scan_pay_linepay_enabled === '1',

            // 現金支付 (預設皆可收，若 JSON 中特別記錄 false 才是 false)
            cash_module_enabled: machine.cash_module_enabled === true || machine.cash_module_enabled === 1 || machine.cash_module_enabled === '1',
            cash_bill_1000: settings.cash_bill_1000 !== false && settings.cash_bill_1000 !== 0 && settings.cash_bill_1000 !== '0',
            cash_bill_500: settings.cash_bill_500 !== false && settings.cash_bill_500 !== 0 && settings.cash_bill_500 !== '0',
            cash_bill_100: settings.cash_bill_100 !== false && settings.cash_bill_100 !== 0 && settings.cash_bill_100 !== '0',
            cash_coin_50: settings.cash_coin_50 !== false && settings.cash_coin_50 !== 0 && settings.cash_coin_50 !== '0',
            cash_coin_10: settings.cash_coin_10 !== false && settings.cash_coin_10 !== 0 && settings.cash_coin_10 !== '0',
            cash_coin_5: settings.cash_coin_5 !== false && settings.cash_coin_5 !== 0 && settings.cash_coin_5 !== '0',
            cash_coin_1: settings.cash_coin_1 !== false && settings.cash_coin_1 !== 0 && settings.cash_coin_1 !== '0',

            // 取貨模組
            pickup_module_enabled: settings.pickup_module_enabled !== undefined
                ? (settings.pickup_module_enabled === true || settings.pickup_module_enabled === 1 || settings.pickup_module_enabled === '1')
                : ((settings.pickup_code_enabled === true || settings.pickup_code_enabled === 1 || settings.pickup_code_enabled === '1') || (settings.pass_code_enabled === true || settings.pass_code_enabled === 1 || settings.pass_code_enabled === '1')),
            pickup_code_enabled: settings.pickup_code_enabled === true || settings.pickup_code_enabled === 1 || settings.pickup_code_enabled === '1',
            pass_code_enabled: settings.pass_code_enabled === true || settings.pass_code_enabled === 1 || settings.pass_code_enabled === '1',

            // 領藥單（取物單模式下的開關）
            pharmacy_pickup_enabled: settings.pharmacy_pickup_enabled === true || settings.pharmacy_pickup_enabled === 1 || settings.pharmacy_pickup_enabled === '1',

            // 副櫃系統（格子櫃功能授權，基礎版專用）
            subcabinet_enabled: settings.subcabinet_enabled === true || settings.subcabinet_enabled === 1 || settings.subcabinet_enabled === '1',

            // 零售附加功能
            shopping_cart_enabled: machine.shopping_cart_enabled === true || machine.shopping_cart_enabled === 1 || machine.shopping_cart_enabled === '1',
            welcome_gift_enabled: machine.welcome_gift_enabled === true || machine.welcome_gift_enabled === 1 || machine.welcome_gift_enabled === '1',
            member_system_enabled: machine.member_system_enabled === true || machine.member_system_enabled === 1 || machine.member_system_enabled === '1',
            
            // 環境監控
            ambient_temp_monitoring_enabled: machine.ambient_temp_monitoring_enabled === true || machine.ambient_temp_monitoring_enabled === 1 || machine.ambient_temp_monitoring_enabled === '1',

            // 顯示語系（有序，第一個為預設）。僅系統管理員可編輯。
            languages: Array.isArray(settings.languages) ? [...settings.languages] : []
        };
        this.showMachineSettingsModal = true;
    },
    // ── 顯示語系白名單與互動 ──
    localeWhitelist: @js(config('locales.supported', [])),
    maxLanguages: {{ (int) config('locales.max_per_machine', 5) }},
    toggleLanguage(loc) {
        const langs = this.machineSettings.languages || [];
        const idx = langs.indexOf(loc);
        if (idx === -1) {
            if (langs.length >= this.maxLanguages) return; // 已達上限
            langs.push(loc);
        } else {
            langs.splice(idx, 1);
        }
        this.machineSettings.languages = langs;
    },
    toggleAllCardTerminal() {
        const keys = ['credit_card_enabled', 'mobile_pay_enabled', 'card_pay_enabled'];
        const allChecked = keys.every(k => this.machineSettings[k]);
        keys.forEach(k => {
            this.machineSettings[k] = !allChecked;
        });
    },
    toggleAllScanPay() {
        const keys = ['scan_pay_esun_enabled', 'scan_pay_tappay_enabled', 'tappay_linepay', 'tappay_jkopay', 'tappay_pipay', 'tappay_pluspay', 'tappay_easywallet'];
        const allChecked = keys.every(k => this.machineSettings[k]);
        keys.forEach(k => {
            this.machineSettings[k] = !allChecked;
        });
    },
    toggleAllCash() {
        const keys = ['cash_bill_1000', 'cash_bill_500', 'cash_bill_100', 'cash_coin_50', 'cash_coin_10', 'cash_coin_5', 'cash_coin_1'];
        const allChecked = keys.every(k => this.machineSettings[k]);
        keys.forEach(k => {
            this.machineSettings[k] = !allChecked;
        });
    },
    toggleAllPickup() {
        const keys = ['pickup_code_enabled', 'pass_code_enabled'];
        const allChecked = keys.every(k => this.machineSettings[k]);
        keys.forEach(k => {
            this.machineSettings[k] = !allChecked;
        });
    },
    toggleAllPayments() {
        const mainKeys = ['card_terminal_enabled', 'scan_pay_enabled', 'scan_pay_linepay_enabled', 'cash_module_enabled', 'pickup_module_enabled'];
        const subKeys = [
            'credit_card_enabled', 'mobile_pay_enabled', 'card_pay_enabled',
            'scan_pay_esun_enabled', 'scan_pay_tappay_enabled', 'tappay_linepay', 'tappay_jkopay', 'tappay_pipay', 'tappay_pluspay', 'tappay_easywallet',
            'cash_bill_1000', 'cash_bill_500', 'cash_bill_100', 'cash_coin_50', 'cash_coin_10', 'cash_coin_5', 'cash_coin_1',
            'pickup_code_enabled', 'pass_code_enabled'
        ];
        const allMainChecked = mainKeys.every(k => this.machineSettings[k]);
        const targetValue = !allMainChecked;
        
        mainKeys.forEach(k => this.machineSettings[k] = targetValue);
        subKeys.forEach(k => this.machineSettings[k] = targetValue);
    },
    openMaintenanceQr(machine) {
        this.maintenanceQrMachineName = machine.name;
        const baseUrl = '{{ route('admin.maintenance.create', ['serial_no' => 'SERIAL_NO']) }}';
        this.maintenanceQrUrl = baseUrl.replace('SERIAL_NO', machine.serial_no);
        this.showMaintenanceQrModal = true;
    },
    openDetail(machine, id, serial) {
        this.currentMachine = machine;
        window.activeMachineId = id || machine?.id;
        window.activeMachineSerial = serial || machine?.serial_no;
        this.showDetailDrawer = true;
    },
    openPhotoModal(machine) {
        this.currentMachine = machine;
        this.selectedFiles = [null, null, null];
        this.deletedPhotos = [false, false, false];
        this.showPhotoModal = true;
    },
    handleFileChange(e) {
        this.selectedFileCount = e.target.files.length;
    },
    handlePhotoFileChange(e, index) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.selectedFiles[index] = e.target.result;
                this.deletedPhotos[index] = false;
            };
            reader.readAsDataURL(file);
        }
    },
    deletePhoto(index) {
        this.selectedFiles[index] = null;
        this.deletedPhotos[index] = true;
        // 同時要把 input file 清掉，避免雖然 marked deleted 但還帶著舊檔案
        const input = document.getElementsByName('machine_image_' + index)[0];
        if (input) input.value = '';
    },
    isDeleteConfirmOpen: false,
    deleteFormAction: '',
    confirmDelete(action) {
        this.deleteFormAction = action;
        this.isDeleteConfirmOpen = true;
    },
    // API Token Management
    showApiToken: false,
    loadingRegenerate: false,
    isRegenerateConfirmOpen: false,
    copyToken(machine) {
        if (!machine?.api_token) return;
        navigator.clipboard.writeText(machine.api_token).then(() => {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('API Token Copied') }}', type: 'success' } }));
        });
    },
    regenerateToken() {
        this.isRegenerateConfirmOpen = true;
    },
    executeRegeneration(id, serial) {
        // 僅使用機台序號 (Serial Number) 作為識別碼
        const targetSerial = serial || window.activeMachineSerial || id;
        
        if (!targetSerial) {
            console.error('ExecuteRegeneration failed: No serial number available');
            window.dispatchEvent(new CustomEvent('toast', { 
                detail: { message: '{{ __('Missing machine identification') }}', type: 'error' } 
            }));
            return;
        }

        console.log('ExecuteRegeneration using serial:', targetSerial);
        this.isRegenerateConfirmOpen = false;
        this.loadingRegenerate = true;
        
        fetch(`/admin/basic-settings/machines/${targetSerial}/regenerate-token`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        }).then(res => res.json()).then(data => {
            this.loadingRegenerate = false;
            if(data.success) {
                if (this.currentMachine) {
                    this.currentMachine.api_token = data.api_token;
                }
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'success' } }));
            }
        }).catch(() => {
            this.loadingRegenerate = false;
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Error processing request') }}', type: 'error' } }));
        });
    },
    // Permission Management
    showPermissionModal: false,
    isPermissionsLoading: false,
    targetUserId: null,
    targetUserName: '',
    allMachines: [],
    allMachinesCount: 0,
    permissions: {},
    openPermissionModal(user) {
        this.targetUserId = user.id;
        this.targetUserName = user.name;
        this.showPermissionModal = true;
        this.isPermissionsLoading = true;
        this.permissions = {};
        this.allMachines = [];
        this.permissionSearchQuery = '';

        fetch(`/admin/machines/permissions/accounts/${user.id}`)
            .then(res => res.json())
            .then(data => {
                if (data.machines) {
                    this.allMachines = data.machines;
                    this.allMachinesCount = data.machines.length;
                    const tempPermissions = {};
                    data.machines.forEach(m => {
                        tempPermissions[m.id] = (data.assigned_ids || []).includes(m.id);
                    });
                    this.permissions = tempPermissions;
                }
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Failed to load permissions') }}', type: 'error' } }));
            })
            .finally(() => {
                this.isPermissionsLoading = false;
            });
    },
    togglePermission(machineId) {
        this.permissions = { ...this.permissions, [machineId]: !this.permissions[machineId] };
    },
    toggleSelectAll() {
        const filtered = this.allMachines.filter(m => 
            !this.permissionSearchQuery || 
            m.name.toLowerCase().includes(this.permissionSearchQuery.toLowerCase()) || 
            m.serial_no.toLowerCase().includes(this.permissionSearchQuery.toLowerCase())
        );
        if (filtered.length === 0) return;
        const allSelected = filtered.every(m => this.permissions[m.id]);
        filtered.forEach(m => this.permissions[m.id] = !allSelected);
    },
    savePermissions() {
        const machineIds = Object.keys(this.permissions).filter(id => this.permissions[id]);
        
        fetch(`/admin/machines/permissions/accounts/${this.targetUserId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ machine_ids: machineIds })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.showPermissionModal = false;
                window.location.href = '{{ route('admin.basic-settings.machines.index', ['tab' => 'permissions']) }}';
            } else {
                throw new Error(data.error || 'Update failed');
            }
        })
        .catch(e => {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
        });
    },
    // === 搜尋/分頁 AJAX（僅在搜尋或換頁時觸發，Tab 切換不走此路） ===
    async searchInTab(tabName, extraQuery = '') {
        this.tabLoading = true;
        const searchMap = { machines: this.machineSearch, models: this.modelSearch, permissions: this.permissionSearch, system_settings: this.machineSearch, distribution: '' };
        const search = searchMap[tabName] || '';
        let qs = `tab=${tabName}&_ajax=1`;
        if (search) qs += `&search=${encodeURIComponent(search)}`;
        const machineCompanyId = (this.machineCompanyId || '').trim();
        const permissionCompanyId = (this.permissionCompanyId || '').trim();
        if ((tabName === 'machines' || tabName === 'system_settings') && machineCompanyId) qs += `&company_id=${machineCompanyId}`;
        if (tabName === 'permissions' && permissionCompanyId) qs += `&company_id=${permissionCompanyId}`;
        if (extraQuery) qs += extraQuery;

        // 同步 URL（不含 _ajax）
        const visibleQs = qs.replace(/&?_ajax=1/, '');
        history.pushState({}, '', `{{ route('admin.basic-settings.machines.index') }}?${visibleQs}`);

        try {
            const res = await fetch(
                `{{ route('admin.basic-settings.machines.index') }}?${qs}`,
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
            );
            const html = await res.text();
            const ref = this.$refs[tabName + 'Content'];
            if (ref) {
                ref.innerHTML = html;
                this.$nextTick(() => {
                    Alpine.initTree(ref);
                    this.bindPaginationLinks(ref, tabName);
                    if (window.HSStaticMethods) {
                        setTimeout(() => window.HSStaticMethods.autoInit(), 100);
                    }
                });
            }
        } catch(e) {
            console.error('Search failed:', e);
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Failed to load tab content') }}', type: 'error' } }));
        } finally {
            this.tabLoading = false;
        }
    },
    // 攔截分頁連結，改為 AJAX 請求
    bindPaginationLinks(container, tabName) {
        if (!container) return;
        container.querySelectorAll('a[href]').forEach(a => {
            const href = a.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            try {
                const url = new URL(href, window.location.origin);
                if (!url.searchParams.has('page') || a.closest('td.px-6')) return;
                a.addEventListener('click', (e) => {
                    if (a.title) return; // 排除 action 按鈕
                    e.preventDefault();
                    const page = url.searchParams.get('page') || 1;
                    const perPage = url.searchParams.get('per_page') || '';
                    let extra = `&page=${page}`;
                    if (perPage) extra += `&per_page=${perPage}`;
                    this.searchInTab(tabName, extra);
                });
            } catch(err) {}
        });
        // 攔截分頁 <select> (快速跳頁 & 每頁筆數)
        container.querySelectorAll('select[onchange]').forEach(sel => {
            const origOnchange = sel.getAttribute('onchange');
            sel.removeAttribute('onchange');
            sel.addEventListener('change', () => {
                const val = sel.value;
                try {
                    if (val.startsWith('http') || val.startsWith('/')) {
                        const url = new URL(val, window.location.origin);
                        const page = url.searchParams.get('page') || 1;
                        const perPage = url.searchParams.get('per_page') || '';
                        let extra = `&page=${page}`;
                        if (perPage) extra += `&per_page=${perPage}`;
                        this.searchInTab(tabName, extra);
                    } else if (origOnchange && origOnchange.includes('per_page')) {
                        this.searchInTab(tabName, `&per_page=${val}`);
                    }
                } catch(err) {
                    if (origOnchange) new Function(origOnchange).call(sel);
                }
            });
        });
    },
    init() {
        // 觸發頂部進度條
        // Sync top loading bar (Removed for tab/pagination to reduce visual noise as requested)
        /*
        this.$watch('tabLoading', (val) => {
            const bar = document.getElementById('top-loading-bar');
            if (bar) {
                if (val) bar.classList.add('loading');
                else bar.classList.remove('loading');
            }
        });
        */
        // 首次載入時綁定每個 Tab 的分頁連結
        this.$nextTick(() => {
            ['machines', 'models', 'permissions', 'system_settings'].forEach(t => {
                const ref = this.$refs[t + 'Content'];
                if (ref) this.bindPaginationLinks(ref, t);
            });
        });
        // Tab 切換時同步 URL
        this.$watch('tab', (newTab) => {
            history.pushState({}, '', `{{ route('admin.basic-settings.machines.index') }}?tab=${newTab}`);
            window.dispatchEvent(new CustomEvent('tab-changed', { detail: { tab: newTab } }));
        });
        // 瀏覽器上一頁/下一頁
        window.addEventListener('popstate', () => {
            const url = new URL(window.location.href);
            this.tab = url.searchParams.get('tab') || 'machines';
        });
    }
}" @execute-regenerate.window="executeRegeneration($event.detail)">

    <!-- Machine System Settings Modal -->
    <template x-teleport="body">
        <div x-show="showMachineSettingsModal" class="fixed inset-0 z-[200] flex items-center justify-center" x-cloak>
            <div x-show="showMachineSettingsModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"
                @click="showMachineSettingsModal = false"></div>

            <div x-show="showMachineSettingsModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 w-full max-w-2xl mx-4 overflow-hidden z-[201] flex flex-col max-h-[90vh]">

                <div
                    class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight font-display">{{
                            __('Edit Machine System Settings') }}</h3>
                        <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest"
                            x-text="currentMachine?.name + ' (' + currentMachine?.serial_no + ')'"></p>
                    </div>
                    <button type="button" @click="showMachineSettingsModal = false"
                        class="text-slate-400 hover:text-slate-500 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl p-2 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-white dark:bg-slate-900">
                    <form @submit.prevent="submitMachineSettings()">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-3">
                            <!-- 購物方式 (單選) -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <div class="md:col-span-1">
                                    <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Shopping Mode') }}</h4>
                                </div>
                                <div class="md:col-span-3 flex flex-wrap gap-x-6 gap-y-2">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="radio" name="settings[shopping_mode]" value="basic" x-model="machineSettings.shopping_mode" class="w-4 h-4 text-cyan-500 border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Basic Mode') }}</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="radio" name="settings[shopping_mode]" value="employee_card" x-model="machineSettings.shopping_mode" class="w-4 h-4 text-cyan-500 border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Staff Card') }}</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="radio" name="settings[shopping_mode]" value="pickup_sheet" x-model="machineSettings.shopping_mode" class="w-4 h-4 text-cyan-500 border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Pickup Sheet (Material No.)') }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 取物單模式專屬：領藥單開關 (toggle switch，比照會員系統/稅務系統) -->
                            <div x-show="machineSettings.shopping_mode === 'pickup_sheet'" x-transition class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 border-l-4 border-l-emerald-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <div class="md:col-span-1">
                                    <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Pharmacy Pickup (Rx)') }}</h4>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ __('Issue pharmacy pickup orders from the backend; patient scans QR to dispense.') }}</p>
                                </div>
                                <div class="md:col-span-3 flex flex-wrap gap-x-8 gap-y-4">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative inline-flex items-center">
                                            <input type="hidden" name="settings[pharmacy_pickup_enabled]" value="0">
                                            <input type="checkbox" name="settings[pharmacy_pickup_enabled]" value="1"
                                                x-model="machineSettings.pharmacy_pickup_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Pharmacy Pickup (Rx)') }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 零售/金流展開部分 (僅基礎版顯示) -->
                            <div x-show="machineSettings.shopping_mode === 'basic'" x-transition class="space-y-3 rounded-2xl border border-slate-200 dark:border-slate-700/60 border-l-4 border-l-cyan-500 bg-slate-50/40 dark:bg-slate-800/20 p-4">

                                <!-- 基礎版專屬區塊標籤 -->
                                <div class="flex items-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 text-[11px] font-black uppercase tracking-wider">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        {{ __('Basic Mode Features') }}
                                    </span>
                                </div>

                                <!-- 一鍵全選/取消全選所有金流 -->
                                <div class="flex justify-between items-center px-4 py-3 rounded-xl bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                                    <span class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Payment Type') }}</span>
                                    <button type="button" @click="toggleAllPayments()" class="text-xs font-bold text-cyan-500 hover:text-cyan-600 transition-colors uppercase tracking-wider">
                                        {{ __('Select All / Deselect All') }}
                                    </button>
                                </div>

                                <!-- 刷卡機支付 -->
                                <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Includes Credit Card/Mobile Pay') }}</h4>
                                            <button type="button" x-show="machineSettings.card_terminal_enabled" x-transition @click="toggleAllCardTerminal()" class="text-[10px] font-bold text-cyan-500 hover:text-cyan-600 transition-colors uppercase tracking-wider">
                                                {{ __('Select All / Deselect All') }}
                                            </button>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[card_terminal_enabled]" value="0">
                                            <input type="checkbox" name="settings[card_terminal_enabled]" value="1"
                                                x-model="machineSettings.card_terminal_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </label>
                                    </div>
                                    
                                    <!-- 刷卡機子項目 -->
                                    <div x-show="machineSettings.card_terminal_enabled" x-transition class="pl-4 border-l-2 border-slate-200 dark:border-slate-700 space-y-3">
                                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[credit_card_enabled]" value="0">
                                                <input type="checkbox" name="settings[credit_card_enabled]" value="1" x-model="machineSettings.credit_card_enabled" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('Credit Card Payment') }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[mobile_pay_enabled]" value="0">
                                                <input type="checkbox" name="settings[mobile_pay_enabled]" value="1" x-model="machineSettings.mobile_pay_enabled" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('Mobile Payment') }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[card_pay_enabled]" value="0">
                                                <input type="checkbox" name="settings[card_pay_enabled]" value="1" x-model="machineSettings.card_pay_enabled" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('Card Payment') }}</span>
                                            </label>
                                        </div>
                                        
                                    </div>
                                </div>

                                <!-- 掃碼支付 -->
                                <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Scan Payment') }}</h4>
                                            <button type="button" x-show="machineSettings.scan_pay_enabled" x-transition @click="toggleAllScanPay()" class="text-[10px] font-bold text-cyan-500 hover:text-cyan-600 transition-colors uppercase tracking-wider">
                                                {{ __('Select All / Deselect All') }}
                                            </button>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[scan_pay_enabled]" value="0">
                                            <input type="checkbox" name="settings[scan_pay_enabled]" value="1"
                                                x-model="machineSettings.scan_pay_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </label>
                                    </div>
                                    
                                    <!-- 掃碼子項目 -->
                                    <div x-show="machineSettings.scan_pay_enabled" x-transition class="pl-4 border-l-2 border-slate-200 dark:border-slate-700 space-y-3">
                                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[scan_pay_esun_enabled]" value="0">
                                                <input type="checkbox" name="settings[scan_pay_esun_enabled]" value="1" x-model="machineSettings.scan_pay_esun_enabled" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('E.SUN Pay') }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[scan_pay_tappay_enabled]" value="0">
                                                <input type="checkbox" name="settings[scan_pay_tappay_enabled]" value="1" x-model="machineSettings.scan_pay_tappay_enabled" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('TapPay') }}</span>
                                            </label>
                                        </div>
                                        
                                        <!-- TapPay 孫項目 -->
                                        <div x-show="machineSettings.scan_pay_tappay_enabled && machineSettings.scan_pay_enabled" x-transition class="pl-4 border-l border-dashed border-slate-200 dark:border-slate-700 flex flex-wrap gap-x-6 gap-y-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[tappay_linepay]" value="0">
                                                <input type="checkbox" name="settings[tappay_linepay]" value="1" x-model="machineSettings.tappay_linepay" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('Line Pay') }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[tappay_jkopay]" value="0">
                                                <input type="checkbox" name="settings[tappay_jkopay]" value="1" x-model="machineSettings.tappay_jkopay" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('JKOPAY') }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[tappay_pipay]" value="0">
                                                <input type="checkbox" name="settings[tappay_pipay]" value="1" x-model="machineSettings.tappay_pipay" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('Pi Wallet') }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[tappay_pluspay]" value="0">
                                                <input type="checkbox" name="settings[tappay_pluspay]" value="1" x-model="machineSettings.tappay_pluspay" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('PlusPay') }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="hidden" name="settings[tappay_easywallet]" value="0">
                                                <input type="checkbox" name="settings[tappay_easywallet]" value="1" x-model="machineSettings.tappay_easywallet" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('EasyWallet') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Line 官方支付 (LINE Pay 官方直連) -->
                                <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('LINE Official Pay') }}</h4>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[scan_pay_linepay_enabled]" value="0">
                                            <input type="checkbox" name="settings[scan_pay_linepay_enabled]" value="1"
                                                x-model="machineSettings.scan_pay_linepay_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- 現金支付 -->
                                <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Cash Payment') }}</h4>
                                            <button type="button" x-show="machineSettings.cash_module_enabled" x-transition @click="toggleAllCash()" class="text-[10px] font-bold text-cyan-500 hover:text-cyan-600 transition-colors uppercase tracking-wider">
                                                {{ __('Select All / Deselect All') }}
                                            </button>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[cash_module_enabled]" value="0">
                                            <input type="checkbox" name="settings[cash_module_enabled]" value="1"
                                                x-model="machineSettings.cash_module_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </label>
                                    </div>
                                    
                                    <!-- 現金面額子項目 -->
                                    <div x-show="machineSettings.cash_module_enabled" x-transition class="pl-4 border-l-2 border-slate-200 dark:border-slate-700 space-y-3">
                                        <div>
                                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">{{ __('Bill Denominations') }}</span>
                                            <div class="flex flex-wrap gap-x-6 gap-y-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="hidden" name="settings[cash_bill_1000]" value="0">
                                                    <input type="checkbox" name="settings[cash_bill_1000]" value="1" x-model="machineSettings.cash_bill_1000" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">1000</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="hidden" name="settings[cash_bill_500]" value="0">
                                                    <input type="checkbox" name="settings[cash_bill_500]" value="1" x-model="machineSettings.cash_bill_500" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">500</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="hidden" name="settings[cash_bill_100]" value="0">
                                                    <input type="checkbox" name="settings[cash_bill_100]" value="1" x-model="machineSettings.cash_bill_100" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">100</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="pt-2">
                                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">{{ __('Coin Denominations') }}</span>
                                            <div class="flex flex-wrap gap-x-6 gap-y-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="hidden" name="settings[cash_coin_50]" value="0">
                                                    <input type="checkbox" name="settings[cash_coin_50]" value="1" x-model="machineSettings.cash_coin_50" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">50</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="hidden" name="settings[cash_coin_10]" value="0">
                                                    <input type="checkbox" name="settings[cash_coin_10]" value="1" x-model="machineSettings.cash_coin_10" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">10</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="hidden" name="settings[cash_coin_5]" value="0">
                                                    <input type="checkbox" name="settings[cash_coin_5]" value="1" x-model="machineSettings.cash_coin_5" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">5</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="hidden" name="settings[cash_coin_1]" value="0">
                                                    <input type="checkbox" name="settings[cash_coin_1]" value="1" x-model="machineSettings.cash_coin_1" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">1</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 取貨模組 -->
                                <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Pickup Module') }}</h4>
                                            <button type="button" x-show="machineSettings.pickup_module_enabled" x-transition @click="toggleAllPickup()" class="text-[10px] font-bold text-cyan-500 hover:text-cyan-600 transition-colors uppercase tracking-wider">
                                                {{ __('Select All / Deselect All') }}
                                            </button>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[pickup_module_enabled]" value="0">
                                            <input type="checkbox" name="settings[pickup_module_enabled]" value="1"
                                                x-model="machineSettings.pickup_module_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </label>
                                    </div>
                                    <div x-show="machineSettings.pickup_module_enabled" x-transition class="pl-4 border-l-2 border-slate-200 dark:border-slate-700 flex flex-wrap gap-x-6 gap-y-2">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="hidden" name="settings[pickup_code_enabled]" value="0">
                                            <input type="checkbox" name="settings[pickup_code_enabled]" value="1" x-model="machineSettings.pickup_code_enabled" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('Pickup Code') }}</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="hidden" name="settings[pass_code_enabled]" value="0">
                                            <input type="checkbox" name="settings[pass_code_enabled]" value="1" x-model="machineSettings.pass_code_enabled" class="w-4 h-4 text-cyan-500 rounded border-slate-300 focus:ring-cyan-500 dark:bg-slate-800 dark:border-slate-700 dark:checked:bg-cyan-500 dark:checked:border-cyan-500 dark:focus:border-cyan-500">
                                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('Passcode') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- 購物車功能 -->
                                <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Shopping Cart') }}</h4>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[shopping_cart_enabled]" value="0">
                                            <input type="checkbox" name="settings[shopping_cart_enabled]" value="1"
                                                x-model="machineSettings.shopping_cart_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- 來店禮功能 -->
                                <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Welcome Gift') }}</h4>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="settings[welcome_gift_enabled]" value="0">
                                            <input type="checkbox" name="settings[welcome_gift_enabled]" value="1"
                                                x-model="machineSettings.welcome_gift_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- 會員系統功能 -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <div class="md:col-span-1">
                                    <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Member System') }}</h4>
                                </div>
                                <div class="md:col-span-3 flex flex-wrap gap-x-8 gap-y-4">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative inline-flex items-center">
                                            <input type="hidden" name="settings[member_system_enabled]" value="0">
                                            <input type="checkbox" name="settings[member_system_enabled]" value="1"
                                                x-model="machineSettings.member_system_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Member System') }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 稅務系統 (電子發票) -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <div class="md:col-span-1">
                                    <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Tax System') }}</h4>
                                </div>
                                <div class="md:col-span-3 flex flex-wrap gap-x-8 gap-y-4">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative inline-flex items-center">
                                            <input type="hidden" name="settings[tax_invoice_enabled]" value="0">
                                            <input type="checkbox" name="settings[tax_invoice_enabled]" value="1"
                                                x-model="machineSettings.tax_invoice_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Electronic Invoice') }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 硬體周邊設定 -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <div class="md:col-span-1">
                                    <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Hardware Peripheral Settings') }}</h4>
                                </div>
                                <div class="md:col-span-3 flex flex-wrap gap-x-8 gap-y-4">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative inline-flex items-center">
                                            <input type="hidden" name="settings[ambient_temp_monitoring_enabled]" value="0">
                                            <input type="checkbox" name="settings[ambient_temp_monitoring_enabled]" value="1"
                                                x-model="machineSettings.ambient_temp_monitoring_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ __('Ambient Temperature Monitoring') }}</span>
                                    </label>
                                </div>
                            </div>
                            <!-- 副櫃系統（格子櫃功能授權；機台端據此顯示副櫃分頁/鎖控板設定/貨道管理） -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <div class="md:col-span-1">
                                    <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">副櫃系統</h4>
                                </div>
                                <div class="md:col-span-3 flex flex-wrap gap-x-8 gap-y-4">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative inline-flex items-center">
                                            <input type="hidden" name="settings[subcabinet_enabled]" value="0">
                                            <input type="checkbox" name="settings[subcabinet_enabled]" value="1"
                                                x-model="machineSettings.subcabinet_enabled" class="peer sr-only">
                                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">格子櫃功能</span>
                                    </label>
                                </div>
                            </div>

                            @if(auth()->user()->isSystemAdmin())
                            <!-- 顯示語系（最多 N 種，第一個為預設）。僅系統管理員可設定。 -->
                            <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('Display Languages') }}</h4>
                                    <span class="text-[11px] font-bold text-slate-400"
                                          x-text="`${(machineSettings.languages || []).length}/${maxLanguages}`"></span>
                                </div>
                                <p class="text-[11px] font-medium text-slate-400 mb-3">{{ __('Select up to :max languages the machine can switch between. The first one is the default.', ['max' => (int) config('locales.max_per_machine', 5)]) }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="[loc, label] in Object.entries(localeWhitelist)" :key="loc">
                                        <button type="button" @click="toggleLanguage(loc)"
                                            :disabled="!(machineSettings.languages || []).includes(loc) && (machineSettings.languages || []).length >= maxLanguages"
                                            :class="(machineSettings.languages || []).includes(loc)
                                                ? 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-300 border-cyan-500/40 shadow-sm'
                                                : 'bg-white dark:bg-slate-900 text-slate-500 border-slate-200 dark:border-slate-700 hover:text-slate-800 dark:hover:text-slate-200 disabled:opacity-40 disabled:cursor-not-allowed'"
                                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-xs font-black tracking-wide transition-all">
                                            <span x-text="label"></span>
                                            <span x-show="(machineSettings.languages || [])[0] === loc"
                                                  class="px-1.5 py-0.5 rounded bg-cyan-500/20 text-cyan-600 dark:text-cyan-300 text-[9px] uppercase tracking-widest">{{ __('Default') }}</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div
                            class="flex justify-end gap-x-4 pt-8 mt-10 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showMachineSettingsModal = false"
                                class="btn-luxury-ghost px-8">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn-luxury-primary px-12" :disabled="isUpdatingSetting">
                                <span x-show="!isUpdatingSetting">{{ __('Update Settings') }}</span>
                                <span x-show="isUpdatingSetting" class="flex items-center gap-2">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ __('Updating...') }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- 1. Header Area -->
    <div class="flex items-start justify-between gap-4 mb-2">
        <div class="flex flex-col gap-1 min-w-0">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <h1
                    class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white tracking-tight font-display truncate">
                    {{ __('Machine Settings') }}</h1>
                <a href="{{ route('machines.distribution') }}" target="_blank"
                    class="flex items-center gap-2 px-1 group transition-all pt-1">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-slate-400 dark:text-white/40 group-hover:text-cyan-500 dark:group-hover:text-white transition-colors shrink-0"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 6.75V15m6-10.5v.106c0 .707-.555 1.296-1.244 1.333a5.232 5.232 0 0 0-4.834 4.834C8.889 11.418 8.299 12 7.591 12H7.5m0 0v7.5m0-7.5h.75m.75 0h.375c.49 0 .959.122 1.374.339a5.251 5.251 0 0 1 2.625 4.547v.105c0 .708.555 1.296 1.244 1.333a5.232 5.232 0 0 0 4.834-4.834c.038-.689.627-1.279 1.335-1.279h.75M15 6.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 18.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                    </svg>
                    <span
                        class="text-sm sm:text-base font-black text-slate-500 dark:text-white/60 group-hover:text-slate-700 dark:group-hover:text-white transition-colors truncate">{{
                        __('Machine Distribution') }}</span>
                </a>
            </div>
            <p
                class="text-[11px] sm:text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest truncate">
                {{ __('Management of operational parameters and models') }}</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <button x-show="tab === 'machines'" x-cloak @click="showCreateMachineModal = true"
                class="btn-luxury-primary flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 h-10 sm:h-auto rounded-xl shadow-lg shadow-cyan-500/20 transition-all duration-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="font-black text-sm sm:text-base tracking-tight">{{ __('Add Machine') }}</span>
            </button>
            <button x-show="tab === 'models'" x-cloak @click="newModel = { name: '', temp_upper_limit: '', temp_lower_limit: '' }; showCreateModelModal = true"
                class="btn-luxury-primary flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 h-10 sm:h-auto rounded-xl shadow-lg shadow-cyan-500/20 transition-all duration-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="font-black text-sm sm:text-base tracking-tight">{{ __('Add Model') }}</span>
            </button>
        </div>
    </div>

    <x-tab-nav model="tab">
        <x-tab-nav-item value="machines" :label="__('Machines')" model="tab" />
        <x-tab-nav-item value="models" :label="__('Models')" model="tab" />
        <x-tab-nav-item value="permissions" :label="__('Machine Permissions')" model="tab" />
        <x-tab-nav-item value="system_settings" :label="__('Machine System Settings')" model="tab" />
    </x-tab-nav>

    <!-- 2. Main Content Card -->
    <div class="luxury-card rounded-3xl p-6 sm:p-8 animate-luxury-in mt-6 relative overflow-hidden">
        <x-luxury-spinner show="tabLoading" z-index="z-20" />

        <div
            :class="tabLoading ? 'opacity-30 pointer-events-none transition-opacity duration-300' : 'transition-opacity duration-300'">
            <!-- Machines Tab -->
            <div x-show="tab === 'machines'" x-cloak>
                <div x-ref="machinesContent">
                    @include('admin.basic-settings.machines.partials.tab-machines')
                </div>
            </div>

            <!-- Models Tab -->
            <div x-show="tab === 'models'" x-cloak>
                <div x-ref="modelsContent">
                    @include('admin.basic-settings.machines.partials.tab-models')
                </div>
            </div>

            <!-- Permissions Tab -->
            <div x-show="tab === 'permissions'" x-cloak>
                <div x-ref="permissionsContent">
                    @include('admin.basic-settings.machines.partials.tab-permissions')
                </div>
            </div>

            <div x-show="tab === 'system_settings'" x-cloak>
                <div x-ref="system_settingsContent">
                    @include('admin.basic-settings.machines.partials.tab-system-settings')
                </div>
            </div>
        </div>
    </div>

    <!-- Modals & Drawers -->

    <!-- Sync System Settings Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="showSyncSettingsModal" class="fixed inset-0 z-[210] overflow-y-auto" x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                {{-- Backdrop --}}
                <div x-show="showSyncSettingsModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="showSyncSettingsModal = false"></div>

                {{-- Modal Content --}}
                <div x-show="showSyncSettingsModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-visible rounded-[2.5rem] bg-white dark:bg-slate-900 p-8 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200 dark:border-slate-800">

                    {{-- Header --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 border border-amber-500/20">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase">{{ __('Command Confirmation') }}</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ __('Please confirm the details below') }}</p>
                        </div>
                    </div>

                    <div class="space-y-4 bg-slate-50 dark:bg-slate-950/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800/50 mb-8">
                        {{-- Command Type --}}
                        <div class="flex justify-between items-center px-1">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('Command Type') }}</span>
                            <span class="text-sm font-black text-slate-800 dark:text-slate-200">{{ __('Sync Settings') }}</span>
                        </div>

                        {{-- Target Machine：列表進入時鎖定顯示；頂部按鈕進入時可搜尋選擇 --}}
                        <div class="px-1 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                            <span class="text-[10px] font-black text-cyan-500 uppercase tracking-widest block mb-2">{{ __('Target Machine') }}</span>

                            {{-- 已鎖定 (從列表列開啟) --}}
                            <template x-if="syncSettingsMachineLocked">
                                <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <svg class="w-4 h-4 text-cyan-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm font-black text-slate-800 dark:text-slate-200 truncate" x-text="syncSelectedMachineLabel()"></span>
                                </div>
                            </template>

                            {{-- 可選擇 (從頂部按鈕開啟) --}}
                            <template x-if="!syncSettingsMachineLocked">
                                <div class="relative" @click.outside="syncMachineDropdownOpen = false">
                                    <button type="button" @click="syncMachineDropdownOpen = !syncMachineDropdownOpen"
                                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-cyan-500/50 transition-all text-left">
                                        <span class="text-sm font-bold truncate"
                                            :class="syncSettingsMachineId ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400'"
                                            x-text="syncSettingsMachineId ? syncSelectedMachineLabel() : '{{ __('Search Machine...') }}'"></span>
                                        <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="{ 'rotate-180': syncMachineDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <div x-show="syncMachineDropdownOpen" x-transition x-cloak
                                        class="absolute z-[20] mt-2 w-full rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
                                        <div class="p-3 border-b border-slate-100 dark:border-slate-800">
                                            <input type="text" x-model="syncMachineSearch" @click.stop
                                                placeholder="{{ __('Search machine name or code...') }}"
                                                class="luxury-input w-full text-sm py-2.5 px-4">
                                        </div>
                                        <div class="max-h-60 overflow-y-auto py-2">
                                            <template x-for="m in filteredSyncMachines" :key="m.id">
                                                <button type="button" @click="selectSyncMachine(m.id)"
                                                    class="w-full text-left px-4 py-3 hover:bg-cyan-500/5 transition-colors flex items-center justify-between gap-3"
                                                    :class="{ 'bg-cyan-500/10': syncSettingsMachineId == m.id }">
                                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300 truncate" x-text="m.name"></span>
                                                    <span class="text-[11px] font-mono font-bold text-slate-400 uppercase tracking-widest shrink-0" x-text="m.serial_no"></span>
                                                </button>
                                            </template>
                                            <div x-show="filteredSyncMachines.length === 0" class="px-4 py-6 text-center text-xs font-bold text-slate-400">
                                                {{ __('No machines found') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Operation Note --}}
                        <div class="space-y-2 px-1 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                            <label class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] ml-1">{{ __('Operation Note') }}</label>
                            <textarea x-model="syncSettingsNote"
                                class="luxury-input w-full min-h-[90px] text-sm py-3 px-4 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus:border-cyan-500/50 rounded-2xl"
                                placeholder="{{ __('Reason for this command...') }}"></textarea>
                        </div>
                    </div>

                    {{-- 提醒：機台收到後將主動回抓最新系統設定 --}}
                    <div class="flex items-start gap-3 p-4 mb-6 bg-cyan-500/5 border border-cyan-500/15 rounded-2xl">
                        <svg class="w-5 h-5 text-cyan-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 leading-relaxed">
                            {{ __('The machine will be notified to fetch the latest system settings. It may take a moment to apply.') }}
                        </p>
                    </div>

                    <div class="flex gap-4">
                        <button @click="showSyncSettingsModal = false"
                            class="flex-1 px-6 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="executeSyncSettings()" :disabled="isSyncingSettings"
                            class="flex-1 px-6 py-4 rounded-2xl bg-cyan-600 text-white text-xs font-black uppercase tracking-widest hover:bg-cyan-500 shadow-lg shadow-cyan-500/20 active:scale-[0.98] transition-all disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <svg x-show="isSyncingSettings" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span x-text="isSyncingSettings ? '{{ __('Sending...') }}' : '{{ __('Execute') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- 1. Create Machine Modal -->
    <template x-teleport="body">
        <div x-show="showCreateMachineModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true"
                    @click="showCreateMachineModal = false">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-visible shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-luxury-in border border-slate-100 dark:border-slate-800">
                    <div
                        class="px-8 pt-8 pb-6 border-b border-slate-50 dark:border-slate-800/50 flex justify-between items-center">
                        <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{
                            __('Add Machine') }}</h3>
                        <button @click="showCreateMachineModal = false"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form action="{{ route('admin.basic-settings.machines.store') }}" method="POST"
                        enctype="multipart/form-data" @submit.prevent="
                        if(!$el.name.value.trim()){ window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Enter machine name') }}', type: 'error' } })); return; }
                        if(!$el.serial_no.value.trim()){ window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Enter serial number') }}', type: 'error' } })); return; }
                        if(!$el.machine_model_id.value.trim()){ window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __('Please select a machine model') }}', type: 'error' } })); return; }
                        $el.submit()
                    ">
                        @csrf
                        <div class="px-8 py-8 space-y-6">
                            <div>
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                    {{ __('Machine Name') }} <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="name" class="luxury-input w-full"
                                    placeholder="{{ __('Enter machine name') }}">
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                    {{ __('Serial No') }} <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="serial_no" class="luxury-input w-full"
                                    placeholder="{{ __('Enter serial number') }}">
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                    {{ __('Location') }}
                                </label>
                                <input type="text" name="location" class="luxury-input w-full"
                                    placeholder="{{ __('Enter machine location') }}">
                            </div>

                            <div>
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                    {{ __('Key No') }}
                                </label>
                                <input type="text" name="key_no" class="luxury-input w-full"
                                    placeholder="{{ __('Enter key number') }}">
                            </div>

                            <div class="relative focus-within:z-[60]">
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                    {{ __('Model') }} <span class="text-rose-500">*</span>
                                </label>
                                <x-searchable-select name="machine_model_id" :placeholder="__('Select Model')">
                                    @foreach($models as $model)
                                    <option value="{{ $model->id }}">{{ $model->name }}</option>
                                    @endforeach
                                </x-searchable-select>
                            </div>

                            <div class="relative focus-within:z-[60]">
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                    {{ __('Owner') }}
                                </label>
                                <x-searchable-select name="company_id" :placeholder="__('Select Owner')">
                                    @foreach($companies as $company)
                                    <option value="{{ $company->id }}"
                                        data-title="{{ $company->name }}{{ $company->code ? ' (' . $company->code . ')' : '' }}">
                                        {{ $company->name }}{{ $company->code ? ' (' . $company->code . ')' : '' }}
                                    </option>
                                    @endforeach
                                </x-searchable-select>
                            </div>


                            <div>
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                    {{ __('Machine Images') }} ({{ __('Max 3') }})
                                </label>
                                <label
                                    class="relative flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl cursor-pointer bg-slate-50/50 dark:bg-slate-900/50 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all group">
                                    <template x-if="selectedFileCount === 0">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-8 h-8 mb-3 text-slate-400 group-hover:text-cyan-500 transition-colors"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            <p
                                                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                                                {{ __('Click to upload') }}</p>
                                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mt-1">
                                                {{ __('PNG, JPG, WEBP up to 10MB') }} ({{ __('Max 3') }})
                                            </p>
                                        </div>
                                    </template>
                                    <template x-if="selectedFileCount > 0">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <div
                                                class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 mb-2">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                            <p class="text-xs font-black text-emerald-500 uppercase tracking-widest"
                                                x-text="`${selectedFileCount} {{ __('files selected') }}`"></p>
                                        </div>
                                    </template>
                                    <input type="file" name="images[]" multiple accept="image/*" class="hidden"
                                        @change="handleFileChange">
                                </label>
                            </div>
                        </div>
                        <div
                            class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showCreateMachineModal = false" class="btn-luxury-ghost">{{
                                __('Cancel') }}</button>
                            <button type="submit" class="btn-luxury-primary px-8">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- 2. Create Model Modal -->
    <template x-teleport="body">
        <div x-show="showCreateModelModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showCreateModelModal = false">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-visible shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-luxury-in border border-slate-100 dark:border-slate-800">
                    <div
                        class="px-8 pt-8 pb-6 border-b border-slate-50 dark:border-slate-800/50 flex justify-between items-center">
                        <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{
                            __('Add Machine Model') }}</h3>
                        <button @click="showCreateModelModal = false"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form action="{{ route('admin.basic-settings.machine-models.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="redirect_to"
                            value="{{ route('admin.basic-settings.machines.index', ['tab' => 'models']) }}">
                        <div class="px-8 py-8 space-y-6">
                            <div>
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{
                                    __('Model Name') }}</label>
                                <input type="text" name="name" x-model="newModel.name" required class="luxury-input w-full"
                                    placeholder="{{ __('Enter model name') }}">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                        {{ __('Default Temp Alert Upper Limit (°C)') }}
                                    </label>
                                    <div class="flex items-center h-12 rounded-xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all w-full">
                                        <button type="button" @click="adjustModelTemp(newModel, 'temp_upper_limit', -1, '40')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <input type="text" name="temp_upper_limit" x-model="newModel.temp_upper_limit"
                                            placeholder="40"
                                            class="w-full bg-transparent border-none text-center text-sm font-bold text-slate-800 dark:text-white focus:ring-0 p-0">
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <button type="button" @click="adjustModelTemp(newModel, 'temp_upper_limit', 1, '40')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                        {{ __('Default Temp Alert Lower Limit (°C)') }}
                                    </label>
                                    <div class="flex items-center h-12 rounded-xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all w-full">
                                        <button type="button" @click="adjustModelTemp(newModel, 'temp_lower_limit', -1, '0')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <input type="text" name="temp_lower_limit" x-model="newModel.temp_lower_limit"
                                            placeholder="0"
                                            class="w-full bg-transparent border-none text-center text-sm font-bold text-slate-800 dark:text-white focus:ring-0 p-0">
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <button type="button" @click="adjustModelTemp(newModel, 'temp_lower_limit', 1, '0')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <div
                            class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showCreateModelModal = false" class="btn-luxury-ghost">{{
                                __('Cancel') }}</button>
                            <button type="submit" class="btn-luxury-primary px-8">{{ __('Create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- 3. Edit Model Modal -->
    <template x-teleport="body">
        <div x-show="showEditModelModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showEditModelModal = false">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-luxury-in border border-slate-100 dark:border-slate-800">
                    <div
                        class="px-8 pt-8 pb-6 border-b border-slate-50 dark:border-slate-800/50 flex justify-between items-center">
                        <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight font-display">{{
                            __('Edit Machine Model') }}</h3>
                        <button @click="showEditModelModal = false"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form :action="modelActionUrl" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_to"
                            value="{{ route('admin.basic-settings.machines.index', ['tab' => 'models']) }}">
                        <div class="px-8 py-8 space-y-6">
                            <div>
                                <label
                                    class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{
                                    __('Model Name') }}</label>
                                <input type="text" name="name" x-model="currentModel.name" required
                                    class="luxury-input w-full" placeholder="{{ __('Enter model name') }}">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                        {{ __('Default Temp Alert Upper Limit (°C)') }}
                                    </label>
                                    <div class="flex items-center h-12 rounded-xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all w-full">
                                        <button type="button" @click="adjustModelTemp(currentModel, 'temp_upper_limit', -1, '40')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <input type="text" name="temp_upper_limit" x-model="currentModel.temp_upper_limit"
                                            placeholder="40"
                                            class="w-full bg-transparent border-none text-center text-sm font-bold text-slate-800 dark:text-white focus:ring-0 p-0">
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <button type="button" @click="adjustModelTemp(currentModel, 'temp_upper_limit', 1, '40')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                                        {{ __('Default Temp Alert Lower Limit (°C)') }}
                                    </label>
                                    <div class="flex items-center h-12 rounded-xl border border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden group focus-within:ring-2 focus-within:ring-cyan-500/20 transition-all w-full">
                                        <button type="button" @click="adjustModelTemp(currentModel, 'temp_lower_limit', -1, '0')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        </button>
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <input type="text" name="temp_lower_limit" x-model="currentModel.temp_lower_limit"
                                            placeholder="0"
                                            class="w-full bg-transparent border-none text-center text-sm font-bold text-slate-800 dark:text-white focus:ring-0 p-0">
                                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-700/50"></div>
                                        <button type="button" @click="adjustModelTemp(currentModel, 'temp_lower_limit', 1, '0')" 
                                            class="shrink-0 w-12 h-full flex items-center justify-center text-slate-400 hover:text-cyan-500 hover:bg-cyan-500/5 active:scale-90 transition-all">
                                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showEditModelModal = false" class="btn-luxury-ghost">{{
                                __('Cancel') }}</button>
                            <button type="submit" class="btn-luxury-primary px-8">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- 4. Machine Photo Management Modal -->
    <template x-teleport="body">
        <div x-show="showPhotoModal" class="fixed inset-0 z-[150]" x-cloak>
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" x-show="showPhotoModal"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                @click="showPhotoModal = false">
            </div>

            <div
                class="fixed inset-0 z-[160] overflow-y-auto pointer-events-none p-4 flex items-center justify-center min-h-screen">
                <div
                    class="w-full max-w-2xl max-h-[calc(100vh-2rem)] flex flex-col bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 pointer-events-auto overflow-hidden animate-luxury-in">
                    <div
                        class="px-6 py-5 md:px-8 md:py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900 shrink-0">
                        <div>
                            <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight">{{ __('Machine Images') }}</h2>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1"
                                x-text="currentMachine?.name"></p>
                        </div>
                        <button @click="showPhotoModal = false" type="button"
                            class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form
                        :action="'{{ route('admin.basic-settings.machines.photos.update', ':id') }}'.replace(':id', currentMachine?.id)"
                        method="POST" enctype="multipart/form-data" class="flex flex-col min-h-0">
                        @csrf
                        @method('PATCH')

                        <div class="p-6 md:p-8 space-y-6 md:space-y-8 overflow-y-auto custom-scrollbar">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                                <template x-for="i in [0, 1, 2]" :key="i">
                                    <div class="space-y-3">
                                        <label
                                            class="block text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]"
                                            x-text="'{{ __('Photo Slot') }} ' + (i + 1)"></label>

                                        <div class="relative group aspect-square rounded-[2rem] overflow-hidden border-2 border-dashed border-slate-200 dark:border-slate-800 hover:border-emerald-500/50 transition-all bg-slate-50/50 dark:bg-slate-900/50 flex flex-col items-center justify-center cursor-pointer"
                                            @click="$el.querySelector('input').click()">

                                            <template
                                                x-if="(selectedFiles[i] || (currentMachine?.image_urls && currentMachine.image_urls[i])) && !deletedPhotos[i]">
                                                <div class="absolute inset-0 w-full h-full">
                                                    <img :src="selectedFiles[i] || currentMachine.image_urls[i]"
                                                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                                    <div
                                                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-all flex flex-col items-center justify-center gap-3">
                                                        <button type="button"
                                                            class="bg-white text-emerald-600 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-xl transform hover:scale-105 transition-all"
                                                            @click.stop="$el.closest('.group').querySelector('input').click()">
                                                            {{ __('Change') }}
                                                        </button>
                                                        <button type="button"
                                                            class="bg-rose-500/90 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-xl transform hover:scale-105 transition-all"
                                                            @click.stop="deletePhoto(i)">
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>

                                            <template
                                                x-if="!selectedFiles[i] && !(currentMachine?.image_urls && currentMachine.image_urls[i]) || deletedPhotos[i]">
                                                <div class="flex flex-col items-center gap-3">
                                                    <div
                                                        class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                    </div>
                                                    <span
                                                        class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{
                                                        __('Upload') }}</span>
                                                </div>
                                            </template>

                                            <input type="file" :name="'machine_image_' + i" class="hidden"
                                                accept="image/*" @change="handlePhotoFileChange($event, i)">
                                            <input type="hidden" :name="'delete_photo_' + i"
                                                :value="deletedPhotos[i] ? '1' : '0'">
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div
                                class="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-4 flex items-center gap-4">
                                <div
                                    class="flex-shrink-0 p-2 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p
                                    class="text-xs font-bold text-amber-700 dark:text-amber-300 leading-relaxed text-left flex-1">
                                    {{ __('PNG, JPG, WEBP up to 10MB') }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-6 py-5 md:px-8 md:py-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800 shrink-0">
                            <button type="button" @click="showPhotoModal = false" class="btn-luxury-ghost">{{
                                __('Cancel') }}</button>
                            <button type="submit" class="btn-luxury-primary px-8">{{ __('Save Changes') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- 4.1 Image Lightbox Modal -->
    <template x-teleport="body">
        <div x-show="showImageLightbox" class="fixed inset-0 z-[200] flex items-center justify-center p-4 md:p-12"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-950/90 backdrop-blur-xl" @click="showImageLightbox = false"></div>

            <!-- Close Button -->
            <button @click="showImageLightbox = false"
                class="absolute top-6 right-6 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white backdrop-blur-md transition-all duration-300 z-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Image Container -->
            <div class="relative max-w-5xl w-full max-h-full flex items-center justify-center p-4 animate-luxury-in"
                @click.away="showImageLightbox = false">
                <img :src="lightboxImageUrl"
                    class="max-w-full max-h-[85vh] rounded-3xl shadow-2xl border border-white/10 ring-1 ring-white/5 object-contain"
                    x-show="showImageLightbox" x-transition:enter="transition ease-out duration-500 delay-100"
                    x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100">
            </div>

            <!-- Helper text -->
            {{ __('Click anywhere to close') }}
        </div>
</div>
</template>


<!-- 4.2 Maintenance QR Modal -->
<template x-teleport="body">
    <div x-show="showMaintenanceQrModal" class="fixed inset-0 z-[200] overflow-y-auto" x-cloak
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 transition-opacity" @click="showMaintenanceQrModal = false">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            </div>

            <div
                class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 w-full max-w-sm overflow-hidden animate-luxury-in">
                <div
                    class="px-8 py-6 border-b border-slate-50 dark:border-slate-800/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight">{{ __('Maintenance QR') }}</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1"
                            x-text="maintenanceQrMachineName"></p>
                    </div>
                    <button @click="showMaintenanceQrModal = false"
                        class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-10 flex flex-col items-center gap-6">
                    <div class="p-4 bg-white rounded-3xl shadow-xl border border-slate-100">
                        <x-qr-code data="maintenanceQrUrl" size="200" class="w-48 h-48" />
                    </div>
                    <div class="text-center space-y-2">
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 leading-relaxed px-4">
                            {{ __('Scan this code to quickly access the maintenance form for this device.') }}
                        </p>
                        <div
                            class="mt-4 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700">
                            <code class="text-[10px] break-all text-cyan-600 dark:text-cyan-400 font-bold"
                                x-text="maintenanceQrUrl"></code>
                        </div>
                    </div>
                </div>

                <div
                    class="px-8 py-6 bg-slate-50 dark:bg-slate-900/50 flex justify-center border-t border-slate-100 dark:border-slate-800">
                    <button @click="showMaintenanceQrModal = false"
                        class="btn-luxury-primary w-full py-4 rounded-2xl">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>


<!-- 5. Detail Drawer (Same for both) -->
<template x-teleport="body">
    <div x-show="showDetailDrawer" class="fixed inset-0 z-[150]" x-cloak>
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" x-show="showDetailDrawer"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showDetailDrawer = false">
        </div>
        <div class="fixed inset-y-0 right-0 max-w-full flex">
            <div class="w-screen max-w-md" x-show="showDetailDrawer"
                x-transition:enter="transform transition ease-in-out duration-500"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div
                    class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-100 dark:border-slate-800">
                    <div
                        class="px-6 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-black text-slate-800 dark:text-white">{{ __('Parameters') }}</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mt-1"
                                x-text="currentMachine?.name"></p>
                        </div>
                        <button @click="showDetailDrawer = false"
                            class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 pt-1 pb-6 space-y-6 custom-scrollbar">
                        <template x-if="currentMachine?.image_urls && currentMachine.image_urls.length > 0">
                            <section class="space-y-4">
                                <h3 class="text-xs font-black text-indigo-500 uppercase tracking-[0.3em]">{{
                                    __('Machine Images') }}</h3>
                                <div class="grid grid-cols-2 gap-3">
                                    <template x-for="(url, index) in currentMachine.image_urls" :key="index">
                                        <div @click="lightboxImageUrl = url; showImageLightbox = true"
                                            class="relative group aspect-square rounded-2xl overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm bg-slate-50 dark:bg-slate-800/50 cursor-zoom-in hover:ring-2 hover:ring-cyan-500/50 transition-all duration-300 group/img">
                                            <img :src="url"
                                                class="absolute inset-0 w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-500">
                                            <div
                                                class="absolute inset-0 bg-slate-900/0 group-hover/img:bg-slate-900/20 flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-all duration-300">
                                                <svg class="w-6 h-6 text-white drop-shadow-md" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </section>
                        </template>
                        <section class="space-y-6">
                            <h3 class="text-xs font-black text-cyan-500 uppercase tracking-[0.3em]">{{ __('Hardware & Network') }}</h3>
                            <div class="grid grid-cols-1 gap-4">
                                <div
                                    class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                    <span
                                        class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{
                                        __('Serial & Version') }}</span>
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-mono font-bold text-slate-700 dark:text-slate-300"
                                            x-text="currentMachine?.serial_no"></div>
                                        <span
                                            class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-900 text-[10px] font-black text-slate-500 border border-slate-100 dark:border-slate-800"
                                            x-text="'v' + (currentMachine?.firmware_version || '1.0')"></span>
                                    </div>
                                </div>
                                <div
                                    class="bg-slate-50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                                    <span
                                        class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1.5">{{
                                        __('Heartbeat') }}</span>
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300"
                                        x-text="currentMachine?.last_heartbeat_at ? new Date(currentMachine.last_heartbeat_at).toLocaleString() : '--'">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Operational Settings -->
                        <section class="space-y-6">
                            <h3 class="text-xs font-black text-amber-500 uppercase tracking-[0.3em]">{{
                                __('Operations') }}</h3>
                            <div class="space-y-4">
                                <div
                                    class="flex items-center justify-between p-2 border-b border-slate-50 dark:border-white/5">
                                    <span class="text-sm font-bold text-slate-500">{{ __('Heating Range') }}</span>
                                    <span class="text-sm font-black text-slate-700 dark:text-slate-300"
                                        x-text="(currentMachine?.heating_start_time ? currentMachine.heating_start_time.substring(0, 5) : '00:00') + ' ~ ' + (currentMachine?.heating_end_time ? currentMachine.heating_end_time.substring(0, 5) : '00:00')"></span>
                                </div>
                                <div
                                    class="flex items-center justify-between p-2 border-b border-slate-50 dark:border-white/5">
                                    <span class="text-sm font-bold text-slate-500">{{ __('Card Reader No') }}</span>
                                    <span class="text-sm font-black text-slate-700 dark:text-slate-300"
                                        x-text="currentMachine?.card_reader_no || '--'"></span>
                                </div>
                                <div
                                    class="flex items-center justify-between p-2 border-b border-slate-50 dark:border-white/5">
                                    <span class="text-sm font-bold text-slate-500">{{ __('Key No') }}</span>
                                    <span class="text-sm font-black text-slate-700 dark:text-slate-300"
                                        x-text="currentMachine?.key_no || '--'"></span>
                                </div>
                                <div
                                    class="flex flex-col gap-3 p-3 mt-1 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-700/50 relative">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-black text-slate-500 uppercase tracking-widest">{{
                                            __('API Token') }}</span>
                                        <div class="flex items-center gap-1">
                                            <template x-if="currentMachine?.api_token">
                                                <div class="flex items-center gap-1">
                                                    <button @click="showApiToken = !showApiToken"
                                                        class="p-1.5 rounded-lg text-slate-400 hover:text-cyan-500 hover:bg-cyan-50 dark:hover:bg-cyan-900/40 transition-all font-bold"
                                                        :title="showApiToken ? '{{ __('Hide') }}' : '{{ __('Show') }}'">
                                                        <svg x-show="!showApiToken" class="w-4 h-4" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        <svg x-show="showApiToken" x-cloak class="w-4 h-4" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                        </svg>
                                                    </button>
                                                    <button @click="copyToken(currentMachine)"
                                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 transition-all font-bold"
                                                        title="{{ __('Copy') }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <button @click="regenerateToken()" :disabled="loadingRegenerate"
                                                class="ml-2 px-2.5 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-500/20 text-xs font-black uppercase tracking-widest transition-all disabled:opacity-50 flex items-center gap-1.5 border border-rose-100 dark:border-rose-500/20"
                                                title="{{ __('Regenerate') }}">
                                                <svg x-show="loadingRegenerate" class="animate-spin w-3 h-3"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                                <svg x-show="!loadingRegenerate" class="w-3 h-3" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>{{ __('Regenerate') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700/50 p-2.5 overflow-x-auto custom-scrollbar">
                                        <span
                                            class="text-sm font-mono font-bold tracking-[0.1em] text-cyan-600 dark:text-cyan-400 select-all block whitespace-nowrap min-w-full"
                                            x-text="currentMachine?.api_token ? (showApiToken ? currentMachine.api_token : '•'.repeat(16)) : '{{ __('None') }}'"></span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Location -->
                        <section class="space-y-4">
                            <h3 class="text-xs font-black text-emerald-500 uppercase tracking-[0.3em]">{{
                                __('Location') }}</h3>
                            <div
                                class="p-4 bg-emerald-50/30 dark:bg-emerald-500/5 rounded-2xl border border-emerald-100/50 dark:border-emerald-500/10">
                                <p class="text-sm text-emerald-700 dark:text-emerald-400 leading-relaxed font-bold"
                                    x-text="currentMachine?.location || '{{ __('No location set') }}'"></p>
                            </div>
                        </section>
                    </div>
                    <div
                        class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <button @click="showDetailDrawer = false" class="w-full btn-luxury-ghost">{{ __('Close Panel')
                            }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<!-- Global Delete Confirm Modal -->
<x-delete-confirm-modal />

<x-confirm-modal alpine-var="isRegenerateConfirmOpen"
    confirm-action="isRegenerateConfirmOpen = false; window.dispatchEvent(new CustomEvent('execute-regenerate', { detail: window.activeMachineSerial || window.activeMachineId }))"
    icon-type="warning" confirm-color="sky" :title="__('Are you sure?')"
    :message="__('Regenerating the token will disconnect the physical machine until it is updated. Continue?')"
    :confirm-text="__('Yes, regenerate')" />

<!-- Machine Permissions Modal -->
<template x-teleport='body'>
    <div x-show='showPermissionModal' class='fixed inset-0 z-[160] overflow-y-auto' x-cloak>
        <div class='flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0'>
            <div x-show='showPermissionModal' @click='showPermissionModal = false'
                x-transition:enter='ease-out duration-300' x-transition:enter-start='opacity-0'
                x-transition:enter-end='opacity-100' x-transition:leave='ease-in duration-200'
                x-transition:leave-start='opacity-100' x-transition:leave-end='opacity-0'
                class='fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity'></div>

            <span class='hidden sm:inline-block sm:align-middle sm:h-screen'>&#8203;</span>

            <div x-show='showPermissionModal' x-transition:enter='ease-out duration-300'
                x-transition:enter-start='opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95'
                x-transition:enter-end='opacity-100 translate-y-0 sm:scale-100'
                x-transition:leave='ease-in duration-200'
                x-transition:leave-start='opacity-100 translate-y-0 sm:scale-100'
                x-transition:leave-end='opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95'
                class='inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full overflow-hidden animate-luxury-in'>

                <div class='flex justify-between items-center mb-8'>
                    <div>
                        <h3 class='text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight'>
                            {{ __('Authorized Machines Management') }}</h3>
                        <div class='flex items-center gap-2 mt-1 drop-shadow-sm'>
                            <span class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]'>{{
                                __('Account') }}:</span>
                            <span class='text-xs font-bold text-cyan-500 uppercase tracking-widest'
                                x-text='targetUserName'></span>
                        </div>
                    </div>
                    <button @click='showPermissionModal = false'
                        class='text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors bg-slate-50 dark:bg-slate-800 p-2 rounded-xl'>
                        <svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5'
                                d='M6 18L18 6M6 6l12 12' />
                        </svg>
                    </button>
                </div>

                <div class='relative min-h-[400px]'>
                    <div class='mb-6 flex flex-col md:flex-row gap-4 items-center'>
                        <div class='flex-1 relative group w-full text-left'>
                            <span class='absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none z-10'>
                                <svg class='w-4 h-4 text-slate-400 group-focus-within:text-cyan-500 transition-colors'
                                    viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5'
                                    stroke-linecap='round' stroke-linejoin='round'>
                                    <circle cx='11' cy='11' r='8'></circle>
                                    <line x1='21' y1='21' x2='16.65' y2='16.65'></line>
                                </svg>
                            </span>
                            <input type='text' x-model='permissionSearchQuery'
                                placeholder='{{ __("Search machines...") }}'
                                class='luxury-input py-3 pl-12 pr-6 block w-full text-sm font-extrabold' @click.stop>
                        </div>
                        <button @click="toggleSelectAll()"
                            class="shrink-0 flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-cyan-500 hover:text-white transition-all duration-300 border border-slate-200 dark:border-slate-700 font-black text-xs uppercase tracking-widest shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span
                                x-text="allMachines.filter(m => !permissionSearchQuery || m.name.toLowerCase().includes(permissionSearchQuery.toLowerCase()) || m.serial_no.toLowerCase().includes(permissionSearchQuery.toLowerCase())).every(m => permissions[m.id]) ? '{{ __('Deselect All') }}' : '{{ __('Select All') }}'"></span>
                        </button>
                    </div>

                    <template x-if='isPermissionsLoading'>
                        <div
                            class='absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-[170] rounded-2xl'>
                            <div class='flex flex-col items-center gap-3'>
                                <div
                                    class='w-10 h-10 border-4 border-cyan-500/20 border-t-cyan-500 rounded-full animate-spin'>
                                </div>
                                <span
                                    class='text-[10px] font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-[0.2em] animate-pulse'>{{
                                    __('Syncing Permissions...') }}</span>
                            </div>
                        </div>
                    </template>

                    <div
                        class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[450px] overflow-y-auto pr-2 custom-scrollbar p-1'>
                        <template
                            x-for='machine in allMachines.filter(m => !permissionSearchQuery || m.name.toLowerCase().includes(permissionSearchQuery.toLowerCase()) || m.serial_no.toLowerCase().includes(permissionSearchQuery.toLowerCase()))'
                            :key='machine.id'>
                            <div @click='togglePermission(machine.id)'
                                :class='permissions[machine.id] ? "border-cyan-500 bg-cyan-500/5 dark:bg-cyan-500/10 ring-1 ring-cyan-500/20 shadow-md shadow-cyan-500/10" : "border-slate-100 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-600 shadow-sm"'
                                class='p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 group relative overflow-hidden'>
                                <div class='flex flex-col relative z-10 text-left'>
                                    <div class='flex items-center gap-2'>
                                        <div class='w-2 h-2 rounded-full'
                                            :class='permissions[machine.id] ? "bg-cyan-500 animate-pulse" : "bg-slate-300 dark:bg-slate-700"'>
                                        </div>
                                        <span class='text-sm font-extrabold truncate drop-shadow-sm'
                                            :class='permissions[machine.id] ? "text-cyan-600 dark:text-cyan-400" : "text-slate-700 dark:text-slate-300"'
                                            x-text='machine.name'></span>
                                    </div>
                                    <span
                                        class='text-[10px] font-mono font-bold text-slate-400 mt-2 tracking-widest uppercase opacity-70'
                                        x-text='machine.serial_no'></span>
                                </div>
                                <div
                                    class='absolute -right-2 -bottom-2 opacity-[0.03] text-slate-900 dark:text-white pointer-events-none group-hover:scale-110 transition-transform duration-700'>
                                    <svg class='w-20 h-20' fill='currentColor' viewBox='0 0 24 24'>
                                        <path
                                            d='M5 2h14c1.1 0 2 .9 2 2v16c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2zm0 2v16h14V4H5zm3 3h8v6H8V7zm0 8h3v2H8v-2zm5 0h3v2h-3v-2z' />
                                    </svg>
                                </div>
                                <div class='absolute top-4 right-4 animate-luxury-in' x-show='permissions[machine.id]'>
                                    <div
                                        class='w-5 h-5 rounded-full bg-cyan-500 flex items-center justify-center shadow-lg shadow-cyan-500/30'>
                                        <svg class='w-3 h-3 text-white' fill='none' stroke='currentColor'
                                            viewBox='0 0 24 24'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='3'
                                                d='M5 13l4 4L19 7' />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div
                    class='flex flex-col sm:flex-row justify-between items-center mt-10 pt-8 border-t border-slate-100 dark:border-slate-800 gap-6'>
                    <div class='flex items-center gap-3'>
                        <div class='flex -space-x-2'>
                            <template x-for='i in Math.min(3, Object.values(permissions).filter(v => v).length)'
                                :key='i'>
                                <div
                                    class='w-6 h-6 rounded-full border-2 border-white dark:border-slate-900 bg-cyan-500 flex items-center justify-center shadow-sm'>
                                    <svg class='w-3 h-3 text-white' fill='currentColor' viewBox='0 0 24 24'>
                                        <path
                                            d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-9l6 4.5-6 4.5z' />
                                    </svg>
                                </div>
                            </template>
                        </div>
                        <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]'>
                            {{ __('Selection') }}: <span class='text-cyan-500 text-xs font-extrabold'
                                x-text='Object.values(permissions).filter(v => v).length'></span> / <span
                                class="font-extrabold" x-text='allMachines?.length || 0'></span> {{ __('Devices') }}
                        </p>
                    </div>
                    <div class='flex gap-4 w-full sm:w-auto'>
                        <button @click='showPermissionModal = false'
                            class='flex-1 sm:flex-none btn-luxury-ghost px-8'>{{ __('Cancel') }}</button>
                        <button @click='savePermissions()'
                            class='flex-1 sm:flex-none btn-luxury-primary px-12 transition-all duration-300 shadow-lg shadow-cyan-500/20'
                            :disabled='isPermissionsLoading'>
                            <span>{{ __('Update Authorization') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>



</div>

@endsection

@section('scripts')
<script>

</script>
@endsection
