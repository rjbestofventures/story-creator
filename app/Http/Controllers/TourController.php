<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TourController extends Controller
{
    /** Mark the first-time onboarding tour as seen so it never auto-runs again. */
    public function complete(Request $request)
    {
        $request->user()->forceFill(['tour_completed_at' => now()])->save();

        return response()->noContent();
    }
}
