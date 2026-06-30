<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', 'default_language')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->string('default_language', 10)->default('en')->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('general_settings') && Schema::hasColumn('general_settings', 'default_language')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('default_language');
            });
        }
    }
};
