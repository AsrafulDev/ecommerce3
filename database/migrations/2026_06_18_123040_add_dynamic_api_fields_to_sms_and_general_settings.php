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
        // SMS Gateway - add method & phone_key
        Schema::table('sms_gateways', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_gateways', 'method')) {
                $table->string('method', 10)->default('POST')->after('url');
            }
            if (!Schema::hasColumn('sms_gateways', 'phone_key')) {
                $table->string('phone_key', 50)->default('number')->after('method');
            }
        });

        // General Settings - add duplicate order API dynamic fields
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'duplicate_order_api_url')) {
                $table->string('duplicate_order_api_url')->nullable()->after('duplicate_order_api_key');
            }
            if (!Schema::hasColumn('general_settings', 'duplicate_order_method')) {
                $table->string('duplicate_order_method', 10)->default('POST')->after('duplicate_order_api_url');
            }
            if (!Schema::hasColumn('general_settings', 'duplicate_order_phone_key')) {
                $table->string('duplicate_order_phone_key', 50)->default('phone')->after('duplicate_order_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_gateways', function (Blueprint $table) {
            $table->dropColumn(['method', 'phone_key']);
        });

        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['duplicate_order_api_url', 'duplicate_order_method', 'duplicate_order_phone_key']);
        });
    }
};
