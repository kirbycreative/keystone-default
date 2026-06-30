<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('page_suggestions')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('summary');
            $table->text('rationale');
            $table->json('source_asset_ids');
            $table->json('suggested_copy')->nullable();
            $table->string('status')->default('suggested')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_suggestions');
    }
};
