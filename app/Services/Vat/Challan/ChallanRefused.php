<?php

namespace App\Services\Vat\Challan;

/**
 * Thrown when a challan cannot be issued. Carries every reason, so the caller can show them all at once.
 */
class ChallanRefused extends \DomainException
{
    /** @param  list<string>  $blockers */
    public function __construct(public readonly array $blockers)
    {
        parent::__construct(implode(' ', $blockers));
    }
}
