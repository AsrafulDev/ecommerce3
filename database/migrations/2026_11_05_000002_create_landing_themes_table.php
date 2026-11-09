<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custom colour themes for landing pages. The built-in presets stay in code (App\Services\Landing\LandingThemes).
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('landing_themes')) {
            Schema::create('landing_themes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->char('primary', 7);
                $table->char('accent', 7);
                $table->char('background', 7);
                $table->char('text', 7);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_themes');
    }
};
