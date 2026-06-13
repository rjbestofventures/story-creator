<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $adminId = session('impersonating_admin_id');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? array_merge(
                    $request->user()->only('id', 'name', 'email'),
                    ['roles' => $request->user()->getRoleNames()->toArray()]
                ) : null,
            ],
            'impersonating' => $adminId ? [
                'admin_id' => $adminId,
                'admin_name' => User::find($adminId)?->name,
            ] : null,
        ];
    }
}
