<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->integer('category_id');
            $table->integer('brand_id')->nullable();
            $table->string('product_code', 255);
            $table->integer('purchase_price');
            $table->integer('old_price')->nullable();
            $table->integer('new_price');
            $table->decimal('advance_amount', 14,2)->default(0);
            $table->integer('stock');
            $table->string('pro_unit', 50)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('topsale')->nullable();
            $table->tinyInteger('flashsale')->default(0);
            $table->tinyInteger('feature_product')->nullable();
            $table->tinyInteger('campaign_id')->nullable();
            $table->tinyInteger('status');
            $table->string('product_type', 20)->default('simple');
            $table->tinyInteger('is_digital')->default(0);
            $table->string('digital_file', 255)->nullable();
            $table->integer('download_limit')->nullable();
            $table->integer('download_expire_days')->nullable();
            $table->timestamp('facebook_posted_at')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->tinyInteger('is_wholesale')->default(0);
            $table->decimal('wholesale_price', 14,2)->nullable();
            $table->integer('min_wholesale_quantity')->default(1);
            $table->tinyInteger('free_delivery')->default(0);
            $table->string('pro_video_type', 20)->nullable();
            $table->string('pro_video_path', 300)->nullable();
            $table->bigInteger('vendor_id')->unsigned()->nullable();
            $table->unique('product_code');
            $table->index('vendor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
