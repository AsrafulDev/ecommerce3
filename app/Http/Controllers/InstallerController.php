<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * First-run install wizard. Only reachable while the DB is empty (no
 * general_settings row + no admin user) — see RedirectIfInstalled middleware.
 *
 * Runs the migrations + DefaultDatabaseSeeder (optionally + DemoDataSeeder),
 * then applies the submitted site name / admin credentials on top and logs
 * the new admin straight into the dashboard.
 */
class InstallerController extends Controller
{
    /**
     * Installed = base setup data already exists. Treated as "not installed"
     * if the DB isn't reachable / tables aren't migrated yet.
     */
    public static function isInstalled(): bool
    {
        try {
            return Schema::hasTable('general_settings')
                && Schema::hasTable('users')
                && DB::table('general_settings')->count() > 0
                && DB::table('users')->count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index()
    {
        return view('installer.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_name'            => ['required', 'string', 'max:55'],
            'admin_name'           => ['required', 'string', 'max:255'],
            'admin_email'          => ['required', 'email', 'max:255'],
            'admin_password'       => ['required', 'string', 'min:6', 'confirmed'],
            'seed_demo'            => ['nullable', 'boolean'],
        ]);

        try {
            Artisan::call('migrate', ['--force' => true]);

            Artisan::call('db:seed', [
                '--class' => DefaultDatabaseSeeder::class,
                '--force' => true,
            ]);

            if ($request->boolean('seed_demo')) {
                Artisan::call('db:seed', [
                    '--class' => DemoDataSeeder::class,
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Installation failed: ' . $e->getMessage());
        }

        // Apply the submitted admin credentials over the seeded default admin.
        $admin = User::orderBy('id')->first();
        if ($admin) {
            $admin->update([
                'name'     => $request->admin_name,
                'email'    => $request->admin_email,
                'password' => Hash::make($request->admin_password),
            ]);
        }

        // Apply the submitted site name over the seeded default settings.
        GeneralSetting::query()->update(['name' => $request->site_name]);

        Cache::forget('general_setting');
        Cache::forget('active_theme');

        if ($admin) {
            Auth::guard('admin')->login($admin);
        }

        return redirect()->route('admin.dashboard')->with('success', 'Installation complete. Welcome!');
    }
}
