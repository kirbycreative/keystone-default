<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->uuid('generation_submission_id')->nullable()->unique()->after('suggested_sites');
            $table->string('generation_remote_id')->nullable()->after('generation_submission_id');
            $table->string('generation_status')->nullable()->after('generation_remote_id');
            $table->text('generation_error')->nullable()->after('generation_status');
            $table->renameColumn('imports_started_at', 'generation_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->dropUnique(['generation_submission_id']);
            $table->dropColumn([
                'generation_submission_id',
                'generation_remote_id',
                'generation_status',
                'generation_error',
            ]);
            $table->renameColumn('generation_started_at', 'imports_started_at');
        });
    }
};
