<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'label' => 'Free',
                'episode_limit' => 12,
                'stories_per_month' => 0,
                'refine_monthly' => 0,
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_months' => 0,
            ],
            [
                'slug' => 'partner',
                'label' => 'Verified Business Partner',
                'episode_limit' => 12,
                'stories_per_month' => 2,
                'refine_monthly' => 12,
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_months' => 6,
            ],
            [
                'slug' => 'basic',
                'label' => 'Basic',
                'episode_limit' => 12,
                'stories_per_month' => 2,
                'refine_monthly' => 12,
                'price_monthly' => 10,
                'price_yearly' => 100,
                'trial_months' => 0,
            ],
            [
                'slug' => 'premium',
                'label' => 'Premium',
                'episode_limit' => 18,
                'stories_per_month' => 2,
                'refine_monthly' => 24,
                'price_monthly' => 15,
                'price_yearly' => 150,
                'trial_months' => 0,
            ],
            [
                'slug' => 'professional',
                'label' => 'Professional',
                'episode_limit' => 24,
                'stories_per_month' => 2,
                'refine_monthly' => 48,
                'price_monthly' => 25,
                'price_yearly' => 250,
                'trial_months' => 0,
            ],
        ];

        foreach ($plans as $data) {
            Plan::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
