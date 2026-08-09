<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `features` JSON column to campaigns — stores an array of feature items
 * for the landing page Features grid. Each item: { icon, image, title, text }.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'features')) {
                $table->json('features')->nullable()->after('labels');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'features')) {
                $table->dropColumn('features');
            }
        });
    }
};
