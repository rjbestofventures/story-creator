<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Retail Packs on sale match the signup page exactly: Silver and Gold, with
 * the Professional tier taken off sale.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('credit_packs')->where('slug', 'storybot-basic')->update([
            'label' => 'Silver', 'price' => 18000, 'credits' => 24, 'max_episodes' => 12, 'is_active' => true, 'updated_at' => now(),
        ]);

        DB::table('credit_packs')->where('slug', 'storybot-premium')->update([
            'label' => 'Gold', 'price' => 27000, 'credits' => 36, 'max_episodes' => 24, 'is_active' => true, 'updated_at' => now(),
        ]);

        DB::table('credit_packs')->where('slug', 'storybot-professional')->update([
            'is_active' => false, 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('credit_packs')->where('slug', 'storybot-basic')->update([
            'label' => 'Basic', 'credits' => 48, 'max_episodes' => 12, 'is_active' => false,
        ]);

        DB::table('credit_packs')->where('slug', 'storybot-premium')->update([
            'label' => 'Premium', 'credits' => 72, 'max_episodes' => 18, 'is_active' => false,
        ]);
    }
};
