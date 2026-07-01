<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_product')) {
            Schema::create('campaign_product', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('campaign_id')->unsigned();
                $table->bigInteger('product_id')->unsigned();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['campaign_id', 'product_id']);
                $table->index('campaign_id');
                $table->index('product_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_product');
    }
};
