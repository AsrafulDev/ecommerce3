<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_reviews')) {
            Schema::create('campaign_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->string('image', 255);
            $table->integer('campaign_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_reviews');
    }
};
