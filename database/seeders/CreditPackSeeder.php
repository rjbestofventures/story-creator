<?php

namespace Database\Seeders;

use App\Models\CreditPack;
use Illuminate\Database\Seeder;

class CreditPackSeeder extends Seeder
{
    public function run(): void
    {
        $packs = [
            [
                'slug'             => 'basic',
                'label'            => 'Basic',
                'price'            => 900,   // $9.00
                'episode_limit'    => 12,
                'revision_credits' => 36,
                'stripe_price_id'  => null, // set via Admin > Settings > Stripe
                'is_active'        => true,
            ],
            [
                'slug'             => 'premium',
                'label'            => 'Premium',
                'price'            => 1500,  // $15.00
                'episode_limit'    => 18,
                'revision_credits' => 54,
                'stripe_price_id'  => null,
                'is_active'        => true,
            ],
            [
                'slug'             => 'professional',
                'label'            => 'Professional',
                'price'            => 2500,  // $25.00
                'episode_limit'    => 24,
                'revision_credits' => 72,
                'stripe_price_id'  => null,
                'is_active'        => true,
            ],
        ];

        foreach ($packs as $pack) {
            CreditPack::updateOrCreate(['slug' => $pack['slug']], $pack);
        }
    }
}
