<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InstallerController;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks /install/* once the app is already set up (general_settings + an
 * admin user exist). Keeps the wizard reachable only for a fresh/empty DB.
 */
class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (InstallerController::isInstalled()) {
            return redirect()->route('login')->with('error', 'Application is already installed.');
        }

        return $next($request);
    }
}
