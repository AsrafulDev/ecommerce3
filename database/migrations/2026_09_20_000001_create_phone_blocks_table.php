<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phone-number block list (anti-fraud), mirroring `ip_blocks`.
 *
 * `phone_normalized` is the canonical digits-only form used for matching so a
 * block on 01712345678 also catches +8801712345678 / 8801712345678.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('phone_blocks')) {
            Schema::create('phone_blocks', function (Blueprint $table) {
                $table->increments('id');
                $table->string('phone', 30);
                $table->string('phone_normalized', 20)->nullable()->index();
                $table->text('reason')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_blocks');
    }
};
