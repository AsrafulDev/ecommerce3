<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * customers.district is currently an INTEGER column, but the app stores a
     * district NAME (e.g. "Dhaka") into it. Convert it to a string so the
     * district name saves correctly (and matches how the profile form submits).
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'district')) {
                $type = Schema::getColumnType('customers', 'district');
                if (in_array($type, ['integer', 'int', 'bigint', 'smallint'], true)) {
                    $table->string('district', 255)->nullable()->change();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'district')) {
                $type = Schema::getColumnType('customers', 'district');
                if ($type === 'string') {
                    $table->integer('district')->nullable()->change();
                }
            }
        });
    }
};
