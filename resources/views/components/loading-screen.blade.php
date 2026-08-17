<div x-data="{ 
         loading: true,
         hideAfterRender() {
             requestAnimationFrame(() => {
                 requestAnimationFrame(() => {
                     setTimeout(() => {
                         this.loading = false;
                     }, 220);
                 });
             });
         }
     }" 
     x-init="hideAfterRender()"
     x-on:show-global-loading.window="loading = true"
     x-on:hide-global-loading.window="loading = false"
     x-show="loading"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="global-loading-screen fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-md">
    <div class="relative flex flex-col items-center animate-luxury-in">
        <!-- Logo with Spinner Animation -->
        <div class="relative w-28 h-28 mb-10 flex items-center justify-center">
            <!-- Luxury Rotating Ring -->
            <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-cyan-500 border-r-cyan-500/30 animate-spin" style="animation-duration: 1.5s;"></div>
            <div class="absolute inset-2 rounded-full border border-white/5 animate-spin" style="animation-duration: 3s; direction: reverse;"></div>
            
            <!-- Glow Effect -->
            <div class="absolute inset-0 rounded-full bg-cyan-500/10 blur-xl animate-pulse"></div>
            
            <!-- Central Logo -->
            <div class="relative w-20 h-20 rounded-full bg-white flex items-center justify-center shadow-2xl overflow-hidden p-2 border border-slate-100">
                <img src="{{ asset('starcloud_icon.png') }}" alt="Logo" class="w-full h-full object-contain">
            </div>
        </div>
        
        <!-- Text Animation -->
        <div class="flex items-center gap-x-3 text-3xl font-bold text-white font-display tracking-tightest overflow-hidden mb-4">
            <span class="animate-fade-in-right opacity-0" style="animation-delay: 100ms; animation-fill-mode: forwards;">Star</span>
            <span class="text-cyan-400 animate-fade-in-right opacity-0" style="animation-delay: 300ms; animation-fill-mode: forwards;">Cloud</span>
        </div>
        
        <p class="text-[10px] font-black text-white/30 uppercase tracking-[0.6em] animate-pulse">{{ __('Systems Initializing') }}</p>
    </div>
</div>
