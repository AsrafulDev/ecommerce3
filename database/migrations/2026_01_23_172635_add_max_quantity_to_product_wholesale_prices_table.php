<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_wholesale_prices')) {
            Schema::table('product_wholesale_prices', function (Blueprint $table) {
                $table->integer('max_qty')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // No safe revert
    }
};
