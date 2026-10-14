<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns away vulnerability scanners before the app boots a session or touches
 * the database. A probe for a file this app never serves (.env, config.json,
 * wp-login.php, backups…) gets a bare 404, and an IP that keeps probing is
 * shut out for a while.
 */
class BlockSuspiciousRequests
{
    public const STRIKES_BEFORE_BAN = 5;

    public const STRIKE_WINDOW_MINUTES = 10;

    public const BAN_MINUTES = 60;

    private const PROBE_PATTERN = '#(^|/)(\.env|\.git|\.svn|\.ht|\.aws|\.ssh|\.vscode|\.DS_Store|wp-|wordpress|xmlrpc|phpmyadmin|pma|cgi-bin|vendor/phpunit|_profiler|debug/default|actuator|server-status|~)'
        .'|(^|/)\.?env\.|\.(php\d?|asp|aspx|asa|asax|jsp|cgi|bak|backup|old|orig|tmp|swp|sql|sqlite|db|yml|yaml|ini|log|conf|config|tar|gz|zip|rar|7z)$'
        .'|(^|/)(config|secrets|credentials|parameters|settings|appsettings)\.(json|js|xml)$#i';

    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();

        if (Cache::has($this->banKey($ip))) {
            $request->attributes->set('firewall.banned', true);

            return response('', 403);
        }

        if ($this->isProbe($request)) {
            $request->attributes->set('firewall.blocked', true);
            $this->strike($ip);

            return response('Not Found', 404);
        }

        return $next($request);
    }

    public function isProbe(Request $request): bool
    {
        $path = rawurldecode($request->path());

        return $path !== 'index.php' && preg_match(self::PROBE_PATTERN, $path) === 1;
    }

    private function strike(string $ip): void
    {
        $key = "firewall:strikes:{$ip}";

        Cache::add($key, 0, now()->addMinutes(self::STRIKE_WINDOW_MINUTES));

        if (Cache::increment($key) >= self::STRIKES_BEFORE_BAN) {
            Cache::put($this->banKey($ip), true, now()->addMinutes(self::BAN_MINUTES));
            Cache::forget($key);
        }
    }

    private function banKey(string $ip): string
    {
        return "firewall:ban:{$ip}";
    }
}
