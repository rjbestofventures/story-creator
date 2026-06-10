<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            'basic' => 36,
            'premium' => 54,
            'professional' => 72,
        ];

        foreach ($updates as $slug => $refine) {
            Plan::where('slug', $slug)->update(['refine_monthly' => $refine]);
        }
    }

    public function down(): void
    {
        $original = [
            'basic' => 12,
            'premium' => 24,
            'professional' => 48,
        ];

        foreach ($original as $slug => $refine) {
            Plan::where('slug', $slug)->update(['refine_monthly' => $refine]);
        }
    }
};
