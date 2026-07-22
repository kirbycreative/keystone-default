<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_assets', function (Blueprint $table): void {
            $table->string('remote_id')->nullable()->unique()->after('ingestion_result');
            $table->string('remote_status')->nullable()->after('remote_id');
            $table->text('remote_error')->nullable()->after('remote_status');
        });
    }

    public function down(): void
    {
        Schema::table('content_assets', function (Blueprint $table): void {
            $table->dropUnique(['remote_id']);
            $table->dropColumn(['remote_id', 'remote_status', 'remote_error']);
        });
    }
};
