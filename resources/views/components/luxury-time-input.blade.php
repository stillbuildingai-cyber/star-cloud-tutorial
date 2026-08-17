@props(['name', 'value' => '', 'label' => ''])

<div x-data="{ 
    time: '{{ $value ? \Carbon\Carbon::parse($value)->format('H:i:s') : '' }}',
    formatInput(e) {
        let cursor = e.target.selectionStart;
        let originalLen = e.target.value.length;
        let val = e.target.value.replace(/\D/g, '');
        
        if (val.length > 6) val = val.slice(0, 6);
        
        let formatted = '';
        if (val.length > 0) {
            formatted = val.slice(0, 2);
            if (val.length > 2) {
                formatted += ':' + val.slice(2, 4);
                if (val.length > 4) {
                    formatted += ':' + val.slice(4, 6);
                }
            }
        }
        
        this.time = formatted;
        
        // Minor delay to fix cursor position if needed, 
        // though for time strings move-to-end is often acceptable.
        this.$nextTick(() => {
            let diff = formatted.length - originalLen;
            let newPos = cursor + diff;
            // e.target.setSelectionRange(newPos, newPos); // Optional cursor fix
        });
    }
}" class="relative">
    <input 
        type="text" 
        name="{{ $name }}" 
        :value="time"
        @input="formatInput"
        maxlength="8"
        placeholder="HH:mm:ss"
        class="luxury-input w-full pr-12 font-mono tracking-wider"
        autocomplete="off"
    >
    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 dark:text-slate-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
</div>
