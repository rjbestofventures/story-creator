<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('login_count')->default(0)->after('last_login_at');
            $table->timestamp('password_set_at')->nullable()->after('login_count');
        });

        // Anyone who has logged in must have a password; the exact count and date are unknown.
        DB::table('users')->whereNotNull('last_login_at')->update([
            'login_count' => 1,
            'password_set_at' => DB::raw('last_login_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_count', 'password_set_at']);
        });
    }
};
