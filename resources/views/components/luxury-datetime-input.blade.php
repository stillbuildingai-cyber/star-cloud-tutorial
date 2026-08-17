@props([
    'name',
    'label' => '',
    'placeholder' => '',
    'value' => '',
])

<div x-data="{
    value: @js($value),
    init() {
        flatpickr(this.$refs.input, {
            enableTime: true,
            dateFormat: 'Y/m/d H:i',
            time_24hr: true,
            locale: window.flatpickrLocale || 'zh_TW',
            disableMobile: 'true',
            onChange: (selectedDates, dateStr) => {
                this.value = dateStr;
                this.$dispatch('input', dateStr);
            }
        });
        
        this.$watch('value', (val) => {
            if (this.$refs.input._flatpickr) {
                this.$refs.input._flatpickr.setDate(val, false);
            }
        });
    }
}" class="relative group/datetime" @set-date.window="if($event.detail.name === '{{ $name }}') value = $event.detail.value">
    <input 
        x-ref="input"
        type="text" 
        name="{{ $name }}" 
        x-model="value"
        placeholder="{{ $placeholder }}"
        readonly
        class="w-full h-12 bg-slate-50 dark:bg-slate-800/50 border-none rounded-xl px-4 pr-10 text-sm font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-cyan-500/20 transition-all placeholder:text-slate-400 cursor-pointer"
    >
    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 group-hover/datetime:text-cyan-500 transition-colors pointer-events-none">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
    </div>
</div>
