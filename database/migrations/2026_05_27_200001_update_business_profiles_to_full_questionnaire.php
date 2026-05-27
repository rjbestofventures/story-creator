<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn(['origin_story', 'the_work', 'big_picture']);
            $table->json('answers')->after('industry'); // [{question, answer, section}, ...]
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn('answers');
            $table->text('origin_story');
            $table->text('the_work');
            $table->text('big_picture');
        });
    }
};
