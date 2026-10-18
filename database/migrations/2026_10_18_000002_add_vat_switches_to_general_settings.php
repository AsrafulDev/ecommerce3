<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two switches for VAT. Both default to "off / not decided": VAT is only calculated
 * once the owner has enabled it AND chosen whether prices include VAT. Until then
 * no line receives a VAT snapshot, so existing sales and purchases are unchanged.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'vat_enabled')) {
                $table->boolean('vat_enabled')->default(false);
            }
            if (!Schema::hasColumn('general_settings', 'vat_prices_include_vat')) {
                $table->boolean('vat_prices_include_vat')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        $present = array_values(array_filter(
            ['vat_enabled', 'vat_prices_include_vat'],
            fn ($c) => Schema::hasColumn('general_settings', $c)
        ));
        if ($present) {
            Schema::table('general_settings', fn (Blueprint $t) => $t->dropColumn($present));
        }
    }
};
