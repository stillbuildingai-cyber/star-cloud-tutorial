@props([
    'field',            // 'names' | 'specs' — 對應 formData[field] 與送出欄位名稱
    'active',           // Alpine 狀態變數名稱，如 'activeNameLocale'
    'required' => true, // zh_TW 是否必填（名稱必填、規格選填）
    'multiline' => false, // 是否使用多行 textarea（詳情啟用，名稱維持單行）
])

{{--
    商品多語系輸入 Tab（名稱 / 規格共用）。
    Tab 清單 = 公司機台語系聯集（由父層 productForm 提供 locales / localeLabels）。
    已填寫的語系在 Tab 上顯示小圓點標記完成度。
--}}
<div>
    {{-- Tabs --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <template x-for="loc in locales" :key="'{{ $field }}-tab-' + loc">
            <button type="button" @click="{{ $active }} = loc"
                :class="{{ $active }} === loc
                    ? 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-300 border-cyan-500/40 shadow-sm'
                    : 'bg-white dark:bg-slate-900 text-slate-500 border-slate-200 dark:border-slate-700 hover:text-slate-800 dark:hover:text-slate-200'"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-black uppercase tracking-widest transition-all">
                <span x-text="localeLabels[loc] || loc"></span>
                @if($required)
                    <span x-show="loc === 'zh_TW'" class="text-rose-400">*</span>
                @endif
                {{-- 完成度標記：有填內容亮起 --}}
                <span class="w-1.5 h-1.5 rounded-full transition-colors"
                      :class="(formData.{{ $field }}[loc] || '').trim() ? 'bg-cyan-400' : 'bg-slate-300/60 dark:bg-slate-600/60'"></span>
            </button>
        </template>
    </div>

    {{-- Inputs：所有語系皆在 DOM（一併送出），僅顯示當前 Tab --}}
    <template x-for="loc in locales" :key="'{{ $field }}-input-' + loc">
        <div x-show="{{ $active }} === loc" x-cloak>
            @if($multiline)
                {{-- 多行：保留換行排版，原樣傳至 APP --}}
                <textarea rows="6"
                          :name="'{{ $field }}[' + loc + ']'"
                          x-model="formData.{{ $field }}[loc]"
                          :required="{{ $required ? "loc === 'zh_TW'" : 'false' }}"
                          class="luxury-input !py-3 text-base font-bold shadow-sm w-full whitespace-pre-wrap resize-y leading-relaxed"></textarea>
            @else
                <input type="text"
                       :name="'{{ $field }}[' + loc + ']'"
                       x-model="formData.{{ $field }}[loc]"
                       :required="{{ $required ? "loc === 'zh_TW'" : 'false' }}"
                       class="luxury-input !py-3 text-base font-bold shadow-sm w-full">
            @endif
        </div>
    </template>
</div>
