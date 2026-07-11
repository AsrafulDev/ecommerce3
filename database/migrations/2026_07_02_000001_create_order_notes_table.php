<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_notes')) {
            Schema::create('order_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('order_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->text('content');
                $table->string('type', 20)->default('info');
                // type: info, warning, success, danger
                $table->string('source', 30)->default('admin');
                // source: admin, system, courier, customer
                $table->json('metadata')->nullable();
                // metadata: stores extra context (e.g. old_status, new_status, courier_name)
                $table->timestamps();

                $table->foreign('order_id')
                    ->references('id')->on('orders')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('set null');

                $table->index(['order_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');
    }
};
