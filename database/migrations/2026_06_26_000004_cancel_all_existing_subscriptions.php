<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_subscriptions')
            ->whereIn('status', ['active', 'trialing'])
            ->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        // Irreversible data migration
    }
};
