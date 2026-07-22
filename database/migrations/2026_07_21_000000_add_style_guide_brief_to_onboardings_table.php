<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->text('company_description')->nullable()->after('business_category');
            $table->text('ideal_customer')->nullable()->after('company_description');
            $table->text('brand_personality_voice')->nullable()->after('ideal_customer');
            $table->text('existing_brand_assets')->nullable()->after('brand_personality_voice');
            $table->string('primary_color', 32)->nullable()->after('primary_colors');
            $table->string('secondary_color', 32)->nullable()->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->dropColumn([
                'company_description',
                'ideal_customer',
                'brand_personality_voice',
                'existing_brand_assets',
                'primary_color',
                'secondary_color',
            ]);
        });
    }
};
