<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaigns')) {
            Schema::create('campaigns', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->string('date', 55);
            $table->text('short_description');
            $table->string('review', 255);
            $table->text('description');
            $table->text('image_one');
            $table->text('image_two')->nullable();
            $table->text('image_three')->nullable();
            $table->string('status', 55);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->longText('page_design')->nullable();
            $table->longText('page_html')->nullable();
            $table->longText('page_css')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
