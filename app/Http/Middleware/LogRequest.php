<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every request for the admin Logs page. The row is written after the
 * response has gone out, so logging never slows the visitor down.
 */
class LogRequest
{
    private const SKIP = ['build/*', 'images/*', 'favicon.*', 'up', 'admin/logs*'];

    private const SECRET_PARAMS = ['signature', 'token', 'password', 'key', 'code', 'secret', 'email'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->is(...self::SKIP) || $request->attributes->get('firewall.banned')) {
            return;
        }

        try {
            RequestLog::create([
                'method' => $request->method(),
                'path' => Str::limit($this->redactedUri($request), 1000, ''),
                'status' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - (defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT'))) * 1000),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'blocked' => (bool) $request->attributes->get('firewall.blocked'),
            ]);
        } catch (\Throwable) {
            // The table may not exist yet mid-deploy; a lost log row is not worth an error page.
        }
    }

    /**
     * Login links, password resets and email verification carry working
     * credentials in the URL, so their secret parts never reach the log.
     */
    private function redactedUri(Request $request): string
    {
        $path = '/'.ltrim($request->path(), '/');
        $path = preg_replace('#^/(reset-password|login/link/\d+/\w+|verify-email/\d+)/[^/]+$#', '/$1/REDACTED', $path);

        $query = collect($request->query())
            ->map(fn ($value, $key) => in_array(strtolower($key), self::SECRET_PARAMS, true) ? 'REDACTED' : $value)
            ->all();

        return $query ? $path.'?'.urldecode(http_build_query($query)) : $path;
    }
}
