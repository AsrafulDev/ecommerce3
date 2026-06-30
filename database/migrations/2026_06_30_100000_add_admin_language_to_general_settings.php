<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', 'admin_language')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->string('admin_language', 10)->default('en')->after('default_language');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('general_settings') && Schema::hasColumn('general_settings', 'admin_language')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('admin_language');
            });
        }
    }
};
