<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the configurable admin login address. NULL means the default "/login".
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('general_settings', 'admin_login_path')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->string('admin_login_path', 64)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('general_settings', 'admin_login_path')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('admin_login_path');
            });
        }
    }
};
