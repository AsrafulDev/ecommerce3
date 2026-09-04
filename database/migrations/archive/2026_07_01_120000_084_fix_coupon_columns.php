<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new columns (with correct names) alongside old ones
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'type')) {
                $table->string('type', 20)->default('fixed')->after('code');
            }
            if (!Schema::hasColumn('coupons', 'value')) {
                $table->decimal('value', 14, 2)->default(0)->after('type');
            }
            if (!Schema::hasColumn('coupons', 'min_purchase')) {
                $table->decimal('min_purchase', 14, 2)->default(0)->after('value');
            }
            if (!Schema::hasColumn('coupons', 'valid_from')) {
                $table->date('valid_from')->nullable()->after('min_purchase');
            }
            if (!Schema::hasColumn('coupons', 'valid_to')) {
                $table->date('valid_to')->nullable()->after('valid_from');
            }
        });

        // 2. Copy data from old columns to new columns
        if (Schema::hasColumn('coupons', 'discount_type') && Schema::hasColumn('coupons', 'type')) {
            DB::statement("UPDATE `coupons` SET `type` = `discount_type`");
        }
        if (Schema::hasColumn('coupons', 'discount') && Schema::hasColumn('coupons', 'value')) {
            DB::statement("UPDATE `coupons` SET `value` = `discount`");
        }
        if (Schema::hasColumn('coupons', 'min_order_amount') && Schema::hasColumn('coupons', 'min_purchase')) {
            DB::statement("UPDATE `coupons` SET `min_purchase` = `min_order_amount`");
        }
        if (Schema::hasColumn('coupons', 'expiry_date') && Schema::hasColumn('coupons', 'valid_to')) {
            DB::statement("UPDATE `coupons` SET `valid_to` = `expiry_date`");
        }

        // 3. Drop old columns
        Schema::table('coupons', function (Blueprint $table) {
            $oldColumns = ['discount_type', 'discount', 'min_order_amount', 'expiry_date'];
            foreach ($oldColumns as $col) {
                if (Schema::hasColumn('coupons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        // Add back old columns
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'discount_type')) {
                $table->enum('discount_type', ['fixed', 'percent'])->default('fixed')->after('code');
            }
            if (!Schema::hasColumn('coupons', 'discount')) {
                $table->decimal('discount', 14, 2)->default(0)->after('discount_type');
            }
            if (!Schema::hasColumn('coupons', 'min_order_amount')) {
                $table->decimal('min_order_amount', 14, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('coupons', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('min_order_amount');
            }
        });

        // Copy data back
        if (Schema::hasColumn('coupons', 'type') && Schema::hasColumn('coupons', 'discount_type')) {
            DB::statement("UPDATE `coupons` SET `discount_type` = `type`");
        }
        if (Schema::hasColumn('coupons', 'value') && Schema::hasColumn('coupons', 'discount')) {
            DB::statement("UPDATE `coupons` SET `discount` = `value`");
        }
        if (Schema::hasColumn('coupons', 'min_purchase') && Schema::hasColumn('coupons', 'min_order_amount')) {
            DB::statement("UPDATE `coupons` SET `min_order_amount` = `min_purchase`");
        }
        if (Schema::hasColumn('coupons', 'valid_to') && Schema::hasColumn('coupons', 'expiry_date')) {
            DB::statement("UPDATE `coupons` SET `expiry_date` = `valid_to`");
        }

        // Drop new columns
        Schema::table('coupons', function (Blueprint $table) {
            $newColumns = ['type', 'value', 'min_purchase', 'valid_from', 'valid_to'];
            foreach ($newColumns as $col) {
                if (Schema::hasColumn('coupons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
