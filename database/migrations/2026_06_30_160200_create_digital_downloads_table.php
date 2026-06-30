<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('digital_downloads')) {
            Schema::create('digital_downloads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('product_id');
                $table->string('token', 100)->unique();
                $table->string('file_path')->nullable();
                $table->integer('download_count')->default(0);
                $table->integer('remaining_downloads')->default(9999);
                $table->integer('max_downloads')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_downloads');
    }
};
