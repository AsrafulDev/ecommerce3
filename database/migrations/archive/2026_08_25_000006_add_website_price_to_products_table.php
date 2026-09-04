<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'website_price')) {
                $table->decimal('website_price', 14, 2)->nullable()->after('new_price')
                    ->comment('Cached price from the active website batch (fast catalog queries)');
            }
            if (!Schema::hasColumn('products', 'website_stock')) {
                $table->integer('website_stock')->default(0)->after('website_price')
                    ->comment('Cached sum of website-enabled batch stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['website_price', 'website_stock']);
        });
    }
};
