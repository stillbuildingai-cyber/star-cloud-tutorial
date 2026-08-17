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
        Schema::table('machines', function (Blueprint $table) {
            $table->string('serial_no')->unique()->after('name')->comment('機台序號');
            $table->string('model')->nullable()->after('serial_no')->comment('型號');
            $table->tinyInteger('current_page')->default(0)->after('status')->comment('當前頁面狀態');
            $table->string('door_status')->nullable()->after('current_page')->comment('門禁狀態');
            $table->string('api_token')->nullable()->after('firmware_version')->comment('API Token');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['serial_no', 'model', 'current_page', 'door_status', 'api_token', 'deleted_at']);
        });
    }
};
