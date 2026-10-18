<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VAT (Mushak) data foundation. Every column is nullable and nothing reads or
 * computes them yet: the rates, registration rules and inclusive/exclusive
 * pricing are still open business decisions. Filled-in values are snapshots
 * taken at sale or purchase time, so a later rate change never rewrites history.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('general_settings')) {
            Schema::table('general_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('general_settings', 'vat_bin')) {
                    $table->string('vat_bin', 20)->nullable();
                }
                if (!Schema::hasColumn('general_settings', 'vat_business_name')) {
                    $table->string('vat_business_name', 191)->nullable();
                }
                if (!Schema::hasColumn('general_settings', 'vat_business_address')) {
                    $table->string('vat_business_address', 500)->nullable();
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'vat_rate')) {
                    $table->decimal('vat_rate', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('products', 'vat_category')) {
                    $table->string('vat_category', 20)->nullable();
                }
                if (!Schema::hasColumn('products', 'hs_code')) {
                    $table->string('hs_code', 20)->nullable();
                }
            });
        }

        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                if (!Schema::hasColumn('order_details', 'vat_rate')) {
                    $table->decimal('vat_rate', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('order_details', 'taxable_value')) {
                    $table->decimal('taxable_value', 15, 2)->nullable();
                }
                if (!Schema::hasColumn('order_details', 'vat_amount')) {
                    $table->decimal('vat_amount', 15, 2)->nullable();
                }
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_items', 'vat_rate')) {
                    $table->decimal('vat_rate', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('purchase_items', 'taxable_value')) {
                    $table->decimal('taxable_value', 15, 2)->nullable();
                }
                if (!Schema::hasColumn('purchase_items', 'vat_amount')) {
                    $table->decimal('vat_amount', 15, 2)->nullable();
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'buyer_bin')) {
                    $table->string('buyer_bin', 20)->nullable();
                }
                if (!Schema::hasColumn('orders', 'mushak_invoice_no')) {
                    $table->string('mushak_invoice_no', 40)->nullable()->unique();
                }
            });
        }

        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'bin')) {
                    $table->string('bin', 20)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Columns are dropped only if they exist, so a partial install can roll back.
        $drop = [
            'general_settings' => ['vat_bin', 'vat_business_name', 'vat_business_address'],
            'products' => ['vat_rate', 'vat_category', 'hs_code'],
            'order_details' => ['vat_rate', 'taxable_value', 'vat_amount'],
            'purchase_items' => ['vat_rate', 'taxable_value', 'vat_amount'],
            'orders' => ['buyer_bin', 'mushak_invoice_no'],
            'suppliers' => ['bin'],
        ];

        foreach ($drop as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $present = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            if ($present) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn($present));
            }
        }
    }
};
