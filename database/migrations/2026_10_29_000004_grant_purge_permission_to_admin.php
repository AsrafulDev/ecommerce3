<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The super-admin purge (transaction control) is gated by purge-financial-transactions. The
 * permission was never created, so nobody could purge. It is created here and granted to the
 * Admin role, which the super admin (user 1) holds. Other staff can be given it explicitly.
 */
return new class extends Migration {
    private const NAME = 'purge-financial-transactions';

    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => self::NAME, 'guard_name' => 'admin']);

        $role = Role::where('name', 'admin')->where('guard_name', 'admin')->first();
        if ($role) {
            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', self::NAME)->where('guard_name', 'admin')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
