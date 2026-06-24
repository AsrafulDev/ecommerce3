<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 255);
            $table->string('mobile', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('details')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('contact_messages'); }
};
