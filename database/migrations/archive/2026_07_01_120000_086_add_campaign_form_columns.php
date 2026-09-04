<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename existing 'date' column to 'deadline' (matches form field)
        if (Schema::hasColumn('campaigns', 'date') && !Schema::hasColumn('campaigns', 'deadline')) {
            DB::statement("ALTER TABLE `campaigns` CHANGE `date` `deadline` VARCHAR(55) NULL");
        }

        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'banner')) {
                $table->string('banner', 255)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('campaigns', 'banner_title')) {
                $table->string('banner_title', 255)->nullable()->after('banner');
            }
            if (!Schema::hasColumn('campaigns', 'top_title_1')) {
                $table->string('top_title_1', 255)->nullable()->after('deadline');
            }
            if (!Schema::hasColumn('campaigns', 'top_title_2')) {
                $table->string('top_title_2', 255)->nullable()->after('top_title_1');
            }
            if (!Schema::hasColumn('campaigns', 'heading_1')) {
                $table->string('heading_1', 255)->nullable()->after('top_title_2');
            }
            if (!Schema::hasColumn('campaigns', 'feature_1')) {
                $table->text('feature_1')->nullable()->after('heading_1');
            }
            if (!Schema::hasColumn('campaigns', 'feature_2')) {
                $table->text('feature_2')->nullable()->after('feature_1');
            }
            if (!Schema::hasColumn('campaigns', 'heading_2')) {
                $table->string('heading_2', 255)->nullable()->after('feature_2');
            }
            if (!Schema::hasColumn('campaigns', 'heading_3')) {
                $table->string('heading_3', 255)->nullable()->after('heading_2');
            }
            if (!Schema::hasColumn('campaigns', 'heading_4')) {
                $table->string('heading_4', 255)->nullable()->after('heading_3');
            }
            if (!Schema::hasColumn('campaigns', 'note')) {
                $table->text('note')->nullable()->after('heading_4');
            }
            if (!Schema::hasColumn('campaigns', 'billing_details')) {
                $table->text('billing_details')->nullable()->after('note');
            }
            if (!Schema::hasColumn('campaigns', 'video')) {
                $table->string('video', 300)->nullable()->after('billing_details');
            }
        });
    }

    public function down(): void
    {
        // Rename back
        if (Schema::hasColumn('campaigns', 'deadline') && !Schema::hasColumn('campaigns', 'date')) {
            DB::statement("ALTER TABLE `campaigns` CHANGE `deadline` `date` VARCHAR(55) NULL");
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $columns = [
                'banner', 'banner_title', 'top_title_1', 'top_title_2',
                'heading_1', 'feature_1', 'feature_2', 'heading_2',
                'heading_3', 'heading_4', 'note', 'billing_details', 'video',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('campaigns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
