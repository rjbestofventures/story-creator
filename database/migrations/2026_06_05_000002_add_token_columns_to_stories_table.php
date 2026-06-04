<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->unsignedBigInteger('tokens_input')->default(0)->after('refines_used');
            $table->unsignedBigInteger('tokens_output')->default(0)->after('tokens_input');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['tokens_input', 'tokens_output']);
        });
    }
};
