<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_transactions')) {
            Schema::table('fund_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fund_transactions')) {
            Schema::table('fund_transactions', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }
    }
};
