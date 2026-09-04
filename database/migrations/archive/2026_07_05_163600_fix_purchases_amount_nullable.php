<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'amount')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->decimal('amount', 15, 2)->default(0)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'amount')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->decimal('amount', 15, 2)->default(null)->nullable(false)->change();
            });
        }
    }
};
