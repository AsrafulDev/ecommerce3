<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ip_blocks')) {
            Schema::create('ip_blocks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip_no', 255);
            $table->text('reason');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_blocks');
    }
};
