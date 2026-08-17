<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 商品「規格 (spec)」多語系：比照名稱的 name_dictionary_key，
     * 為規格新增獨立字典鍵，對應 translations.group = 'product_spec'。
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->uuid('spec_dictionary_key')->nullable()->after('spec')->comment('規格多語系 Key');
            $table->index('spec_dictionary_key');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['spec_dictionary_key']);
            $table->dropColumn('spec_dictionary_key');
        });
    }
};
