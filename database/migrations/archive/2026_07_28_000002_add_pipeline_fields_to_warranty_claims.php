<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('warranty_claims', 'product_received_at')) {
                $table->timestamp('product_received_at')->nullable()->after('claimed_at');
            }
            if (!Schema::hasColumn('warranty_claims', 'receive_challan_no')) {
                $table->string('receive_challan_no', 50)->nullable()->after('product_received_at');
            }
            if (!Schema::hasColumn('warranty_claims', 'receive_notes')) {
                $table->text('receive_notes')->nullable()->after('receive_challan_no');
            }
            if (!Schema::hasColumn('warranty_claims', 'sent_to_supplier_at')) {
                $table->timestamp('sent_to_supplier_at')->nullable()->after('receive_notes');
            }
            if (!Schema::hasColumn('warranty_claims', 'supplier_challan_no')) {
                $table->string('supplier_challan_no', 50)->nullable()->after('sent_to_supplier_at');
            }
            if (!Schema::hasColumn('warranty_claims', 'sent_supplier_id')) {
                $table->foreignId('sent_supplier_id')->nullable()->after('supplier_challan_no')
                      ->constrained('suppliers')->nullOnDelete();
            }
            if (!Schema::hasColumn('warranty_claims', 'supplier_send_notes')) {
                $table->text('supplier_send_notes')->nullable()->after('sent_supplier_id');
            }
            if (!Schema::hasColumn('warranty_claims', 'returned_from_supplier_at')) {
                $table->timestamp('returned_from_supplier_at')->nullable()->after('supplier_send_notes');
            }
            if (!Schema::hasColumn('warranty_claims', 'supplier_return_challan_no')) {
                $table->string('supplier_return_challan_no', 50)->nullable()->after('returned_from_supplier_at');
            }
            if (!Schema::hasColumn('warranty_claims', 'replacement_sn')) {
                $table->string('replacement_sn', 100)->nullable()->after('supplier_return_challan_no');
            }
            if (!Schema::hasColumn('warranty_claims', 'return_type')) {
                $table->enum('return_type', ['repaired', 'replaced', 'refunded'])->nullable()->after('replacement_sn');
            }
            if (!Schema::hasColumn('warranty_claims', 'supplier_return_notes')) {
                $table->text('supplier_return_notes')->nullable()->after('return_type');
            }
            if (!Schema::hasColumn('warranty_claims', 'ready_for_delivery_at')) {
                $table->timestamp('ready_for_delivery_at')->nullable()->after('supplier_return_notes');
            }
            if (!Schema::hasColumn('warranty_claims', 'delivery_challan_no')) {
                $table->string('delivery_challan_no', 50)->nullable()->after('ready_for_delivery_at');
            }
            if (!Schema::hasColumn('warranty_claims', 'delivered_to_customer_at')) {
                $table->timestamp('delivered_to_customer_at')->nullable()->after('delivery_challan_no');
            }
            if (!Schema::hasColumn('warranty_claims', 'delivery_notes')) {
                $table->text('delivery_notes')->nullable()->after('delivered_to_customer_at');
            }
            if (!Schema::hasColumn('warranty_claims', 'supplier_charge')) {
                $table->decimal('supplier_charge', 15, 2)->nullable()->after('delivery_notes');
            }
            if (!Schema::hasColumn('warranty_claims', 'customer_charge')) {
                $table->decimal('customer_charge', 15, 2)->nullable()->after('supplier_charge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warranty_claims', function (Blueprint $table) {
            $columns = [
                'product_received_at', 'receive_challan_no', 'receive_notes',
                'sent_to_supplier_at', 'supplier_challan_no', 'sent_supplier_id', 'supplier_send_notes',
                'returned_from_supplier_at', 'supplier_return_challan_no', 'replacement_sn', 'return_type', 'supplier_return_notes',
                'ready_for_delivery_at', 'delivery_challan_no', 'delivered_to_customer_at', 'delivery_notes',
                'supplier_charge', 'customer_charge',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('warranty_claims', $col)) {
                    if ($col === 'sent_supplier_id') {
                        $table->dropForeign(['warranty_claims_sent_supplier_id_foreign']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
