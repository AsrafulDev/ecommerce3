<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missingColumns = [
            'facebook_page_username' => "ALTER TABLE general_settings ADD facebook_page_username VARCHAR(255) NULL",
            'primary_color' => "ALTER TABLE general_settings ADD primary_color VARCHAR(7) DEFAULT '#0d6efd'",
            'secodery_color' => "ALTER TABLE general_settings ADD secodery_color VARCHAR(7) DEFAULT '#198754'",
            'footer_color' => "ALTER TABLE general_settings ADD footer_color VARCHAR(7) DEFAULT '#222222'",
            'copyright_color' => "ALTER TABLE general_settings ADD copyright_color VARCHAR(7) DEFAULT '#111111'",
            'order_policy' => "ALTER TABLE general_settings ADD order_policy TEXT NULL",
            'checkout_note' => "ALTER TABLE general_settings ADD checkout_note TEXT NULL",
        ];

        foreach ($missingColumns as $col => $sql) {
            if (Schema::hasTable('general_settings') && !Schema::hasColumn('general_settings', $col)) {
                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'facebook_page_username', 'primary_color', 'secodery_color',
            'footer_color', 'copyright_color', 'checkout_note', 'order_policy'
        ];
        foreach ($columns as $col) {
            if (Schema::hasTable('general_settings') && Schema::hasColumn('general_settings', $col)) {
                Schema::table('general_settings', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
