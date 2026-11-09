<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved product detail page designs (Single Layout). Exactly one is active; visitors see it on every product page.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('single_layouts')) {
            Schema::create('single_layouts', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->json('settings');
                $table->boolean('is_active')->default(false)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('single_layouts');
    }
};
