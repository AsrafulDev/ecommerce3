<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('email', 55);
            $table->string('ratting', 4);
            $table->text('review');
            $table->integer('product_id');
            $table->string('status', 55);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }

Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('product_id');
                $table->index('customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
