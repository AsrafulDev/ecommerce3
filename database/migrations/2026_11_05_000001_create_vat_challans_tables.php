<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mushak 6.3 (tax challan) documents, their lines, an insert-only audit trail, and the document number sequences.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('vat_challans')) {
            Schema::create('vat_challans', function (Blueprint $table) {
                $table->id();
                $table->string('challan_no', 40)->unique();
                $table->string('status', 20)->default('issued')->index();
                $table->unsignedBigInteger('order_id')->index();
                // Set only while the challan is issued. The unique index is the database-level guard against two issued
                // challans for one sale. Voiding clears it, so the sale can be challaned again under a new number.
                $table->unsignedBigInteger('issued_order_id')->nullable()->unique();
                $table->string('invoice_no', 60)->nullable();
                $table->date('issue_date');
                $table->time('issue_time');
                $table->dateTime('issued_at');
                // Seller and buyer are copied at issue, so a later change to settings or the order never rewrites the challan.
                $table->string('seller_name', 191)->nullable();
                $table->string('seller_bin', 20)->nullable();
                $table->string('seller_address', 500)->nullable();
                $table->string('buyer_name', 191)->nullable();
                $table->string('buyer_bin', 20)->nullable();
                $table->string('buyer_address', 500)->nullable();
                $table->string('destination', 500)->nullable();
                $table->decimal('total_value', 15, 2)->default(0);
                $table->decimal('total_vat', 15, 2)->default(0);
                $table->decimal('total_with_tax', 15, 2)->default(0);
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->unsignedBigInteger('voided_by')->nullable();
                $table->dateTime('voided_at')->nullable();
                $table->string('void_reason', 500)->nullable();
                $table->string('pdf_path', 255)->nullable();
                $table->char('pdf_sha256', 64)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vat_challan_lines')) {
            Schema::create('vat_challan_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vat_challan_id')->constrained('vat_challans');
                $table->unsignedSmallInteger('serial');
                $table->unsignedBigInteger('order_detail_id')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('description', 500);
                $table->string('unit', 50)->nullable();
                $table->decimal('quantity', 15, 3);
                $table->decimal('unit_value', 15, 4)->nullable();
                $table->decimal('line_value', 15, 2);
                // Null when supplementary duty does not apply to the line. The form column is left blank, not printed as zero.
                $table->decimal('sd_amount', 15, 2)->nullable();
                $table->decimal('vat_rate', 5, 2);
                $table->decimal('vat_amount', 15, 2);
                $table->decimal('line_total', 15, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vat_challan_events')) {
            Schema::create('vat_challan_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vat_challan_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('event', 40);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('ip', 45)->nullable();
                $table->longText('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('vat_document_sequences')) {
            Schema::create('vat_document_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('series', 40);
                $table->unsignedSmallInteger('year');
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();
                $table->unique(['series', 'year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_challan_lines');
        Schema::dropIfExists('vat_challan_events');
        Schema::dropIfExists('vat_document_sequences');
        Schema::dropIfExists('vat_challans');
    }
};
