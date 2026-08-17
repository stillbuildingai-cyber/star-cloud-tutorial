<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 將 products.spec（商品詳情主鏡像值）由 VARCHAR(255) 改為 TEXT，
     * 以支援多行詳情排版，避免 zh_TW 較長內容被截斷。
     * （translations.value 本即 TEXT，不需變更。）
     */
    public function up(): void
    {
        // sqlite 為動態型別，VARCHAR/TEXT 等價且不支援 MODIFY 語法，僅在 MySQL 執行。
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE products MODIFY spec TEXT NULL COMMENT \'詳情\'');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE products MODIFY spec VARCHAR(255) NULL COMMENT \'規格\'');
        }
    }
};
