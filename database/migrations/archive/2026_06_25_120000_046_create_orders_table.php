<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoice_id', 55);
            $table->integer('amount');
            $table->integer('discount');
            $table->integer('shipping_charge');
            $table->integer('customer_id');
            $table->string('ip_address', 45)->nullable();
            $table->string('order_status', 55);
            $table->text('note')->nullable();
            $table->text('order_note')->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->string('coupon_code', 50)->nullable();
            $table->string('courier_type', 255)->nullable();
            $table->string('courier_tracking_id', 255)->nullable();
            $table->timestamp('courier_sent_at')->nullable();
            $table->decimal('fraud_success_rate', 5,2)->nullable();
            $table->decimal('pathao_rate', 5,2)->nullable();
            $table->decimal('redx_rate', 5,2)->nullable();
            $table->decimal('steadfast_rate', 5,2)->nullable();
            $table->tinyInteger('is_duplicate_order')->default(0);
            $table->integer('duplicate_order_count')->default(0);
            $table->decimal('duplicate_order_rate', 5,2)->nullable();
            $table->dateTime('last_duplicate_order_date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }

if (!Schema::hasColumn('orders', 'order_type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('order_type', 20)->default('online')->after('payment_status');
                // Values: online, pos, cod
            });
        }

Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('orders', 'due_amount')) {
                $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            }
        });

        // Backfill: paid/completed orders → paid_amount = amount, due = 0
        DB::table('orders')
            ->whereIn('payment_status', ['paid', 'completed', 'success', 'approved'])
            ->update(['paid_amount' => DB::raw('amount'), 'due_amount' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
