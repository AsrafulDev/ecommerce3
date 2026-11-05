<?php

namespace App\Services\Vat\Challan;

/**
 * The checks the challan applies before it prints anything. Each is a rule the challan relies on, and each is listed
 * with its source in docs/vat-mushak-63.md, so a practitioner can see where it comes from.
 */
final class ChallanRules
{
    /**
     * A Bangladesh BIN is 12 digits. Checked here only for shape, from the repo's own VAT plan (bd-vat-mushak/PLAN.md,
     * section 2: "validated as a 12-digit BIN"). Whether the check digit must also be verified is an open question.
     */
    public static function validBin(?string $bin): bool
    {
        return $bin !== null && preg_match('/^\d{12}$/', trim($bin)) === 1;
    }

    /** Document series used for the number sequence. */
    public const SERIES = 'mushak-6.3';
}
