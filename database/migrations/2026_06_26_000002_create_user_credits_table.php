<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_pack_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('episode_limit');
            $table->unsignedInteger('revision_credits_granted');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->enum('status', ['available', 'spent'])->default('available');
            $table->timestamp('purchased_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credits');
    }
};
