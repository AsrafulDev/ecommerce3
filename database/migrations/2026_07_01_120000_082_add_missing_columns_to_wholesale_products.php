<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesale_products', function (Blueprint $table) {
            if (!Schema::hasColumn('wholesale_products', 'subcategory_id')) {
                $table->unsignedBigInteger('subcategory_id')->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('wholesale_products', 'childcategory_id')) {
                $table->unsignedBigInteger('childcategory_id')->nullable()->after('subcategory_id');
            }
            if (!Schema::hasColumn('wholesale_products', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('childcategory_id');
            }
            if (!Schema::hasColumn('wholesale_products', 'product_code')) {
                $table->string('product_code', 255)->nullable()->after('brand_id');
            }
            if (!Schema::hasColumn('wholesale_products', 'purchase_price')) {
                $table->decimal('purchase_price', 14, 2)->nullable()->after('product_code');
            }
            if (!Schema::hasColumn('wholesale_products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 14, 2)->nullable()->after('purchase_price');
            }
            if (!Schema::hasColumn('wholesale_products', 'retail_price')) {
                $table->decimal('retail_price', 14, 2)->nullable()->after('wholesale_price');
            }
            if (!Schema::hasColumn('wholesale_products', 'min_quantity')) {
                $table->integer('min_quantity')->default(1)->after('retail_price');
            }
            if (!Schema::hasColumn('wholesale_products', 'unit')) {
                $table->string('unit', 50)->nullable()->after('min_quantity');
            }
            if (!Schema::hasColumn('wholesale_products', 'approval_status')) {
                $table->string('approval_status', 20)->default('approved')->after('status');
            }
            if (!Schema::hasColumn('wholesale_products', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('wholesale_products', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('wholesale_products', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('wholesale_products', 'meta_keywords')) {
                $table->string('meta_keywords', 255)->nullable()->after('meta_description');
            }
            if (!Schema::hasColumn('wholesale_products', 'meta_image')) {
                $table->string('meta_image', 255)->nullable()->after('meta_keywords');
            }

            // Add index for created_by
            if (!Schema::hasColumn('wholesale_products', 'created_by') || !$this->hasIndex('wholesale_products', 'wholesale_products_created_by_index')) {
                // index will be added by the column creation above
            }
        });
    }

    public function down(): void
    {
        Schema::table('wholesale_products', function (Blueprint $table) {
            $columns = [
                'subcategory_id', 'childcategory_id', 'brand_id', 'product_code',
                'purchase_price', 'wholesale_price', 'retail_price', 'min_quantity',
                'unit', 'approval_status', 'created_by',
                'meta_title', 'meta_description', 'meta_keywords', 'meta_image',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('wholesale_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
        return array_key_exists($index, $indexes);
    }
};
