@php
    $allErrors = [];
    if (isset($errors)) {
        foreach ($errors->getBags() as $bag) {
            $allErrors = array_merge($allErrors, $bag->all());
        }
    }
@endphp

<div x-data="{ 
    toasts: [],
    add(message, type = 'success') {
        const id = Date.now();
        this.toasts.push({ id, message, type });
        setTimeout(() => {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }, type === 'success' ? 3000 : 5000);
    }
}" 
@toast.window="add($event.detail.message, $event.detail.type)"
x-init="
    window.Alpine.store('toast', {
        show(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
        }
    });
    window.showToast = (message, type = 'success') => {
        window.Alpine.store('toast').show(message, type);
    };
    @if(session('success')) add('{{ addslashes(session('success')) }}', 'success'); @endif
    @if(session('error')) add('{{ addslashes(session('error')) }}', 'error'); @endif
    @foreach($allErrors as $error) add('{{ addslashes($error) }}', 'error'); @endforeach
"
class="fixed top-8 left-1/2 -translate-x-1/2 z-[99999] w-full max-w-sm px-4 space-y-3 pointer-events-none">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform -translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 transform -translate-y-4 scale-95"
             :class="toast.type === 'success' 
                ? 'border-emerald-500/30 text-emerald-600 dark:text-emerald-400 shadow-[0_20px_50px_rgba(16,185,129,0.15)]' 
                : 'border-rose-500/30 text-rose-600 dark:text-rose-400 shadow-[0_20px_50px_rgba(244,63,94,0.15)]'"
             class="p-4 bg-white dark:bg-slate-900 border rounded-2xl font-bold flex items-center gap-3 pointer-events-auto backdrop-blur-xl bg-opacity-90 dark:bg-opacity-90 animate-luxury-in">
            <div :class="toast.type === 'success' ? 'bg-emerald-500/20 text-emerald-500' : 'bg-rose-500/20 text-rose-500'"
                 class="size-8 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg x-show="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <svg x-show="toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
