<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_warranty_tiers', function (Blueprint $table) {
            if (!Schema::hasColumn('product_warranty_tiers', 'variant_id')) {
                $table->bigInteger('variant_id')->unsigned()->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_warranty_tiers', function (Blueprint $table) {
            $table->dropColumn('variant_id');
        });
    }
};
