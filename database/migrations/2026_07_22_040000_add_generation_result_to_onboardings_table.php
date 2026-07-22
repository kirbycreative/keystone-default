<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->json('generation_result')->nullable()->after('generation_error');
        });
    }

    public function down(): void
    {
        Schema::table('onboardings', fn (Blueprint $table) => $table->dropColumn('generation_result'));
    }
};
