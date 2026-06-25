<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cron_job_settings')) {
            Schema::create('cron_job_settings', function (Blueprint $table) {
            $table->id();
            $table->string('job_key', 80);
            $table->string('job_title', 150);
            $table->text('job_description')->nullable();
            $table->tinyInteger('is_enabled')->default(1);
            $table->smallInteger('frequency_minutes')->unsigned()->default(10);
            $table->smallInteger('order_limit')->unsigned()->default(50);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status', 20)->nullable();
            $table->text('last_run_result')->nullable();
            $table->integer('last_updated_count')->unsigned()->default(0);
            $table->integer('last_failed_count')->unsigned()->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('job_key');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_job_settings');
    }
};
