<?php

namespace App\Services\Landing;

use RuntimeException;

/** A landing page's content broke a rule. The messages are safe to show to the editor. */
class InvalidLandingContent extends RuntimeException
{
    /** @param array<int, string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }
}
