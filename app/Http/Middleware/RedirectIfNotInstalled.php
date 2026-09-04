<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InstallerController;
use Closure;
use Illuminate\Http\Request;

/**
 * 🔀 AUTO-INSTALL REDIRECT — while the application is not set up yet
 * (empty DB, or no general_settings row / no users yet) every browser
 * request is redirected to the install wizard so the visitor lands on
 * /install instead of hitting runtime errors from missing tables/data.
 *
 * Runs early in the web stack (prepended before the web middleware group)
 * so it fires even for routes that would otherwise need DB data
 * (auth, dashboard, storefront, model-bound routes, ...).
 */
class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        // Never intercept the wizard itself, Laravel's health check, the
        // API group, or JSON/AJAX calls (those should fail loudly instead
        // of being bounced with a 302).
        if ($request->routeIs('install.*')
            || $request->is('up', 'api/*')
            || $request->expectsJson()) {
            return $next($request);
        }

        if (! InstallerController::isInstalled()) {
            return redirect()->route('install.index');
        }

        return $next($request);
    }
}
