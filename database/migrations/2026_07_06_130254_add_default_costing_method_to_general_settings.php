<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('general_settings')) {
            Schema::table('general_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('general_settings', 'default_costing_method')) {
                    $table->enum('default_costing_method', ['lifo', 'fifo', 'average'])->default('average');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('general_settings') && Schema::hasColumn('general_settings', 'default_costing_method')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('default_costing_method');
            });
        }
    }
};
