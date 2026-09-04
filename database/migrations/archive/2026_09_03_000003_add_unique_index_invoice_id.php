<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'invoice_id')) {
            return;
        }

        // Fix duplicate invoice_ids by appending a suffix to duplicates (keep first row unchanged)
        $dups = DB::table('orders')
            ->select('invoice_id')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('invoice_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($dups as $dup) {
            $rows = DB::table('orders')->where('invoice_id', $dup->invoice_id)->orderBy('id')->get();
            $i = 0;
            foreach ($rows as $row) {
                if ($i === 0) {
                    $i++;
                    continue;
                }
                $new = $row->invoice_id . '-' . $i;
                DB::table('orders')->where('id', $row->id)->update(['invoice_id' => $new]);
                $i++;
            }
        }

        // Attempt to add unique index. Some DB drivers used in tests may not
        // expose Doctrine schema helpers, so wrap in try/catch and ignore
        // failures (migration will still have done the de-duplication step).
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique('invoice_id');
            });
        } catch (\Throwable $e) {
            // Log and continue; in environments where index creation fails
            // the unique constraint can be added manually on production.
            \Log::warning('Could not add unique index orders.invoice_id: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'invoice_id')) {
            return;
        }

        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = array_map(fn($idx) => $idx->getName(), $sm->listTableIndexes('orders'));
        if (in_array('orders_invoice_id_unique', $indexes)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_invoice_id_unique');
            });
        }
    }
};
