<?php

namespace App\Support\Accounting;

use Softmit\DoubleEntry\Services\JournalPoster;

/**
 * The single place that decides whether Advanced Accounting (double-entry) is
 * live. Two independent questions, deliberately kept separate:
 *
 *   available() — is the softmit/bd-double-entry package actually installed?
 *   enabled()   — available() AND the operator switched it on?
 *
 * Advanced Accounting is active only when BOTH are true. No other code may
 * scatter class_exists() or config('double-entry.enabled') checks; everything
 * downstream asks the AdvancedAccountingGateway, which delegates here.
 *
 * The class_exists() check runs BEFORE reading any package config so a
 * disabled/absent package short-circuits without touching package-referencing
 * state of any kind. (config/double-entry.php is package-free by rule now, but
 * the ordering stays as defence in depth.)
 */
final class AccountingAvailability
{
    private static ?bool $available = null;

    /**
     * True when the double-entry package is installed (its engine class can
     * actually load). Never throws when the package is missing. Memoised: the
     * answer cannot change within a process.
     */
    public static function available(): bool
    {
        return self::$available ??= class_exists(JournalPoster::class);
    }

    /**
     * True only when the operator switched Advanced Accounting on AND the
     * package is actually installed. Not memoised — the flag is configuration
     * and screens/tests may change it mid-request.
     */
    public static function enabled(): bool
    {
        return self::available() && (bool) config('double-entry.enabled');
    }
}
