<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->unsignedSmallInteger('episode_limit')->default(12);
            $table->unsignedSmallInteger('stories_per_month')->default(2);
            $table->unsignedSmallInteger('refine_monthly')->default(12);
            $table->unsignedInteger('price_monthly')->default(0);
            $table->unsignedInteger('price_yearly')->default(0);
            $table->unsignedSmallInteger('trial_months')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
