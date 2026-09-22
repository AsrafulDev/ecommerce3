<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('social_media', 'link')) {
            Schema::table('social_media', function (Blueprint $table) {
                $table->string('link')->nullable()->after('icon');
            });
        }

        if (! Schema::hasColumn('social_media', 'color')) {
            Schema::table('social_media', function (Blueprint $table) {
                $table->string('color', 50)->nullable()->after('link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('social_media', 'color')) {
            Schema::table('social_media', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }

        if (Schema::hasColumn('social_media', 'link')) {
            Schema::table('social_media', function (Blueprint $table) {
                $table->dropColumn('link');
            });
        }
    }
};