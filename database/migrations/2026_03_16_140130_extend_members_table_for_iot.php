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
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('members', 'barcode')) {
                $table->string('barcode')->nullable()->index();
            }
            if (!Schema::hasColumn('members', 'points')) {
                $table->integer('points')->default(0);
            }
            if (!Schema::hasColumn('members', 'wallet_balance')) {
                $table->decimal('wallet_balance', 10, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['company_id', 'barcode', 'points', 'wallet_balance']);
        });
    }
};
