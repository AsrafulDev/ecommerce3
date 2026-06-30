<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'subcategory_id')) {
                $table->unsignedBigInteger('subcategory_id')->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('products', 'childcategory_id')) {
                $table->unsignedBigInteger('childcategory_id')->nullable()->after('subcategory_id');
            }
            if (!Schema::hasColumn('products', 'note')) {
                $table->text('note')->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('meta_description');
            }
            if (!Schema::hasColumn('products', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('products', 'reseller_price')) {
                $table->decimal('reseller_price', 10, 2)->nullable()->after('wholesale_price');
            }
            if (!Schema::hasColumn('products', 'pro_video')) {
                $table->string('pro_video')->nullable()->after('pro_video_type');
            }
            if (!Schema::hasColumn('products', 'sold')) {
                $table->integer('sold')->default(0)->after('stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumnIfExists('subcategory_id');
            $table->dropColumnIfExists('childcategory_id');
            $table->dropColumnIfExists('note');
            $table->dropColumnIfExists('meta_title');
            $table->dropColumnIfExists('meta_keywords');
            $table->dropColumnIfExists('reseller_price');
            $table->dropColumnIfExists('pro_video');
            $table->dropColumnIfExists('sold');
        });
    }
};
