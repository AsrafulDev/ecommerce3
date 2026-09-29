<?php

namespace App\Http\Middleware;

use App\Services\Accounting\AdvancedAccountingGateway;
use Closure;
use Illuminate\Http\Request;

/**
 * Gates the Advanced Accounting screens (routes named admin.accounting.*).
 *
 * The module is optional: when the package is missing, or installed but
 * switched off, these screens have no meaning — and answering 404 keeps the
 * guarantee that Lite-only installs never execute double-entry code paths.
 * Permission checks stay on top; this gate sits underneath them.
 */
class EnsureAdvancedAccountingEnabled
{
    public function __construct(protected AdvancedAccountingGateway $accounting)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        abort_unless($this->accounting->enabled(), 404, 'Advanced Accounting is not enabled.');

        return $next($request);
    }
}
