<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // credits = refine_credits + sum(episode_limit of available user_credits)
        $available = DB::table('user_credits')
            ->where('status', 'available')
            ->select('user_id', DB::raw('SUM(episode_limit) as story_credits'))
            ->groupBy('user_id')
            ->pluck('story_credits', 'user_id');

        DB::table('users')->orderBy('id')->each(function ($user) use ($available) {
            $credits = (int) ($user->refine_credits ?? 0) + (int) ($available[$user->id] ?? 0);
            DB::table('users')->where('id', $user->id)->update(['credits' => $credits]);
        });
    }

    public function down(): void
    {
        DB::table('users')->update(['credits' => 0]);
    }
};
