<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Many-to-many pivot linking a ShippingCharge (zone + amount) to the
     * district/area rows it applies to.
     */
    public function up(): void
    {
        if (!Schema::hasTable('shipping_charge_district')) {
            Schema::create('shipping_charge_district', function (Blueprint $table) {
                $table->unsignedInteger('shipping_charge_id');
                $table->unsignedInteger('district_id');
                $table->timestamps();

                $table->primary(['shipping_charge_id', 'district_id']);
                $table->index('district_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_charge_district');
    }
};
