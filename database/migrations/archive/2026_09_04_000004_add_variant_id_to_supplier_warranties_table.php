<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link a supplier (purchase) warranty to the specific variant it covers.
     * No DB-level FK on purpose: `product_variant_prices` rows are deleted and
     * re-created when a product is edited, which would cascade/null the link.
     * Referential integrity is enforced in the app layer.
     */
    public function up(): void
    {
        Schema::table('supplier_warranties', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_warranties', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
                $table->index('variant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_warranties', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_warranties', 'variant_id')) {
                $table->dropIndex(['variant_id']);
                $table->dropColumn('variant_id');
            }
        });
    }
};
