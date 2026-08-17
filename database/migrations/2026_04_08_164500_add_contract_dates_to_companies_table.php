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
        Schema::table('companies', function (Blueprint $table) {
            $table->date('warranty_start_date')->nullable()->after('end_date')->comment('保固起始日');
            $table->date('warranty_end_date')->nullable()->after('warranty_start_date')->comment('保固結束日');
            $table->date('software_start_date')->nullable()->after('warranty_end_date')->comment('軟體服務起始日');
            $table->date('software_end_date')->nullable()->after('software_start_date')->comment('軟體服務結束日');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'warranty_start_date',
                'warranty_end_date',
                'software_start_date',
                'software_end_date'
            ]);
        });
    }
};
