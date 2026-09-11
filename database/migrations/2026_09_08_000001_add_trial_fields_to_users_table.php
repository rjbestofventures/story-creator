<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('is_verified_partner');
            $table->unsignedSmallInteger('trial_allowance')->default(0)->after('is_trial');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_trial', 'trial_allowance']);
        });
    }
};
