<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_packs', function (Blueprint $table) {
            $table->unsignedInteger('stories_count')->default(1)->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('credit_packs', function (Blueprint $table) {
            $table->dropColumn('stories_count');
        });
    }
};
