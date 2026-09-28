<?php

namespace App\Support\Accounting;

/**
 * The single place that decides whether LEVEL 2 (double-entry) is live.
 *
 * This is deliberately the ONLY code allowed to ask "is the package here?", and
 * it does so BEFORE reading any package-referencing config. config/double-entry.php
 * resolves Softmit\DoubleEntry\Support\AccountRole constants while it is being
 * loaded, so touching that config with the package absent is a fatal — the
 * class_exists() check short-circuits ahead of config() precisely to avoid it.
 *
 * Everything downstream depends on the FullAccountingGateway interface, not on
 * this class directly, so a disabled install behaves byte-for-byte like one that
 * never had the package.
 */
final class AccountingAvailability
{
    /**
     * True only when the operator switched Full accounting on AND the package is
     * actually installed. Never throws when the package is missing.
     */
    public static function enabled(): bool
    {
        if (!class_exists(\Softmit\DoubleEntry\Services\JournalPoster::class)) {
            return false;
        }

        return (bool) config('double-entry.enabled');
    }
}
