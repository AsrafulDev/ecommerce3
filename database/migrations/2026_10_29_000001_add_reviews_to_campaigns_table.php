<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer reviews shown on a campaign page. The campaign form saves them as a list,
 * so the column was missing from the schema and every campaign save failed.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('campaigns')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'reviews')) {
                $table->longText('reviews')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('campaigns') && Schema::hasColumn('campaigns', 'reviews')) {
            Schema::table('campaigns', fn (Blueprint $t) => $t->dropColumn('reviews'));
        }
    }
};
