<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;

class AppSessionHandler
{
    /**
     * Handle an incoming request.
     *
     * 🔒 License enforcement is ALWAYS ON (hardcoded via config/updater.php
     * 'enforce' => true) and cannot be disabled through .env. It only skips
     * localhost/127.0.0.1 and the softmit.xyz master domain.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $enforce = config('updater.enforce', false);

        if ($enforce
            && $request->is('admin/*')
            && auth('admin')->check()
            && ! $request->is('admin/login*', 'admin/license-info', 'admin/updates*')
        ) {
            $verify = LicenseService::verify();

            if (! $verify['valid']) {
                return redirect()
                    ->route('admin.license.info')
                    ->with('license_error', $verify['message']);
            }
        }

        return $next($request);
    }
}
