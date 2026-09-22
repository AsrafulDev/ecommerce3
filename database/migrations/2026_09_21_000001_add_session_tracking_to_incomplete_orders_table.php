<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Session tracking for abandoned-checkout leads.
 *
 * `session_id` lets one visitor session own a single row, and `attempts` keeps
 * every distinct number/address that session tried — so the "same client, three
 * different phone numbers" case collapses to one row without losing the signal.
 *
 * Defensive hasColumn() guards: this repo applies migrations to live databases
 * that may already carry some of these columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('incomplete_orders')) {
            return;
        }

        Schema::table('incomplete_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('incomplete_orders', 'session_id')) {
                $table->string('session_id', 255)->nullable()->after('address')->index();
            }

            if (!Schema::hasColumn('incomplete_orders', 'attempts')) {
                $table->longText('attempts')->nullable()->after('items');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('incomplete_orders')) {
            return;
        }

        Schema::table('incomplete_orders', function (Blueprint $table) {
            if (Schema::hasColumn('incomplete_orders', 'session_id')) {
                $table->dropIndex(['session_id']);
                $table->dropColumn('session_id');
            }

            if (Schema::hasColumn('incomplete_orders', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
};
