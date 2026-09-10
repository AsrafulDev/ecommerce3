<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InstallerController;
use Closure;
use Illuminate\Http\Request;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
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
