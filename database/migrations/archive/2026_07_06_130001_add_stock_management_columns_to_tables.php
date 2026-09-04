<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===== products table =====
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'barcode')) {
                    $table->string('barcode', 255)->nullable()->after('product_code');
                }
                if (!Schema::hasColumn('products', 'barcode_type')) {
                    $table->string('barcode_type', 20)->default('C128')->after('barcode');
                }
                if (!Schema::hasColumn('products', 'costing_method')) {
                    $table->enum('costing_method', ['lifo', 'fifo', 'average'])->default('average')->after('purchase_price');
                }
                if (!Schema::hasColumn('products', 'low_stock_threshold')) {
                    $table->integer('low_stock_threshold')->default(10)->after('stock');
                }
                if (!Schema::hasColumn('products', 'allow_negative_stock')) {
                    $table->boolean('allow_negative_stock')->default(false)->after('low_stock_threshold');
                }
                if (!Schema::hasColumn('products', 'weight')) {
                    $table->decimal('weight', 10, 2)->nullable()->after('pro_unit');
                }
            });
        }

        // ===== product_variant_prices table =====
        if (Schema::hasTable('product_variant_prices')) {
            Schema::table('product_variant_prices', function (Blueprint $table) {
                if (!Schema::hasColumn('product_variant_prices', 'barcode')) {
                    $table->string('barcode', 255)->nullable()->after('sku');
                }
            });
        }

        // ===== purchases table =====
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'costing_method')) {
                    $table->enum('costing_method', ['lifo', 'fifo', 'average'])->nullable()->after('invoice_no');
                }
            });
        }

        // ===== purchase_items table =====
        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_items', 'batch_no')) {
                    $table->string('batch_no', 50)->nullable()->after('returned_qty');
                }
                if (!Schema::hasColumn('purchase_items', 'mfg_date')) {
                    $table->date('mfg_date')->nullable()->after('batch_no');
                }
                if (!Schema::hasColumn('purchase_items', 'exp_date')) {
                    $table->date('exp_date')->nullable()->after('mfg_date');
                }
            });
        }

        // ===== order_details table =====
        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                if (!Schema::hasColumn('order_details', 'batch_ids')) {
                    $table->json('batch_ids')->nullable()->after('qty');
                }
                if (!Schema::hasColumn('order_details', 'cogs')) {
                    $table->decimal('cogs', 15, 2)->nullable()->after('batch_ids');
                }
            });
        }

        // ===== suppliers table =====
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'contact_person')) {
                    $table->string('contact_person', 255)->nullable()->after('address');
                }
                if (!Schema::hasColumn('suppliers', 'tax_id')) {
                    $table->string('tax_id', 100)->nullable()->after('contact_person');
                }
                if (!Schema::hasColumn('suppliers', 'payment_terms')) {
                    $table->string('payment_terms', 100)->nullable()->after('tax_id');
                }
                if (!Schema::hasColumn('suppliers', 'lead_time')) {
                    $table->integer('lead_time')->nullable()->after('payment_terms');
                }
                if (!Schema::hasColumn('suppliers', 'notes')) {
                    $table->text('notes')->nullable()->after('lead_time');
                }
                if (!Schema::hasColumn('suppliers', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert changes carefully
        $tables = ['products', 'product_variant_prices', 'purchases', 'purchase_items', 'order_details', 'suppliers'];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = [];
                    if ($tableName === 'products') {
                        $columns = ['barcode', 'barcode_type', 'costing_method', 'low_stock_threshold', 'allow_negative_stock', 'weight'];
                    } elseif ($tableName === 'product_variant_prices') {
                        $columns = ['barcode'];
                    } elseif ($tableName === 'purchases') {
                        $columns = ['costing_method'];
                    } elseif ($tableName === 'purchase_items') {
                        $columns = ['batch_no', 'mfg_date', 'exp_date'];
                    } elseif ($tableName === 'order_details') {
                        $columns = ['batch_ids', 'cogs'];
                    } elseif ($tableName === 'suppliers') {
                        $columns = ['contact_person', 'tax_id', 'payment_terms', 'lead_time', 'notes', 'is_active'];
                    }
                    foreach ($columns as $col) {
                        if (Schema::hasColumn($tableName, $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }
    }
};
