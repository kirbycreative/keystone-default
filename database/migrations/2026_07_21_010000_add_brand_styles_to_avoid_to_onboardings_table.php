<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->text('brand_styles_to_avoid')->nullable()->after('brand_personality_voice');
        });
    }

    public function down(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->dropColumn('brand_styles_to_avoid');
        });
    }
};
