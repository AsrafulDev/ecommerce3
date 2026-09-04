<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15,2);
            $table->text('note')->nullable();
            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->date('expense_date')->nullable();
            });
        }

Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'title')) {
                $table->string('title', 255)->nullable()->after('id');
            }
            if (!Schema::hasColumn('expenses', 'category')) {
                $table->string('category', 100)->nullable()->after('title');
            }
            if (!Schema::hasColumn('expenses', 'fund_transaction_id')) {
                $table->bigInteger('fund_transaction_id')->unsigned()->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
