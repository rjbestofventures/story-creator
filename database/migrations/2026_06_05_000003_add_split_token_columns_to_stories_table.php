<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->string('interview_model')->nullable()->after('tokens_output');
            $table->string('generation_model')->nullable()->after('interview_model');
            $table->unsignedBigInteger('tokens_interview_input')->default(0)->after('generation_model');
            $table->unsignedBigInteger('tokens_interview_output')->default(0)->after('tokens_interview_input');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['interview_model', 'generation_model', 'tokens_interview_input', 'tokens_interview_output']);
        });
    }
};
