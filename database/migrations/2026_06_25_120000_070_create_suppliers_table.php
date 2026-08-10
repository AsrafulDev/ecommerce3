<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->decimal('total_purchase', 15,2)->default(0);
            $table->decimal('total_paid', 15,2)->default(0);
            $table->decimal('total_due', 15,2)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            });
        }

Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0)->after('address');
            }
            if (!Schema::hasColumn('suppliers', 'current_due')) {
                $table->decimal('current_due', 15, 2)->default(0)->after('opening_balance');
            }
        });

Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'company')) {
                $table->string('company', 255)->nullable()->after('address');
            }
            if (!Schema::hasColumn('suppliers', 'contact_person')) {
                $table->string('contact_person', 255)->nullable()->after('company');
            }
            if (!Schema::hasColumn('suppliers', 'tax_id')) {
                $table->string('tax_id', 100)->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('suppliers', 'payment_terms')) {
                $table->string('payment_terms', 50)->nullable()->after('tax_id');
            }
            if (!Schema::hasColumn('suppliers', 'lead_time')) {
                $table->integer('lead_time')->nullable()->after('payment_terms');
            }
            if (!Schema::hasColumn('suppliers', 'notes')) {
                $table->text('notes')->nullable()->after('lead_time');
            }
            if (!Schema::hasColumn('suppliers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
