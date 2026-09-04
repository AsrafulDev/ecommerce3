<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: categories table is missing columns the CategoryController expects.
     * - Base migration created `meta_decription` (typo) instead of `meta_description`.
     * - `front_view` and `icon` were never added by any migration (added manually on
     *   some environments only) → updating a category fails on live:
     *   "Unknown column 'meta_description' in 'SET'".
     *
     * Defensive: each step checks whether the column already exists, so this runs
     * safely on any environment (local + live).
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Fix the typo'd column name: meta_decription → meta_description
            if (Schema::hasColumn('categories', 'meta_decription')
                && !Schema::hasColumn('categories', 'meta_description')) {
                $table->renameColumn('meta_decription', 'meta_description');
            } elseif (!Schema::hasColumn('categories', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }

            // front_view (show on front page toggle)
            if (!Schema::hasColumn('categories', 'front_view')) {
                $table->tinyInteger('front_view')->default(1);
            }

            // icon (small category icon)
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'icon')) {
                $table->dropColumn('icon');
            }
            if (Schema::hasColumn('categories', 'front_view')) {
                $table->dropColumn('front_view');
            }
        });
    }
};
