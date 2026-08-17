{{-- 切換帳號 Modal --}}
<div x-data="impersonateModal()" @open-impersonate-modal.window="openModal()" x-cloak>
    <div x-show="showModal" class="fixed inset-0 z-[110] overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Backdrop --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:min-h-screen" aria-hidden="true">&#8203;</span>
            {{-- Modal Content --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="relative inline-flex flex-col align-bottom bg-white dark:bg-slate-900 rounded-[2.5rem] text-left shadow-2xl transform sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-100 dark:border-slate-800 z-10"
                @click.stop>

                {{-- Header --}}
                <div class="px-10 py-8 pb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight leading-none mb-3">
                            {{ __('切換帳號') }}
                        </h3>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('以其他帳號身份操作系統') }}
                        </p>
                    </div>
                    <button @click="showModal = false"
                        class="p-2.5 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700 shadow-sm">
                        <svg class="w-5 h-5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form action="{{ route('admin.impersonate.store') }}" method="POST" class="flex-1 overflow-y-auto px-10 py-2">
                    @csrf
                    <div class="space-y-6 pb-6">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ __('請選擇您要切換的目標公司與帳號。切換後，您將擁有該帳號的所有操作權限。') }}
                        </p>

                        {{-- Company Select --}}
                        <div class="space-y-3 relative group focus-within:z-[60]">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1">
                                {{ __('公司') }}
                            </label>
                            <div id="impersonate-company-select-wrapper" class="relative">
                                {{-- 透過 Alpine.js 動態重建 HSSelect --}}
                                <div class="luxury-input w-full px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50 text-slate-400 text-sm flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-cyan-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ __('載入中...') }}
                                </div>
                            </div>
                        </div>

                        {{-- User Select --}}
                        <div class="space-y-3 relative group focus-within:z-[60]">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.15em] pl-1">
                                {{ __('使用者帳號') }}
                            </label>
                            <div id="impersonate-user-select-wrapper" class="relative">
                                {{-- 透過 Alpine.js 動態重建 HSSelect --}}
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="py-6 border-t border-slate-100 dark:border-slate-800/50 flex items-center justify-end gap-4">
                        <button type="button" @click="showModal = false" class="btn-luxury-ghost px-8">
                            {{ __('取消') }}
                        </button>
                        <button type="submit" :disabled="!selectedUser" class="btn-luxury-primary px-12 disabled:opacity-50">
                            {{ __('確認切換') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function impersonateModal() {
        return {
            showModal: false,
            companies: [],
            users: [],
            selectedCompany: '',
            selectedUser: '',
            isLoadingUsers: false,
            initialized: false,

            openModal() {
                this.showModal = true;
                if (!this.initialized) {
                    this.fetchCompanies();
                    this.initialized = true;
                } else {
                    // Make sure Preline JS re-initializes or attaches correctly when modal becomes visible
                    this.$nextTick(() => {
                        this.updateCompanySelect();
                        this.updateUserSelect();
                    });
                }
            },

            fetchCompanies() {
                fetch("{{ route('admin.impersonate.companies') }}")
                    .then(res => res.json())
                    .then(data => {
                        this.companies = data;
                        this.$nextTick(() => {
                            this.updateCompanySelect();
                            this.updateUserSelect();
                        });
                    });
            },

            fetchUsers() {
                if (this.selectedCompany === '') return;
                this.isLoadingUsers = true;
                this.users = [];
                this.selectedUser = '';
                this.$nextTick(() => { this.updateUserSelect(); });

                let url = "{{ route('admin.impersonate.users') }}" + '?company_id=' + this.selectedCompany;

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        this.users = data;
                        this.isLoadingUsers = false;
                        this.$nextTick(() => { this.updateUserSelect(); });
                    });
            },

            updateCompanySelect() {
                const wrapper = document.getElementById('impersonate-company-select-wrapper');
                if (!wrapper) return;

                const oldSelect = wrapper.querySelector('select');
                if (oldSelect && window.HSSelect?.getInstance(oldSelect)) {
                    try { window.HSSelect.getInstance(oldSelect).destroy(); } catch (e) { }
                }
                wrapper.innerHTML = '';

                const selectEl = document.createElement('select');
                selectEl.id = `impersonate-company-${Date.now()}`;
                selectEl.className = 'hidden';

                const placeholderOpt = document.createElement('option');
                placeholderOpt.value = '';
                placeholderOpt.textContent = '{{ __("請選擇公司") }}';
                selectEl.appendChild(placeholderOpt);

                const sysOpt = document.createElement('option');
                sysOpt.value = 'system';
                sysOpt.textContent = '{{ __("系統帳號 (無公司)") }}';
                if (this.selectedCompany === 'system') sysOpt.selected = true;
                selectEl.appendChild(sysOpt);

                this.companies.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    if (this.selectedCompany == c.id) opt.selected = true;
                    selectEl.appendChild(opt);
                });

                selectEl.setAttribute('data-hs-select', JSON.stringify({
                    "placeholder": "{{ __('請選擇公司') }}",
                    "hasSearch": true,
                    "searchPlaceholder": "{{ __('Search...') }}",
                    "isHidePlaceholder": false,
                    "searchClasses": "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
                    "searchWrapperClasses": "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                    "toggleClasses": "hs-select-toggle luxury-select-toggle w-full text-left",
                    "toggleTemplate": "<button type=\"button\" aria-expanded=\"false\"><span class=\"me-2\" data-icon></span><span class=\"text-slate-800 dark:text-slate-200 truncate\" data-title></span><div class=\"ms-auto\"><svg class=\"size-4 text-slate-400 transition-transform duration-300\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m6 9 6 6 6-6\" /></svg></div></button>",
                    "dropdownClasses": "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[150] animate-luxury-in max-h-72 overflow-y-auto custom-scrollbar-thin",
                    "optionClasses": "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
                    "optionTemplate": "<div class=\"flex items-center justify-between w-full\"><span data-title></span><span class=\"hs-select-active-indicator hidden text-cyan-500\"><svg class=\"w-4 h-4\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"20 6 9 17 4 12\"></polyline></svg></span></div>"
                }));

                wrapper.appendChild(selectEl);
                
                selectEl.addEventListener('change', (e) => {
                    this.selectedCompany = e.target.value;
                    this.fetchUsers();
                });
                
                if (window.HSStaticMethods) {
                    window.HSStaticMethods.autoInit();
                }
            },

            updateUserSelect() {
                const wrapper = document.getElementById('impersonate-user-select-wrapper');
                if (!wrapper) return;

                const oldSelect = wrapper.querySelector('select');
                if (oldSelect && window.HSSelect?.getInstance(oldSelect)) {
                    try { window.HSSelect.getInstance(oldSelect).destroy(); } catch (e) { }
                }
                wrapper.innerHTML = '';

                const selectEl = document.createElement('select');
                selectEl.id = `impersonate-user-${Date.now()}`;
                selectEl.name = 'user_id';
                selectEl.required = true;
                selectEl.className = 'hidden';

                const placeholderOpt = document.createElement('option');
                placeholderOpt.value = '';
                
                if (this.isLoadingUsers) {
                    placeholderOpt.textContent = '{{ __("載入中...") }}';
                    selectEl.disabled = true;
                } else if (!this.selectedCompany) {
                    placeholderOpt.textContent = '{{ __("請先選擇公司") }}';
                    selectEl.disabled = true;
                } else if (this.users.length === 0) {
                    placeholderOpt.textContent = '{{ __("此公司目前沒有帳號") }}';
                    selectEl.disabled = true;
                } else {
                    placeholderOpt.textContent = '{{ __("請選擇帳號") }}';
                }
                selectEl.appendChild(placeholderOpt);

                this.users.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.id;
                    const identifier = u.username || u.email || '';
                    opt.textContent = identifier ? `${u.name} (${identifier})` : u.name;
                    if (this.selectedUser == u.id) opt.selected = true;
                    selectEl.appendChild(opt);
                });

                selectEl.setAttribute('data-hs-select', JSON.stringify({
                    "placeholder": selectEl.disabled ? placeholderOpt.textContent : "{{ __('請選擇帳號') }}",
                    "hasSearch": true,
                    "searchPlaceholder": "{{ __('Search...') }}",
                    "isHidePlaceholder": false,
                    "searchClasses": "block w-[calc(100%-16px)] mx-2 py-2 px-3 text-sm border-slate-200 dark:border-white/10 rounded-lg focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 dark:bg-slate-900/50 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500",
                    "searchWrapperClasses": "sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-2 z-10",
                    "toggleClasses": "hs-select-toggle luxury-select-toggle w-full text-left " + (selectEl.disabled ? "opacity-50 cursor-not-allowed" : ""),
                    "toggleTemplate": "<button type=\"button\" aria-expanded=\"false\"><span class=\"me-2\" data-icon></span><span class=\"text-slate-800 dark:text-slate-200 truncate\" data-title></span><div class=\"ms-auto\"><svg class=\"size-4 text-slate-400 transition-transform duration-300\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m6 9 6 6 6-6\" /></svg></div></button>",
                    "dropdownClasses": "hs-select-menu w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mt-2 z-[150] animate-luxury-in max-h-72 overflow-y-auto custom-scrollbar-thin",
                    "optionClasses": "hs-select-option py-2.5 px-3 mb-0.5 text-sm text-slate-800 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-400 rounded-lg flex items-center justify-between transition-all duration-300",
                    "optionTemplate": "<div class=\"flex items-center justify-between w-full\"><span class=\"truncate\" data-title></span><span class=\"hs-select-active-indicator hidden text-cyan-500 shrink-0\"><svg class=\"w-4 h-4\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"20 6 9 17 4 12\"></polyline></svg></span></div>"
                }));

                wrapper.appendChild(selectEl);
                
                selectEl.addEventListener('change', (e) => {
                    this.selectedUser = e.target.value;
                });

                if (window.HSStaticMethods) {
                    window.HSStaticMethods.autoInit();
                }
            }
        }
    }
</script>
