<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            if (!Schema::hasColumn('themes', 'sidebar_bg_color')) {
                $table->string('sidebar_bg_color', 20)->nullable();
            }
            if (!Schema::hasColumn('themes', 'sidebar_text_color')) {
                $table->string('sidebar_text_color', 20)->nullable();
            }
            if (!Schema::hasColumn('themes', 'topbar_bg_color')) {
                $table->string('topbar_bg_color', 20)->nullable();
            }
            if (!Schema::hasColumn('themes', 'admin_card_bg')) {
                $table->string('admin_card_bg', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['sidebar_bg_color', 'sidebar_text_color', 'topbar_bg_color', 'admin_card_bg']);
        });
    }
};
