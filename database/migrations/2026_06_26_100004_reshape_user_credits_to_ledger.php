<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_credits', function (Blueprint $table) {
            if (! Schema::hasColumn('user_credits', 'credits_granted')) {
                $table->unsignedInteger('credits_granted')->default(0)->after('credit_pack_id');
            }
            if (! Schema::hasColumn('user_credits', 'amount_paid')) {
                $table->unsignedInteger('amount_paid')->nullable()->after('credits_granted');
            }
            if (! Schema::hasColumn('user_credits', 'source')) {
                $table->string('source')->default('online')->after('amount_paid');
            }
        });

        // Backfill ledger fields from legacy story-credit rows (only while old columns exist)
        if (Schema::hasColumn('user_credits', 'episode_limit')) {
            DB::table('user_credits')->orderBy('id')->each(function ($row) {
                DB::table('user_credits')->where('id', $row->id)->update([
                    'credits_granted' => (int) ($row->episode_limit ?? 0) + (int) ($row->revision_credits_granted ?? 0),
                    'source' => $row->stripe_checkout_session_id ? 'online' : 'grant',
                ]);
            });
        }

        if (Schema::hasColumn('user_credits', 'status')) {
            // Add a replacement index covering user_id BEFORE dropping the old
            // (user_id, status) index — on MySQL that index serves the user_id
            // foreign key, so it can't be dropped until another covering index exists.
            // Wrapped so re-running after a partial failure (index already added) is safe.
            try {
                Schema::table('user_credits', function (Blueprint $table) {
                    $table->index(['user_id', 'source']);
                });
            } catch (\Throwable $e) {
                // index already exists from a previous partial run — ignore
            }

            Schema::table('user_credits', function (Blueprint $table) {
                $table->dropIndex('user_credits_user_id_status_index');
            });
            Schema::table('user_credits', function (Blueprint $table) {
                $table->dropColumn(['status', 'episode_limit', 'revision_credits_granted']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_credits', function (Blueprint $table) {
            $table->string('status')->default('available');
            $table->unsignedInteger('episode_limit')->default(0);
            $table->unsignedInteger('revision_credits_granted')->default(0);
            $table->dropColumn(['credits_granted', 'amount_paid', 'source']);
        });
    }
};
