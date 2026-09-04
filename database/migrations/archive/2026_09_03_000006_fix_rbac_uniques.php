<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Deduplicate roles by (name, guard_name) before adding unique constraint
        if (Schema::hasTable('roles')) {
            $this->deduplicateRoles();
            Schema::table('roles', function (Blueprint $table) {
                // Drop the old unique on just 'name'
                $this->dropUniqueIfExists('roles', 'roles_name_unique');
                $this->dropUniqueIfExists('roles', 'name');
                // Add composite unique
                if (!$this->indexExists('roles', 'roles_name_guard_name_unique')) {
                    $table->unique(['name', 'guard_name']);
                }
            });
        }

        // 2. Deduplicate permissions by (name, guard_name) before adding unique constraint
        if (Schema::hasTable('permissions')) {
            $this->deduplicatePermissions();
            Schema::table('permissions', function (Blueprint $table) {
                // Drop the old unique on just 'name'
                $this->dropUniqueIfExists('permissions', 'permissions_name_unique');
                $this->dropUniqueIfExists('permissions', 'name');
                // Add composite unique
                if (!$this->indexExists('permissions', 'permissions_name_guard_name_unique')) {
                    $table->unique(['name', 'guard_name']);
                }
            });
        }

        // 3. Add composite primary key to model_has_roles (role_id, model_id, model_type)
        // Note: assumes no exact duplicates exist; if they do, manually clean before migrating
        if (Schema::hasTable('model_has_roles')) {
            // Drop old index if exists
            $this->dropUniqueIfExists('model_has_roles', 'model_has_roles_model_id_model_type_role_id_unique');
            
            // Only add primary key if one doesn't exist
            if (!$this->hasPrimaryKey('model_has_roles')) {
                Schema::table('model_has_roles', function (Blueprint $table) {
                    $table->primary(['role_id', 'model_id', 'model_type']);
                });
            }
        }

        // 4. Add composite primary key to model_has_permissions (permission_id, model_id, model_type)
        // Note: assumes no exact duplicates exist; if they do, manually clean before migrating
        if (Schema::hasTable('model_has_permissions')) {
            // Drop old index if exists
            $this->dropUniqueIfExists('model_has_permissions', 'model_has_permissions_model_id_model_type_permission_id_unique');
            
            // Only add primary key if one doesn't exist
            if (!$this->hasPrimaryKey('model_has_permissions')) {
                Schema::table('model_has_permissions', function (Blueprint $table) {
                    $table->primary(['permission_id', 'model_id', 'model_type']);
                });
            }
        }
    }

    public function down(): void
    {
        // Reverse: drop the composite uniques/PKs (cannot restore originals without backup)
        if (Schema::hasTable('roles')) {
            $this->dropUniqueIfExists('roles', 'roles_name_guard_name_unique');
        }

        if (Schema::hasTable('permissions')) {
            $this->dropUniqueIfExists('permissions', 'permissions_name_guard_name_unique');
        }

        if (Schema::hasTable('model_has_roles')) {
            $this->dropPrimaryKeyIfExists('model_has_roles');
        }

        if (Schema::hasTable('model_has_permissions')) {
            $this->dropPrimaryKeyIfExists('model_has_permissions');
        }
    }

    protected function deduplicateRoles(): void
    {
        // Keep only the latest (highest id) for each (name, guard_name) pair
        \DB::statement('
            DELETE FROM roles WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MAX(id) as id FROM roles GROUP BY name, guard_name
                ) as keep_ids
            )
        ');
    }

    protected function deduplicatePermissions(): void
    {
        // Keep only the latest (highest id) for each (name, guard_name) pair
        \DB::statement('
            DELETE FROM permissions WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MAX(id) as id FROM permissions GROUP BY name, guard_name
                ) as keep_ids
            )
        ');
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

    protected function hasPrimaryKey(string $table): bool
    {
        try {
            $indexes = \DB::select("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = 'PRIMARY'", [
                \DB::getDatabaseName(),
                $table,
            ]);
            return count($indexes) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function dropPrimaryKeyIfExists(string $table): void
    {
        if ($this->hasPrimaryKey($table)) {
            try {
                \DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");
            } catch (\Throwable $e) {
                // Already dropped or error
            }
        }
    }

    protected function dropUniqueIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            try {
                \DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            } catch (\Throwable $e) {
                // Index may not exist
            }
        }
    }
};
