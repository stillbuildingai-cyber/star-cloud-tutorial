<div x-show="isAssignModalOpen" 
     class="fixed inset-0 z-[100] overflow-y-auto" 
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="!isSubmitting && (isAssignModalOpen = false)"></div>

    <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
        <div class="relative inline-block px-8 py-10 text-left align-bottom transition-all transform luxury-card rounded-3xl dark:bg-slate-900 border-slate-200/50 dark:border-slate-700/50 shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full overflow-visible animate-luxury-in">
            
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white font-display tracking-tight">{{ __('Assign Advertisement') }}</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ __('Select a material to play on this machine') }}</p>
                </div>
                <button @click="!isSubmitting && (isAssignModalOpen = false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors" :disabled="isSubmitting">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form @submit.prevent="submitAssignment" class="space-y-6">
                <!-- Machine & Position Info (Read-only) -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-white/5">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ __('Target Position') }}</p>
                        <p class="text-sm font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-tight" x-text="{ vending: '{{ __('vending') }}', visit_gift: '{{ __('visit_gift') }}', standby: '{{ __('standby') }}' }[assignForm.position] || assignForm.position"></p>
                    </div>
                </div>

                <!-- Ad Material Selection -->
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">{{ __('Select Material') }}</label>
                    <div id="assign_ad_select_wrapper">
                        <!-- Select dropdown will be dynamically built by updateAssignSelect() based on machine company context -->
                    </div>
                </div>


                <!-- Action Footer -->
                <div class="flex items-center justify-end gap-3 pt-6">
                    <button type="button" @click="isAssignModalOpen = false" :disabled="isSubmitting" class="px-6 py-3 text-sm font-black text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors uppercase tracking-widest disabled:opacity-60 disabled:cursor-not-allowed">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="btn-luxury-primary px-10 py-3 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg x-show="isSubmitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ __('Confirm Assignment') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Note: This logic is added to the main adManager data object in index.blade.php
    // Here we just define the submit handler
    async function submitAssignment() {
        try {
            const response = await fetch(this.urls.assign, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.assignForm)
            });
            const result = await response.json();
            if (result.success) {
                this.isAssignModalOpen = false;
                this.fetchMachineAds();
                window.showToast?.(result.message, 'success');
            } else {
                window.showToast?.(result.message || 'Error', 'error');
            }
        } catch (e) {
            console.error('Failed to assign ad', e);
        }
    }
</script>
