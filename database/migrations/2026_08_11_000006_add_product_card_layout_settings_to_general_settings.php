<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds per-device product-card row limits (front page vs other pages) and the
 * card title line limit to general_settings.
 * Controlled from Admin → Theme System → Product Design.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('general_settings')) {
            Schema::table('general_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('general_settings', 'pc_home_desktop')) {
                    $table->unsignedTinyInteger('pc_home_desktop')->default(5)->after('product_card_style');
                }
                if (!Schema::hasColumn('general_settings', 'pc_home_laptop')) {
                    $table->unsignedTinyInteger('pc_home_laptop')->default(4)->after('pc_home_desktop');
                }
                if (!Schema::hasColumn('general_settings', 'pc_home_tablet')) {
                    $table->unsignedTinyInteger('pc_home_tablet')->default(3)->after('pc_home_laptop');
                }
                if (!Schema::hasColumn('general_settings', 'pc_home_phone')) {
                    $table->unsignedTinyInteger('pc_home_phone')->default(2)->after('pc_home_tablet');
                }
                if (!Schema::hasColumn('general_settings', 'pc_other_desktop')) {
                    $table->unsignedTinyInteger('pc_other_desktop')->default(4)->after('pc_home_phone');
                }
                if (!Schema::hasColumn('general_settings', 'pc_other_laptop')) {
                    $table->unsignedTinyInteger('pc_other_laptop')->default(3)->after('pc_other_desktop');
                }
                if (!Schema::hasColumn('general_settings', 'pc_other_tablet')) {
                    $table->unsignedTinyInteger('pc_other_tablet')->default(3)->after('pc_other_laptop');
                }
                if (!Schema::hasColumn('general_settings', 'pc_other_phone')) {
                    $table->unsignedTinyInteger('pc_other_phone')->default(2)->after('pc_other_tablet');
                }
                if (!Schema::hasColumn('general_settings', 'pc_title_lines')) {
                    $table->unsignedTinyInteger('pc_title_lines')->default(2)->after('pc_other_phone');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('general_settings')) {
            Schema::table('general_settings', function (Blueprint $table) {
                foreach (['pc_home_desktop', 'pc_home_laptop', 'pc_home_tablet', 'pc_home_phone',
                          'pc_other_desktop', 'pc_other_laptop', 'pc_other_tablet', 'pc_other_phone',
                          'pc_title_lines'] as $col) {
                    if (Schema::hasColumn('general_settings', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
