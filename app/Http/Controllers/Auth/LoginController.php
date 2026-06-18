<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        // ✅ Super Admin (id=1) always allowed - fast check first
        if ($user->id == 1) {
            return redirect()->intended($this->redirectPath());
        }
        
        if ($user->hasRole('Admin') || $user->hasRole('admin')) {
            return redirect()->intended($this->redirectPath());
        }
        
        // ✅ Get Spatie roles once (cached)
        $spatieRoles = $user->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
        
        // ✅ If user has any Spatie role, allow access
        if (count($spatieRoles) > 0) {
            return redirect()->intended($this->redirectPath());
        }

        // ✅ Allowed - proceed to admin dashboard
        return redirect()->intended($this->redirectPath());
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Allow access to login page if not authenticated
        $this->middleware(function ($request, $next) {
            if (Auth::guard('admin')->check()) {
                $user = Auth::guard('admin')->user();
                
                // ✅ Super Admin (id=1) or Admin role - redirect to dashboard
                if ($user->id == 1 || $user->hasRole('Admin') || $user->hasRole('admin')) {
                    return redirect()->route('admin.dashboard');
                }
                
                // ✅ If user has any Spatie role, redirect to dashboard
                $spatieRoles = $user->getRoleNames()->map(function($role) {
                    return strtolower($role);
                })->toArray();
                
                if (count($spatieRoles) > 0) {
                    return redirect()->route('admin.dashboard');
                }
            }
            return $next($request);
        })->except('logout');
    }
    
    /**
     * Attempt to log the user into the application.
     */
    protected function attemptLogin(\Illuminate\Http\Request $request)
    {
        // Call parent attemptLogin method
        $attempt = $this->guard()->attempt(
            $this->credentials($request), 
            $request->filled('remember')
        );
        
        // Log attempt result for debugging
        if (!$attempt) {
            \Log::warning('Login failed', [
                'email' => $request->input('email'),
                'guard' => 'admin'
            ]);
        }
        
        return $attempt;
    }
    
    /**
     * Handle a failed login attempt.
     */
    protected function sendFailedLoginResponse(\Illuminate\Http\Request $request)
    {
        \Log::warning('Login failed - sendFailedLoginResponse', [
            'email' => $request->input('email'),
            'errors' => ['email' => trans('auth.failed')]
        ]);
        
        return redirect()->back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => trans('auth.failed')]);
    }

    /**
     * Get the guard to be used during authentication.
     */
    protected function guard()
    {
        return Auth::guard('admin');
    }

    /**
     * Get the login credentials from the request.
     */
    protected function credentials(\Illuminate\Http\Request $request)
    {
        $login = $request->input('email');
        $password = $request->input('password');

        // Admin login page - only support email (not phone)
        // Phone login is handled in customer login page
        return ['email' => $login, 'password' => $password];
    }
    
    /**
     * Validate the user login request.
     */
    protected function validateLogin(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // Check which login view exists
        if (view()->exists('backEnd.auth.login')) {
            return view('backEnd.auth.login');
        }
        
        if (view()->exists('admin.login')) {
            return view('admin.login');
        }
        
        // Default fallback
        return view('backEnd.auth.login'); 
    }
    
    /**
     * Alias for showLoginForm (for compatibility)
     */
    public function loginForm()
    {
        return $this->showLoginForm();
    }
}