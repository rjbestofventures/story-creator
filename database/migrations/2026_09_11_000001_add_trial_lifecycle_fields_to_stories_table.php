<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->boolean('created_on_trial')->default(false)->after('is_demo');
            $table->timestamp('episodes_unlocked_at')->nullable()->after('created_on_trial');
            $table->timestamp('episodes_reactivated_at')->nullable()->after('episodes_unlocked_at');
            $table->timestamp('reactivation_notified_at')->nullable()->after('episodes_reactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn([
                'created_on_trial',
                'episodes_unlocked_at',
                'episodes_reactivated_at',
                'reactivation_notified_at',
            ]);
        });
    }
};
