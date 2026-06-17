<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;

class CheckResellerPublicEnabled
{
    /**
     * Block reseller public landing pages when reseller system is disabled.
     */
    public function handle(Request $request, Closure $next)
    {
        $generalSetting = GeneralSetting::orderBy('id', 'desc')->first();
        if (!$generalSetting || ($generalSetting->reseller_enabled ?? 1) != 1) {
            abort(404);
        }

        return $next($request);
    }
}
