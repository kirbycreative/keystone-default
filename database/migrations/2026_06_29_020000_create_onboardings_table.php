<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboardings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step')->default(1);
            $table->boolean('dns_verified')->default(false);

            // Step 2 — company & brand
            $table->string('company_name')->nullable();
            $table->text('slogans')->nullable();
            $table->string('business_category')->nullable();
            $table->string('region')->nullable();
            $table->string('region_scope')->nullable();
            $table->string('logo_disk')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('primary_colors')->nullable();

            // Step 3 — inspiration
            $table->json('inspiration_domains')->nullable();
            $table->json('suggested_sites')->nullable();
            $table->timestamp('imports_started_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboardings');
    }
};
