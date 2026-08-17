import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// 初始化 Preline UI
import 'preline';

// 引入 Flatpickr 與語系
import flatpickr from "flatpickr";
import { MandarinTraditional } from "flatpickr/dist/l10n/zh-tw.js";
import { Japanese } from "flatpickr/dist/l10n/ja.js";

const docLang = document.documentElement.lang.toLowerCase();

if (docLang.includes('zh')) {
    flatpickr.localize(MandarinTraditional);
    window.flatpickrLocale = MandarinTraditional;
} else if (docLang.includes('ja')) {
    flatpickr.localize(Japanese);
    window.flatpickrLocale = Japanese;
} else {
    // English is the default in flatpickr
    window.flatpickrLocale = 'default';
}

window.flatpickr = flatpickr;

Alpine.plugin(collapse);

window.Alpine = Alpine;

// 確保其他套件都初始化完成後再啟動 Alpine
Alpine.start();
