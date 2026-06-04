<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episode_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('title');
            $table->longText('content');
            $table->timestamps();

            $table->index(['episode_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_versions');
    }
};
