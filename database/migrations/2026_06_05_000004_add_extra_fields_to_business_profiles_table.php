<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->text('biography')->nullable()->after('industry');
            $table->string('linkedin_url')->nullable()->after('biography');
            $table->string('social_url')->nullable()->after('linkedin_url');
            $table->text('website_content')->nullable()->after('social_url');
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn(['biography', 'linkedin_url', 'social_url', 'website_content']);
        });
    }
};
