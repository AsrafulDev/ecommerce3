<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warranty_challans')) {
            Schema::create('warranty_challans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warranty_claim_id')->constrained('warranty_claims')->cascadeOnDelete();
                $table->enum('challan_type', ['receive', 'send_to_supplier', 'receive_return', 'delivery']);
                $table->string('challan_no', 50)->unique();
                $table->json('challan_data');
                $table->unsignedInteger('generated_by')->nullable();
                $table->foreign('generated_by')->references('id')->on('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_challans');
    }
};
