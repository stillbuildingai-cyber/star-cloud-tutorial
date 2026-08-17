<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // 公司名稱
            $table->string('code', 20)->unique();            // 公司代碼（簡碼）
            $table->string('tax_id', 20)->nullable();        // 統一編號
            $table->string('contact_name', 100)->nullable(); // 聯絡人
            $table->string('contact_phone', 50)->nullable(); // 聯絡電話
            $table->string('contact_email')->nullable();     // 聯絡信箱
            $table->tinyInteger('status')->default(1);       // 1:啟用, 0:停用
            $table->date('valid_until')->nullable();          // 合約期限
            $table->text('note')->nullable();                // 備註
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
