<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fund_transactions')) {
            Schema::create('fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->decimal('amount', 15,2);
            $table->string('direction', 10);
            $table->text('note')->nullable();
            $table->decimal('balance_before', 15,2)->default(0);
            $table->decimal('balance_after', 15,2)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transactions');
    }
};
