<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('versions')) {
            Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->date('release_date');
            $table->text('changelog')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('requires_migration')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('version');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
