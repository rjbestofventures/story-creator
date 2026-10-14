<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('path', 1000);
            $table->unsignedSmallInteger('status');
            $table->unsignedInteger('duration_ms');
            $table->string('ip', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('blocked')->default(false);
            $table->timestamp('created_at')->index();

            $table->index(['status', 'created_at']);
            $table->index(['ip', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
