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
        Schema::table('machine_slots', function (Blueprint $group) {
            $group->string('type')->nullable()->comment('1: track, 2: spring')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_slots', function (Blueprint $group) {
            $group->string('type')->nullable()->comment('1: spring, 2: track')->change();
        });
    }
};
