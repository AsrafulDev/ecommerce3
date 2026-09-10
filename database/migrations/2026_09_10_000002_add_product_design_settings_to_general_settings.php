<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        $columns = [
            'product_card_style' => fn (Blueprint $table) => $table->string('product_card_style', 50)->default('default'),
            'pc_home_desktop' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_home_desktop')->default(5),
            'pc_home_laptop' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_home_laptop')->default(4),
            'pc_home_tablet' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_home_tablet')->default(3),
            'pc_home_phone' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_home_phone')->default(2),
            'pc_other_desktop' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_other_desktop')->default(4),
            'pc_other_laptop' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_other_laptop')->default(3),
            'pc_other_tablet' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_other_tablet')->default(3),
            'pc_other_phone' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_other_phone')->default(2),
            'pc_title_lines' => fn (Blueprint $table) => $table->unsignedTinyInteger('pc_title_lines')->default(2),
            'pc_image_height' => fn (Blueprint $table) => $table->unsignedInteger('pc_image_height')->default(200),
        ];

        foreach ($columns as $name => $definition) {
            if (!Schema::hasColumn('general_settings', $name)) {
                Schema::table('general_settings', $definition);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('general_settings')) {
            return;
        }

        foreach (array_keys([
            'product_card_style' => true,
            'pc_home_desktop' => true,
            'pc_home_laptop' => true,
            'pc_home_tablet' => true,
            'pc_home_phone' => true,
            'pc_other_desktop' => true,
            'pc_other_laptop' => true,
            'pc_other_tablet' => true,
            'pc_other_phone' => true,
            'pc_title_lines' => true,
            'pc_image_height' => true,
        ]) as $name) {
            if (Schema::hasColumn('general_settings', $name)) {
                Schema::table('general_settings', fn (Blueprint $table) => $table->dropColumn($name));
            }
        }
    }
};