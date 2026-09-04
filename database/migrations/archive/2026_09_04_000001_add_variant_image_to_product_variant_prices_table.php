<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a single primary image per variant (Color × Size combo).
     * Media gallery for a variant stays in `productimages` (color_id/size_id).
     */
    public function up(): void
    {
        Schema::table('product_variant_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variant_prices', 'image')) {
                if (Schema::hasColumn('product_variant_prices', 'barcode')) {
                    $table->string('image', 255)->nullable()->after('barcode');
                } else {
                    $table->string('image', 255)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variant_prices', function (Blueprint $table) {
            if (Schema::hasColumn('product_variant_prices', 'image')) {
                $table->dropColumn('image');
            }
        });
    }
};
