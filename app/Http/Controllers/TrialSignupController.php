<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrialSignupRequest;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\TrialAccountCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TrialSignupController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('TrialSignup');
    }

    public function store(TrialSignupRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => "{$data['first_name']} {$data['last_name']}",
            'email' => $data['email'],
            'password' => Hash::make(Str::random(32)),
        ]);

        $user->assignRole('user');
        $user->becomeTemporaryPartner();

        $user->notify(new AccountCreatedNotification(Password::createToken($user)));
        TrialAccountCreatedNotification::sendFor($user, $data['phone']);

        $this->sendToCrm($data);

        return back()->with('submitted', true);
    }

    /**
     * Hand the lead to the CRM. The account already exists by now, so a CRM
     * outage is logged rather than shown to the person who signed up.
     */
    private function sendToCrm(array $data): void
    {
        $url = config('services.crm.trial_webhook');

        if (! $url) {
            return;
        }

        try {
            $response = Http::timeout(6)->post($url, $data + ['source' => 'storybot_trial_signup']);

            if ($response->failed()) {
                Log::error('Complementary Trial CRM webhook rejected the lead', ['status' => $response->status()]);
            }
        } catch (\Throwable $e) {
            Log::error('Complementary Trial CRM webhook failed', ['error' => $e->getMessage()]);
        }
    }
}
