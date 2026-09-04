<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'draft_data')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->json('draft_data')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'draft_data')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('draft_data');
            });
        }
    }
};