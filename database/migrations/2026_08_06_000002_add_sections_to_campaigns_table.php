<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `sections` JSON column to campaigns — stores per-section show/hide
 * flags for the landing page (hero, details, features, video, products, review, offer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'sections')) {
                $table->json('sections')->nullable()->after('page_css');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'sections')) {
                $table->dropColumn('sections');
            }
        });
    }
};
