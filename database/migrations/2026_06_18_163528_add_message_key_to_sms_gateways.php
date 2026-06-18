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
        Schema::table('sms_gateways', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_gateways', 'message_key')) {
                $table->string('message_key', 50)->default('message')->after('phone_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_gateways', function (Blueprint $table) {
            $table->dropColumn(['message_key']);
        });
    }
};
