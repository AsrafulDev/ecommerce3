<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing pages built from a template. All content lives in one JSON column, keyed by
 * element id (see App\Services\Landing\TemplateRegistry), so templates can differ freely.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('landing_templates')) {
            return;
        }

        Schema::create('landing_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->string('template_key', 40);
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->json('content')->nullable();
            $table->boolean('status')->default(false)->index();
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->unsignedInteger('coupon_id')->nullable()->index();
            $table->string('extra_discount_type', 10)->nullable();
            $table->decimal('extra_discount_value', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_templates');
    }
};
