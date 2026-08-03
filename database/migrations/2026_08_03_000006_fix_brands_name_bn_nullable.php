<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: brands table `name_bn` was created as a non-nullable column with no
     * default value, but the brand form has no `name_bn` field. Under MySQL
     * strict mode this fails on every brand insert with:
     *   "SQLSTATE[HY000]: General error: 1364 Field 'name_bn' doesn't have a default value".
     *
     * Make the column nullable so inserts without a Bengali name succeed.
     * Defensive: only alters the column if it already exists (safe on any env).
     */
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'name_bn')) {
                $table->string('name_bn', 255)->nullable()->change();
            } else {
                $table->string('name_bn', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'name_bn')) {
                $table->string('name_bn', 255)->nullable(false)->change();
            }
        });
    }
};
