<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: products table is missing the `meta_image` column, but the
     * ProductController reads/writes it (store + update). The wholesale_products
     * table already has this column; the products table never received it.
     * This causes:
     *   "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'meta_image'".
     *
     * Defensive: only adds the column if it does not already exist, so it runs
     * safely on any environment (local + live).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'meta_image')) {
                $table->string('meta_image', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'meta_image')) {
                $table->dropColumn('meta_image');
            }
        });
    }
};
