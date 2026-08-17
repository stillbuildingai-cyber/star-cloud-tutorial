<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 貨道「效期 + 鎖定」雙向 LWW 同步所需欄位。
 *
 * - machine_slots.is_locked          : 貨道是否被「鎖定停售」（與 is_active「是否啟用/配置」語意不同，兩者並存）。
 * - machine_slots.last_app_lock_rev  : 後台已採用的 App 鎖定編輯序號（per-field LWW，避免比較機台/後台兩個時鐘）。
 * - machine_slots.last_app_expiry_rev: 後台已採用的 App 效期/批號編輯序號。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_slots', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('is_active');
            $table->unsignedBigInteger('last_app_lock_rev')->default(0)->after('is_locked');
            $table->unsignedBigInteger('last_app_expiry_rev')->default(0)->after('last_app_lock_rev');
        });
    }

    public function down(): void
    {
        Schema::table('machine_slots', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'last_app_lock_rev', 'last_app_expiry_rev']);
        });
    }
};
