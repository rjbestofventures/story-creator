<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vbp_plan', 16)->nullable()->after('is_verified_partner');
            $table->boolean('is_temporary_vbp')->default(false)->after('vbp_plan');
            $table->timestamp('temporary_vbp_expires_at')->nullable()->after('is_temporary_vbp');
            $table->timestamp('temporary_story_generated_at')->nullable()->after('temporary_vbp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'vbp_plan',
                'is_temporary_vbp',
                'temporary_vbp_expires_at',
                'temporary_story_generated_at',
            ]);
        });
    }
};
