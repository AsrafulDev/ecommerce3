<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Security audit log — month-wise user activity tracking.
     * NOTE: users.id is INT UNSIGNED, so user_id is unsignedInteger (no FK
     * constraint to avoid errno 150 issues seen elsewhere in this codebase).
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('user_name', 191)->nullable();     // snapshot of user name
            $table->string('module', 50)->index();            // product/order/stock/warranty/purchase...
            $table->string('action', 50);                     // create/update/delete/price_change/status/...
            $table->string('description', 500)->nullable();   // human-readable summary
            $table->string('model_type', 191)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('data')->nullable();                 // old/new values for audit trail
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['module', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
