<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `labels` JSON column to campaigns — stores the dynamic heading/label
 * texts for the landing page (nav, hero, sections, order form, footer).
 * Empty value for a key = that heading is hidden on the page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'labels')) {
                $table->json('labels')->nullable()->after('sections');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'labels')) {
                $table->dropColumn('labels');
            }
        });
    }
};
