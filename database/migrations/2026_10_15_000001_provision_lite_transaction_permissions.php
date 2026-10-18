<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates the Core Transactions permission rows (funds, expenses, operational
 * reports) that the Transactions sidebar and the Lite routes already check.
 *
 * Rows only. This migration grants NOTHING to any role: role assignments are an
 * explicit, reviewed decision made in the Roles screen. The admin role and the
 * super admin keep full access through the Gate override regardless.
 */
return new class extends Migration {
    private const PERMISSIONS = [
        'fund-list', 'fund-create', 'fund-edit',
        'expense-list', 'expense-create', 'expense-edit',
        'report-view', 'order-report', 'purchase-report', 'expense-report', 'stock-report', 'profit-loss-report',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Remove only rows that no role holds. A rollback must never silently strip
     * a permission an operator has already assigned.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', self::PERMISSIONS)
            ->whereNotIn('id', DB::table('role_has_permissions')->select('permission_id'))
            ->whereNotIn('id', DB::table('model_has_permissions')->select('permission_id'))
            ->delete();
    }
};
