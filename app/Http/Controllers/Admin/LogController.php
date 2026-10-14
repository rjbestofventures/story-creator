<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    /** How much of the end of laravel.log the Errors tab reads. */
    private const LOG_TAIL_BYTES = 512 * 1024;

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'method', 'ip', 'search']);
        $tab = $request->query('tab') === 'errors' ? 'errors' : 'requests';

        $since = now()->subDay();

        return Inertia::render('Admin/Logs', [
            'tab' => $tab,
            'filters' => $filters,
            'requests' => $tab === 'requests'
                ? RequestLog::with('user:id,name,email')
                    ->filter($filters)
                    ->latest('id')
                    ->paginate(50)
                    ->withQueryString()
                : null,
            'summary' => [
                'total' => RequestLog::where('created_at', '>=', $since)->count(),
                'errors' => RequestLog::where('created_at', '>=', $since)->where('status', '>=', 500)->count(),
                'blocked' => RequestLog::where('created_at', '>=', $since)->where('blocked', true)->count(),
                'not_found' => RequestLog::where('created_at', '>=', $since)->where('status', 404)->count(),
            ],
            'topIps' => RequestLog::where('created_at', '>=', $since)
                ->select('ip', DB::raw('count(*) as hits'))
                ->groupBy('ip')
                ->orderByDesc('hits')
                ->limit(5)
                ->get(),
            'errors' => $tab === 'errors' ? $this->recentErrors() : [],
            'retentionDays' => RequestLog::RETENTION_DAYS,
        ]);
    }

    /**
     * The newest entries in laravel.log, newest first, each split into its
     * header line and the stack trace beneath it.
     *
     * @return list<array{time:string,level:string,message:string,trace:string}>
     */
    private function recentErrors(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_readable($path)) {
            return [];
        }

        $size = filesize($path);
        $handle = fopen($path, 'r');
        fseek($handle, max(0, $size - self::LOG_TAIL_BYTES));
        $tail = stream_get_contents($handle);
        fclose($handle);

        preg_match_all(
            '/^\[(\d{4}-\d{2}-\d{2}[ T][\d:.+-]+)\] \w+\.(\w+): (.*?)(?=^\[\d{4}-\d{2}-\d{2}[ T]|\z)/ms',
            $tail,
            $matches,
            PREG_SET_ORDER,
        );

        return collect($matches)
            ->reverse()
            ->take(100)
            ->map(function (array $m) {
                [$message, $trace] = array_pad(explode("\n", trim($m[3]), 2), 2, '');

                return [
                    'time' => $m[1],
                    'level' => strtolower($m[2]),
                    'message' => mb_strimwidth($message, 0, 1000, '…'),
                    'trace' => mb_strimwidth($trace, 0, 6000, '…'),
                ];
            })
            ->values()
            ->all();
    }
}
