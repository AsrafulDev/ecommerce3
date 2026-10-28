<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The owner's filing frequency for the 9.1 return (A5): 'monthly' or 'quarterly'.
 * Null means not decided, and the return stays not final until it is chosen.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'vat_filing_frequency')) {
                $table->string('vat_filing_frequency', 10)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        if (Schema::hasColumn('general_settings', 'vat_filing_frequency')) {
            Schema::table('general_settings', fn (Blueprint $t) => $t->dropColumn('vat_filing_frequency'));
        }
    }
};
