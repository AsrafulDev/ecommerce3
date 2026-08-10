<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-step attachments for warranty claims. Each row belongs to a
     * warranty_claim_stage (a "step" of the claim timeline) and stores a
     * reference to an image/PDF (usually inside the shared media gallery,
     * public/uploads/media/warranty/).
     */
    public function up(): void
    {
        Schema::create('warranty_claim_stage_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warranty_claim_stage_id')->index();
            $table->string('file_path', 500);
            $table->string('file_name', 255)->nullable();
            $table->string('file_type', 20)->nullable();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claim_stage_attachments');
    }
};
