<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fund_transactions', 'transaction_category')) {
            Schema::table('fund_transactions', function (Blueprint $table): void {
                $table->string('transaction_category', 50)->nullable()->after('source_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fund_transactions', 'transaction_category')) {
            Schema::table('fund_transactions', fn (Blueprint $table) => $table->dropColumn('transaction_category'));
        }
    }
};
