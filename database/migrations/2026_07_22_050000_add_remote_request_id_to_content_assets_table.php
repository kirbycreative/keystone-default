<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_assets', function (Blueprint $table): void {
            $table->uuid('remote_request_id')->nullable()->unique()->after('ingestion_result');
        });
    }

    public function down(): void
    {
        Schema::table('content_assets', function (Blueprint $table): void {
            $table->dropUnique(['remote_request_id']);
            $table->dropColumn('remote_request_id');
        });
    }
};
