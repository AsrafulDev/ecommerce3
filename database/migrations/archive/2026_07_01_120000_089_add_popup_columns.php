<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('popups', function (Blueprint $table) {
            if (!Schema::hasColumn('popups', 'btn_text')) {
                $table->string('btn_text', 255)->nullable()->after('link');
            }
            if (!Schema::hasColumn('popups', 'offer_end_text')) {
                $table->string('offer_end_text', 255)->nullable()->after('btn_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('popups', function (Blueprint $table) {
            $columns = ['btn_text', 'offer_end_text'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('popups', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
