<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || Schema::hasColumn('orders', 'user_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            // Nullable keeps existing orders valid; this is the admin assignee.
            $table->unsignedInteger('user_id')->nullable()->after('customer_id');
            $table->index('user_id', 'orders_user_id_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'user_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_user_id_index');
            $table->dropColumn('user_id');
        });
    }
};
