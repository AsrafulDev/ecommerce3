<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('theme_id')->nullable()->after('status');
            $table->unsignedBigInteger('active_layout_id')->nullable()->after('theme_id');

            $table->foreign('theme_id')->references('id')->on('themes')->onDelete('set null');
            $table->foreign('active_layout_id')->references('id')->on('homepage_layouts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropForeign(['theme_id']);
            $table->dropForeign(['active_layout_id']);
            $table->dropColumn(['theme_id', 'active_layout_id']);
        });
    }
};
