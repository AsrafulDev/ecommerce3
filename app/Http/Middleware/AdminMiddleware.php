<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Ensure the user is authenticated with admin guard and has admin role (not vendor).
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated with admin guard
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('login')->with('error', 'Please login to access admin panel.');
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

        // ✅ Blocklist: শুধু vendor এবং reseller (customer allow)
        $blockedSpatieRoles = ['vendor', 'reseller'];
        
        // Check if user has reseller Spatie role
        if (in_array('reseller', $spatieRoles)) {
            return redirect()->route('reseller.dashboard')->with('error', 'You do not have permission to access admin panel.');
        }

        // Check if user has vendor Spatie role
        if (in_array('vendor', $spatieRoles)) {
            return redirect()->route('vendor.dashboard')->with('error', 'You do not have permission to access admin panel.');
        }
        
        // ✅ If user has any Spatie role (not blocked), allow access
        // This allows Staff, Salesman, Super Viser, customer, etc.
        if (count($spatieRoles) > 0) {
            return $next($request);
        }
        
        // ✅ If no Spatie role, check role column - শুধু vendor এবং reseller block
        $roleColumn = isset($user->role) ? strtolower($user->role) : null;
        $blockedRoleColumns = ['vendor', 'reseller'];
        
        if ($roleColumn && in_array($roleColumn, $blockedRoleColumns)) {
            Auth::guard('admin')->logout();
            return redirect()->route('login')->with('error', 'You do not have permission to access admin panel.');
        }

        return $next($request);
    }
}
