<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Name-only lookup tables behind the Finance setup screens: account categories
 * and groups, expense categories and heads, balance adjustment heads and areas.
 *
 * None of these rows moves money. Expenses still store their category as text,
 * so deleting a master never changes an existing expense.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_account_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('finance_account_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->foreignId('account_category_id')->constrained('finance_account_categories')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('expense_heads', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150)->unique();
            $table->timestamps();
        });

        Schema::create('balance_adjustment_heads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('finance_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        // The four account categories the reference chart uses.
        $now = now();
        DB::table('finance_account_categories')->insert(array_map(
            fn (string $name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now],
            ['Income', 'Expenses', 'Liabilities', 'Assets'],
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_groups');
        Schema::dropIfExists('finance_account_categories');
        Schema::dropIfExists('expense_heads');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('balance_adjustment_heads');
        Schema::dropIfExists('finance_areas');
    }
};
