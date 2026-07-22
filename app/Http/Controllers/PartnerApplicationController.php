<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PartnerApplicationController extends Controller
{
    private const WEBHOOK_URL = 'https://services.leadconnectorhq.com/hooks/L7jo026RqsHNDYlE2J5q/webhook-trigger/f6418372-116d-4152-bd78-3fe346207e23';

    public function create(): Response
    {
        return Inertia::render('PartnerApply');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:255',
        ]);

        try {
            Http::timeout(6)->post(self::WEBHOOK_URL, $data);
        } catch (\Throwable $e) {
            Log::error('Verified Partner webhook submission failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['form' => 'Something went wrong submitting your application. Please try again.']);
        }

        return back()->with('submitted', true);
    }
}
