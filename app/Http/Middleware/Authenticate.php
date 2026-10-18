<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    
    
    /**
     * Guests never learn the admin login address from a redirect: the admin area
     * sends them to the home page, and the customer login serves everything else.
     * Only the admin login page itself shows the configured address.
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return $request->is('admin', 'admin/*') ? route('home') : route('customer.login');
        }
    }
}
