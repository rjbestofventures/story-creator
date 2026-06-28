<?php

namespace Database\Seeders;

use App\Models\CreditPack;
use Illuminate\Database\Seeder;

class CreditPackSeeder extends Seeder
{
    public function run(): void
    {
        $packs = [
            // Verified Business Partner packs (discounted pricing)
            ['slug' => 'partner-basic',        'label' => 'Basic Pack',        'type' => 'partner',  'credits' => 48, 'max_episodes' => 12, 'price' => 2000],
            ['slug' => 'partner-premium',      'label' => 'Premium Pack',      'type' => 'partner',  'credits' => 72, 'max_episodes' => 18, 'price' => 3000],
            ['slug' => 'partner-professional', 'label' => 'Professional Pack', 'type' => 'partner',  'credits' => 96, 'max_episodes' => 24, 'price' => 4000],

            // StoryBot packs (public retail pricing)
            ['slug' => 'storybot-basic',        'label' => 'Basic Pack',        'type' => 'storybot', 'credits' => 48, 'max_episodes' => 12, 'price' => 18000],
            ['slug' => 'storybot-premium',      'label' => 'Premium Pack',      'type' => 'storybot', 'credits' => 72, 'max_episodes' => 18, 'price' => 27000],
            ['slug' => 'storybot-professional', 'label' => 'Professional Pack', 'type' => 'storybot', 'credits' => 96, 'max_episodes' => 24, 'price' => 36000],

            // Add-on
            ['slug' => 'credit-boost', 'label' => 'Credit Boost Pack', 'type' => 'addon', 'credits' => 12, 'max_episodes' => 24, 'price' => 4500],
        ];

        foreach ($packs as $pack) {
            CreditPack::updateOrCreate(
                ['slug' => $pack['slug']],
                array_merge($pack, ['stripe_price_id' => null, 'is_active' => true]),
            );
        }

        // Retire legacy single-tier packs from the previous model
        CreditPack::whereIn('slug', ['basic', 'premium', 'professional'])->update(['is_active' => false]);
    }
}
