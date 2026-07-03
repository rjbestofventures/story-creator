<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditPack;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProvisionController extends Controller
{
    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'pack' => ['sometimes', 'string', Rule::exists('credit_packs', 'slug')],
        ]);

        $pack = isset($validated['pack'])
            ? CreditPack::where('slug', $validated['pack'])->firstOrFail()
            : null;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
            'is_verified_partner' => $pack?->type === 'partner',
        ]);

        $user->assignRole('user');

        if ($pack) {
            $pack->grantTo($user);
        }

        $token = Password::createToken($user);
        $user->notify(new AccountCreatedNotification($token));

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_verified_partner' => $user->is_verified_partner,
                'credits' => $user->credits,
            ],
            'pack' => $pack?->slug,
        ], 201);
    }
}
