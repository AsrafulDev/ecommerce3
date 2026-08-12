<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an admin-managed license key column to general_settings.
     * The hardcoded config value in config/updater.php remains the default
     * when this column is empty.
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('general_settings', 'license_key')) {
                $table->string('license_key', 100)->nullable()->after('app_version');
            }
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'license_key')) {
                $table->dropColumn('license_key');
            }
        });
    }
};
