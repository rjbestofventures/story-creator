<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_packs', function (Blueprint $table) {
            $table->string('type')->default('storybot')->after('label'); // partner | storybot | addon
            $table->unsignedInteger('credits')->default(0)->after('type');
        });

        // Legacy packs: total credits = old episode_limit + revision_credits
        DB::table('credit_packs')->orderBy('id')->each(function ($pack) {
            DB::table('credit_packs')->where('id', $pack->id)->update([
                'credits' => (int) ($pack->episode_limit ?? 0) + (int) ($pack->revision_credits ?? 0),
            ]);
        });

        Schema::table('credit_packs', function (Blueprint $table) {
            $table->dropColumn(['stories_count', 'episode_limit', 'revision_credits']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_packs', function (Blueprint $table) {
            $table->unsignedInteger('stories_count')->default(1)->after('label');
            $table->unsignedInteger('episode_limit')->default(12);
            $table->unsignedInteger('revision_credits')->default(0);
            $table->dropColumn(['type', 'credits']);
        });
    }
};
