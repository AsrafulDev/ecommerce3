<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'company', 'contact_person', 'tax_id', 'payment_terms',
                'lead_time', 'notes', 'is_active',
            ]);
        });
    }
};
