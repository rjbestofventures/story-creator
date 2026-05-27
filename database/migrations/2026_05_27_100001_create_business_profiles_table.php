<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('business_url')->nullable();
            $table->string('industry')->nullable();
            $table->text('origin_story');      // Q1: How did you get started?
            $table->text('the_work');          // Q2: What do you actually do / who do you help?
            $table->text('big_picture');       // Q3: What's the bigger vision / why does it matter?
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
