<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL ENUM column cannot be altered via Schema builder directly.
        // The column currently accepts only 'physical','digital' but the
        // application also stores 'simple' and 'variable'.
        DB::statement("ALTER TABLE products MODIFY COLUMN product_type ENUM('simple','variable','digital','physical') NOT NULL DEFAULT 'simple'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN product_type ENUM('physical','digital') NOT NULL DEFAULT 'physical'");
    }
};
