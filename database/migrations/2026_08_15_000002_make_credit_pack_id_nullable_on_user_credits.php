<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Admin credit gifts have no underlying pack, so the ledger's credit_pack_id
    // must allow null. Change only the column nullability, leaving the existing
    // foreign key in place.
    public function up(): void
    {
        Schema::table('user_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_pack_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_pack_id')->nullable(false)->change();
        });
    }
};
