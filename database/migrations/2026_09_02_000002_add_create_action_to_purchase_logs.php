<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand purchase_logs.action enum to include 'create' so purchase
     * creation events can be recorded alongside edit/delete.
     */
    public function up(): void
    {
        $col = DB::selectOne("SHOW COLUMNS FROM purchase_logs WHERE Field = 'action'");
        $type = $col->Type ?? '';
        if ($type !== '' && stripos($type, "'create'") === false) {
            DB::statement("ALTER TABLE purchase_logs MODIFY action ENUM('create','edit','delete') NOT NULL");
        }
    }

    public function down(): void
    {
        $col = DB::selectOne("SHOW COLUMNS FROM purchase_logs WHERE Field = 'action'");
        $type = $col->Type ?? '';
        if ($type !== '' && stripos($type, "'create'") !== false) {
            DB::statement("ALTER TABLE purchase_logs MODIFY action ENUM('edit','delete') NOT NULL");
        }
    }
};
