<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a string publish status (active / draft / private) to products.
     *
     * The legacy `status` tinyint (0/1) is kept in sync for backward compatibility —
     * the storefront & API still query `where('status', 1)`. The new column
     * lets admins distinguish draft vs private.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'publish_status')) {
                $table->string('publish_status', 20)->default('active')->after('status');
            }
        });

        // Backfill: active(1) -> 'active', inactive(0) -> 'draft'
        DB::table('products')
            ->where('status', 1)
            ->whereNull('publish_status')
            ->update(['publish_status' => 'active']);

        DB::table('products')
            ->where('status', 0)
            ->whereNull('publish_status')
            ->update(['publish_status' => 'draft']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'publish_status')) {
                $table->dropColumn('publish_status');
            }
        });
    }
};
