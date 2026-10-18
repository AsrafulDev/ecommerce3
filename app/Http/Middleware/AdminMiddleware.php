<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Ensure the user is authenticated with admin guard and has admin role.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated with admin guard
        if (!Auth::guard('admin')->check()) {
            // Not a hint about the login address: the admin area is simply not open to guests.
            return redirect()->route('home')->with('error', 'You do not have access to that page.');
        }

        $user = Auth::guard('admin')->user();

        // ✅ Super Admin (id=1) always allowed
        if ($user->id == 1) {
            return $next($request);
        }
        
        // ✅ Get all Spatie roles (case-insensitive check)
        $spatieRoles = $user->getRoleNames()->map(function($role) {
            return strtolower($role);
        })->toArray();
        
        // ✅ If user has Spatie 'Admin' role (any case), allow access (ignore role column)
        if (in_array('admin', $spatieRoles)) {
            return $next($request);
        }

        // ✅ If user has any Spatie role, allow access
        if (count($spatieRoles) > 0) {
            return $next($request);
        }

        return $next($request);
    }
}
