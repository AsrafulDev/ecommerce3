<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_warranty_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('tier_name');
            $table->string('warranty_type')->default('none');
            $table->integer('warranty_days')->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('additional_cost', 12, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('badge')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['product_id', 'warranty_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_warranty_tiers');
    }
};
