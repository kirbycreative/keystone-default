<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->uuid('site_layout_request_id')->nullable()->unique()->after('generation_started_at');
            $table->string('site_layout_remote_id')->nullable()->after('site_layout_request_id');
            $table->string('site_layout_status')->nullable()->after('site_layout_remote_id');
            $table->text('site_layout_error')->nullable()->after('site_layout_status');
        });
    }

    public function down(): void
    {
        Schema::table('onboardings', function (Blueprint $table): void {
            $table->dropUnique(['site_layout_request_id']);
            $table->dropColumn(['site_layout_request_id', 'site_layout_remote_id', 'site_layout_status', 'site_layout_error']);
        });
    }
};
