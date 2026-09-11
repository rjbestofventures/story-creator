<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->boolean('created_on_trial')->default(false)->after('is_demo');
            $table->timestamp('episodes_unlocked_at')->nullable()->after('created_on_trial');
            $table->timestamp('episodes_reactivated_at')->nullable()->after('episodes_unlocked_at');
            $table->timestamp('reactivation_notified_at')->nullable()->after('episodes_reactivated_at');
        });

        // Locking used to be read off the owner's trial flag. It now hangs off
        // the story, so every library a trial member is currently waiting on has
        // to be marked, or they would all fall open the moment this deploys.
        DB::table('stories')
            ->whereIn('user_id', DB::table('users')->where('is_trial', true)->pluck('id'))
            ->update(['created_on_trial' => true]);
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn([
                'created_on_trial',
                'episodes_unlocked_at',
                'episodes_reactivated_at',
                'reactivation_notified_at',
            ]);
        });
    }
};
