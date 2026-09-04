<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('themes') && !Schema::hasColumn('themes', 'page_custom_css')) {
            Schema::table('themes', function (Blueprint $table) {
                $table->text('page_custom_css')->nullable()->after('custom_css');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('themes') && Schema::hasColumn('themes', 'page_custom_css')) {
            Schema::table('themes', function (Blueprint $table) {
                $table->dropColumn('page_custom_css');
            });
        }
    }
};
