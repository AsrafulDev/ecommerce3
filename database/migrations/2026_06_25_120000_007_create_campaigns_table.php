<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('campaigns')) {
            Schema::create('campaigns', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->string('date', 55);
            $table->text('short_description');
            $table->string('review', 255);
            $table->text('description');
            $table->text('image_one');
            $table->text('image_two')->nullable();
            $table->text('image_three')->nullable();
            $table->string('status', 55);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->longText('page_design')->nullable();
            $table->longText('page_html')->nullable();
            $table->longText('page_css')->nullable();
            });
        }

Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
                $table->index('product_id');
            }
        });

Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'sections')) {
                $table->json('sections')->nullable()->after('page_css');
            }
        });

Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'labels')) {
                $table->json('labels')->nullable()->after('sections');
            }
        });

Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'features')) {
                $table->json('features')->nullable()->after('labels');
            }
        });

Schema::table('campaigns', function (Blueprint $table) {
            $cols = ['problem', 'solution', 'benefits', 'trust', 'faq', 'cta'];
            foreach ($cols as $col) {
                if (!Schema::hasColumn('campaigns', $col)) {
                    $table->json($col)->nullable()->after('features');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
