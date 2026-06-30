<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_suggestions', function (Blueprint $table): void {
            // Which model + task produced this response, so client feedback can strike the right model.
            $table->string('ai_model')->nullable()->after('suggested_copy');
            $table->string('ai_task')->nullable()->after('ai_model');
            // Client's "did this live up to your expectations?" answer (null = not rated yet).
            $table->boolean('ai_feedback')->nullable()->after('ai_task');
            $table->timestamp('ai_feedback_at')->nullable()->after('ai_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('page_suggestions', function (Blueprint $table): void {
            $table->dropColumn(['ai_model', 'ai_task', 'ai_feedback', 'ai_feedback_at']);
        });
    }
};
