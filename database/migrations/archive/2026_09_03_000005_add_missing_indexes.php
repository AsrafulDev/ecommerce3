<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders table indexes
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!$this->indexExists('orders', 'orders_customer_id_index')) {
                    $table->index('customer_id');
                }
                if (!$this->indexExists('orders', 'orders_order_status_index')) {
                    $table->index('order_status');
                }
                // Unique constraint on invoice_id (deduplicated by Phase 5)
                if (!$this->indexExists('orders', 'orders_invoice_id_unique')) {
                    $table->unique('invoice_id');
                }
            });
        }

        // Order details table indexes
        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                if (!$this->indexExists('order_details', 'order_details_order_id_index')) {
                    $table->index('order_id');
                }
                if (!$this->indexExists('order_details', 'order_details_product_id_index')) {
                    $table->index('product_id');
                }
            });
        }

        // Payments table indexes
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!$this->indexExists('payments', 'payments_order_id_index')) {
                    $table->index('order_id');
                }
            });
        }

        // Refunds table indexes
        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                if (!$this->indexExists('refunds', 'refunds_order_id_index')) {
                    $table->index('order_id');
                }
            });
        }

        // Shippings table indexes
        if (Schema::hasTable('shippings')) {
            Schema::table('shippings', function (Blueprint $table) {
                if (!$this->indexExists('shippings', 'shippings_order_id_index')) {
                    $table->index('order_id');
                }
            });
        }

        // Carts table indexes
        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                if (!$this->indexExists('carts', 'carts_customer_id_index')) {
                    $table->index('customer_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'orders', 'orders_customer_id_index');
                $this->dropIndexIfExists($table, 'orders', 'orders_order_status_index');
                $this->dropIndexIfExists($table, 'orders', 'orders_invoice_id_unique');
            });
        }

        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'order_details', 'order_details_order_id_index');
                $this->dropIndexIfExists($table, 'order_details', 'order_details_product_id_index');
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'payments', 'payments_order_id_index');
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'refunds', 'refunds_order_id_index');
            });
        }

        if (Schema::hasTable('shippings')) {
            Schema::table('shippings', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'shippings', 'shippings_order_id_index');
            });
        }

        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'carts', 'carts_customer_id_index');
            });
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = \DB::select("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?", [
                \DB::getDatabaseName(),
                $table,
                $index,
            ]);
            return count($indexes) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function dropIndexIfExists(Blueprint $table, string $tableName, string $index): void
    {
        if ($this->indexExists($tableName, $index)) {
            try {
                // Use direct SQL to drop by name (safer than schema builder guessing)
                \DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `{$index}`");
            } catch (\Throwable $e) {
                // Index may not exist or already dropped
            }
        }
    }
};
