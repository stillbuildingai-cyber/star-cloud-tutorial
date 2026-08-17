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
        Schema::table('staff_cards', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });
        
        Schema::table('staff_card_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->index()->after('id')->comment('租戶ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_card_logs', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('staff_cards', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });
    }
};
