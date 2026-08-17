@props([
    'url',            // PATCH 端點（更新 remark）
    'value' => null,  // 目前備註內容
])

{{-- 列表內嵌備註編輯：點擊文字進入編輯，fetch 儲存後 toast。@click.stop 避免觸發整列的開啟詳情。 --}}
<div @click.stop class="min-w-[150px] max-w-[220px]"
    x-data="{
        editing: false,
        saving: false,
        value: @js($value ?? ''),
        original: @js($value ?? ''),
        cancel() { this.value = this.original; this.editing = false; },
        save() {
            this.saving = true;
            fetch(@js($url), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()), 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ remark: this.value })
            }).then(r => { if (!r.ok) throw 0; return r.json(); })
              .then(d => { this.original = this.value; this.editing = false; window.showToast?.((d && d.message) || @js(__('Remark updated')), 'success'); })
              .catch(() => window.showToast?.(@js(__('Operation failed')), 'error'))
              .finally(() => this.saving = false);
        }
    }">
    <template x-if="!editing">
        <button type="button" @click="editing = true" class="group/rm flex items-start gap-1.5 text-left w-full">
            <span class="text-sm font-bold whitespace-pre-wrap break-words line-clamp-2"
                :class="value ? 'text-slate-600 dark:text-slate-300' : 'text-slate-300 dark:text-slate-600'"
                x-text="value || @js(__('Add Remark'))"></span>
            <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-slate-300 dark:text-slate-600 opacity-0 group-hover/rm:opacity-100 transition-opacity"
                fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
            </svg>
        </button>
    </template>
    <template x-if="editing">
        <div class="space-y-2">
            <textarea x-model="value" rows="2" maxlength="1000"
                placeholder="{{ __('Enter remark (optional)') }}"
                class="luxury-input w-full text-xs py-2 px-3 resize-none"></textarea>
            <div class="flex items-center gap-1.5">
                <button type="button" @click="save()" :disabled="saving"
                    class="px-3 py-1.5 rounded-lg bg-cyan-500 hover:bg-cyan-600 text-white font-black text-[10px] uppercase tracking-widest disabled:opacity-60">{{ __('Save') }}</button>
                <button type="button" @click="cancel()" :disabled="saving"
                    class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black text-[10px] uppercase tracking-widest disabled:opacity-60">{{ __('Cancel') }}</button>
            </div>
        </div>
    </template>
</div>
