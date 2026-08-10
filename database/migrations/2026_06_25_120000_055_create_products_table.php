<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->integer('category_id');
            $table->integer('brand_id')->nullable();
            $table->string('product_code', 255);
            $table->integer('purchase_price');
            $table->integer('old_price')->nullable();
            $table->integer('new_price');
            $table->decimal('advance_amount', 14,2)->default(0);
            $table->integer('stock');
            $table->string('pro_unit', 50)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_image', 255)->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('topsale')->nullable();
            $table->tinyInteger('flashsale')->default(0);
            $table->tinyInteger('feature_product')->nullable();
            $table->tinyInteger('campaign_id')->nullable();
            $table->tinyInteger('status');
            $table->string('product_type', 20)->default('simple');
            $table->tinyInteger('is_digital')->default(0);
            $table->string('digital_file', 255)->nullable();
            $table->integer('download_limit')->nullable();
            $table->integer('download_expire_days')->nullable();
            $table->timestamp('facebook_posted_at')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->tinyInteger('is_wholesale')->default(0);
            $table->decimal('wholesale_price', 14,2)->nullable();
            $table->integer('min_wholesale_quantity')->default(1);
            $table->tinyInteger('free_delivery')->default(0);
            $table->string('pro_video_type', 20)->nullable();
            $table->string('pro_video_path', 300)->nullable();
            $table->bigInteger('vendor_id')->unsigned()->nullable();
            $table->unique('product_code');
            $table->index('vendor_id');
            });
        }

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

Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'supplier_price')) {
                $table->decimal('supplier_price', 14, 2)->default(0)->after('purchase_price');
            }
        });

if (!Schema::hasColumn('products', 'warranty_method')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('warranty_method', 20)->default('active')->after('free_delivery')
                    ->comment('active: show warranty, inactive: hide warranty, hidden: hide from frontend but keep data');
            });
        }

Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'publish_status')) {
                $table->string('publish_status', 20)->default('active')->after('status');
            }
        });

        // Backfill: active(1) -> 'active', inactive(0) -> 'draft'
        DB::table('products')
            ->where('status', 1)
            ->whereNull('publish_status')
            ->update(['publish_status' => 'active']);

        DB::table('products')
            ->where('status', 0)
            ->whereNull('publish_status')
            ->update(['publish_status' => 'draft']);

Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'meta_image')) {
                $table->string('meta_image', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
