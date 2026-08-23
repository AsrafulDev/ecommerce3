<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: subcategories & childcategories tables have the same `meta_decription`
     * typo as categories did. The controllers/forms use `meta_description`, so
     * saving a subcategory/childcategory fails on live:
     *   "Unknown column 'meta_description' in 'field list'".
     *
     * Defensive: each step checks whether the column already exists, so this runs
     * safely on any environment (local + live).
     */
    public function up(): void
    {
        foreach (['subcategories', 'childcategories'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                // Fix the typo'd column name: meta_decription → meta_description
                if (Schema::hasColumn($table, 'meta_decription')
                    && !Schema::hasColumn($table, 'meta_description')) {
                    $t->renameColumn('meta_decription', 'meta_description');
                } elseif (!Schema::hasColumn($table, 'meta_description')) {
                    $t->text('meta_description')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // No-op: renaming back would break the app; the typo'd name is not used.
    }
};