<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('facebook_page_settings')) {
            Schema::create('facebook_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_id', 255)->nullable();
            $table->text('page_access_token')->nullable();
            $table->string('page_name', 255)->nullable();
            $table->tinyInteger('auto_post_new_products')->default(0);
            $table->text('post_template')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_page_settings');
    }
};
