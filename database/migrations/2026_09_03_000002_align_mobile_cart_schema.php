<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: the `carts` create migration predates the mobile (Flutter) Cart model +
     * Api\Mobile\CartController/OrderController, which read/write `quantity`,
     * `size_id` and `color_id`. Those columns exist on the live DB (drift) but not
     * after a fresh migrate — the mobile cart/order flow 500'd with "Unknown column
     * 'quantity'". Add them guarded and backfill quantity from legacy `qty`.
     * (Surfaced while verifying UPDATE-PLAN Phase 2.5.)
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'quantity')) {
                $table->integer('quantity')->default(0)->after('product_id');
            }
            if (!Schema::hasColumn('carts', 'size_id')) {
                $table->bigInteger('size_id')->unsigned()->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('carts', 'color_id')) {
                $table->bigInteger('color_id')->unsigned()->nullable()->after('size_id');
            }
            // The mobile CartController never writes product_name/qty (it uses
            // quantity) → both must be nullable (live DB already is).
            $table->string('product_name', 255)->nullable()->change();
            $table->integer('qty')->nullable()->change();
        });

        // Backfill quantity from the legacy qty column for pre-existing rows.
        DB::table('carts')
            ->where(function ($q) {
                $q->whereNull('quantity')->orWhere('quantity', 0);
            })
            ->update(['quantity' => DB::raw('qty')]);
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            foreach (['quantity', 'size_id', 'color_id'] as $col) {
                if (Schema::hasColumn('carts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
