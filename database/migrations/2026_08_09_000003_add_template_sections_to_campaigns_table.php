<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add JSON content columns for the new landing-page template sections:
 * problem (pain grid), solution (benefits), benefits (stats/arc), trust badges,
 * faq (q+a loop), cta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $cols = ['problem', 'solution', 'benefits', 'trust', 'faq', 'cta'];
            foreach ($cols as $col) {
                if (!Schema::hasColumn('campaigns', $col)) {
                    $table->json($col)->nullable()->after('features');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $cols = ['problem', 'solution', 'benefits', 'trust', 'faq', 'cta'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('campaigns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
