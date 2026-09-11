<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * StoryController::generate() — the endpoint the interview actually calls —
     * was not setting created_on_trial when it moved a story from
     * "interviewing" to "generating". Every story a trial member generated
     * through the real UI in that window came back unlocked. This corrects
     * any of those still owned by a member currently on trial, the same way
     * the previous migration's backfill did. Safe to re-run.
     */
    public function up(): void
    {
        DB::table('stories')
            ->whereIn('user_id', DB::table('users')->where('is_trial', true)->pluck('id'))
            ->where('created_on_trial', false)
            ->whereNull('episodes_unlocked_at')
            ->update(['created_on_trial' => true]);
    }

    public function down(): void
    {
        // Not reversible — there is no record of which rows this touched.
    }
};
