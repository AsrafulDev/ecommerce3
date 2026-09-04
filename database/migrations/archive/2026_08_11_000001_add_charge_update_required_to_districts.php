<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add `charge_update_required` flag to districts so an admin can "mark"
     * a district/area for a shipping-charge update from the District CRUD.
     */
    public function up(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            if (!Schema::hasColumn('districts', 'charge_update_required')) {
                $table->boolean('charge_update_required')->default(0)->after('partialpayment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            if (Schema::hasColumn('districts', 'charge_update_required')) {
                $table->dropColumn('charge_update_required');
            }
        });
    }
};
