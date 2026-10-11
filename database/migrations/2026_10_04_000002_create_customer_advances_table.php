<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('customer_advances', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('customer_id'); $table->decimal('amount', 15, 2); $table->string('payment_method', 55)->default('Cash'); $table->string('trx_note')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps(); $table->index('customer_id'); }); }
    public function down(): void { Schema::dropIfExists('customer_advances'); }
};
