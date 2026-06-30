<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'forgot')) {
            DB::statement('ALTER TABLE customers ADD forgot VARCHAR(10) NULL AFTER password');
        }
        if (!Schema::hasColumn('customers', 'verify_token')) {
            DB::statement('ALTER TABLE customers ADD verify_token VARCHAR(100) NULL AFTER forgot');
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumnIfExists('forgot');
            $table->dropColumnIfExists('verify_token');
        });
    }
};
