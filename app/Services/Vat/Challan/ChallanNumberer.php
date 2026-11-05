<?php

namespace App\Services\Vat\Challan;

use Illuminate\Support\Facades\DB;

/**
 * Hands out the next challan number. The sequence row is locked for the rest of the caller's transaction, so two
 * challans issued at the same moment cannot take the same number, and a number is never reused. A rolled-back issue
 * gives its number back, so the sequence has no gaps from failed attempts.
 */
class ChallanNumberer
{
    /**
     * Makes sure the sequence row exists. Call it before the issuing transaction, not inside it: two transactions that
     * each insert the row and then lock it can deadlock on InnoDB. Here each insert is its own statement and commits at once.
     */
    public function ensure(string $series, int $year): void
    {
        DB::table('vat_document_sequences')->insertOrIgnore([
            'series' => $series,
            'year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Must be called inside a transaction, after ensure(): the lock lasts until that transaction ends. */
    public function next(string $series, int $year): int
    {
        $row = DB::table('vat_document_sequences')
            ->where('series', $series)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($row === null) {
            throw new \RuntimeException("No number sequence for {$series} {$year}. Call ensure() first.");
        }

        $next = (int) $row->last_number + 1;

        DB::table('vat_document_sequences')
            ->where('id', $row->id)
            ->update(['last_number' => $next, 'updated_at' => now()]);

        return $next;
    }

    /** The printed number. The format is ours, not the NBR's: confirm it with the practitioner before filing. */
    public static function format(int $year, int $number): string
    {
        return sprintf('CH-%d-%06d', $year, $number);
    }
}
