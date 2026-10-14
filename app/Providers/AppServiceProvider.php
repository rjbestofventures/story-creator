<?php

namespace App\Providers;

use App\Models\Episode;
use App\Models\SiteSetting;
use App\Policies\EpisodePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        Gate::policy(Episode::class, EpisodePolicy::class);

        // Per-IP ceiling across every web route, well above what a real visitor needs.
        RateLimiter::for('web', fn (Request $request) => Limit::perMinute(240)->by($request->ip()));
    }

    private function overrideStripeConfig(): void
    {
        try {
            $map = [
                'anthropic_api_key' => ['anthropic.api_key'],
                'openai_api_key' => ['services.openai.key'],
                'elevenlabs_api_key' => ['services.elevenlabs.key'],
                'stripe_key' => ['cashier.key',            'services.stripe.key'],
                'stripe_secret' => ['cashier.secret',         'services.stripe.secret'],
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
