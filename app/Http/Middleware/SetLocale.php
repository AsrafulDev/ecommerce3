<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use App\Models\GeneralSetting;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = ['en', 'bn'];

        $setting = Cache::remember('general_setting', 1800, function () {
            return GeneralSetting::where('status', 1)->first();
        });

        // 🛡️ Admin panel: always use admin_language, ignore session
        if ($request->is('admin*')) {
            $locale = $setting->admin_language ?? config('app.locale');
        }
        // 🏠 Frontend: session > default_language > config fallback
        elseif (session()->has('locale')) {
            $locale = session('locale');
        } else {
            $locale = $setting->default_language ?? config('app.locale');
        }

        // Validate locale exists
        if (!in_array($locale, $supported)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
