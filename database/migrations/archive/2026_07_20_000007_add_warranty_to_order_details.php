<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('warranty_tier_id')->nullable()->after('product_color');
            $table->foreign('warranty_tier_id')->references('id')->on('product_warranty_tiers')->nullOnDelete();
            $table->decimal('warranty_price', 12, 2)->default(0)->after('warranty_tier_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['warranty_tier_id']);
            $table->dropColumn(['warranty_tier_id', 'warranty_price']);
        });
    }
};
