<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Landing page permissions. A user who has landing-create and landing-edit can build landing pages;
 * landing-admin sees and edits every landing page and may change its template. The Admin role
 * already passes every check (AppServiceProvider Gate::before).
 */
return new class extends Migration {
    private const NAMES = ['landing-list', 'landing-create', 'landing-edit', 'landing-admin'];

    public function up(): void
    {
        foreach (self::NAMES as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')->whereIn('name', self::NAMES)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
