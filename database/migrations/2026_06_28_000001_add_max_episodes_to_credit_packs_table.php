<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_packs', function (Blueprint $table) {
            // Largest episodes-per-story option this pack unlocks; allowed set is every option <= this.
            $table->unsignedInteger('max_episodes')->default(24)->after('credits');
        });
    }

    public function down(): void
    {
        Schema::table('credit_packs', function (Blueprint $table) {
            $table->dropColumn('max_episodes');
        });
    }
};
