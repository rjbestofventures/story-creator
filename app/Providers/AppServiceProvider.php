<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        $this->overrideStripeConfig();
    }

    private function overrideStripeConfig(): void
    {
        try {
            $map = [
                'anthropic_api_key'     => ['anthropic.api_key'],
                'stripe_key'            => ['cashier.key',            'services.stripe.key'],
                'stripe_secret'         => ['cashier.secret',         'services.stripe.secret'],
                'stripe_webhook_secret' => ['cashier.webhook.secret', 'services.stripe.webhook.secret'],
            ];

            foreach ($map as $setting => $configKeys) {
                $value = SiteSetting::get($setting);
                if ($value) {
                    foreach ($configKeys as $key) {
                        config([$key => $value]);
                    }
                }
            }
        } catch (\Throwable) {
            // DB unavailable during migrations or initial setup
        }
    }
}
