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
     * Installed = the setup marker or any user already exists. Treated as
     * "not installed" only when the database is unreachable or has neither
     * marker nor user data.
     */
    public static function isInstalled(): bool
    {
        try {
            $hasSettings = Schema::hasTable('general_settings')
                && DB::table('general_settings')->exists();
            $hasUsers = Schema::hasTable('users')
                && DB::table('users')->exists();

            return $hasSettings || $hasUsers;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index()
    {
        return view('installer.index', [
            // True when the DB already has leftover tables (partial/old install) —
            // the wizard will clean & re-migrate automatically.
            'hasExistingTables' => $this->databaseHasTables(),

            // ✨ Demo pre-fill values — used to pre-populate the form on a fresh
            // /install (auto-redirect / empty DB) so a one-click demo setup works.
            'demo' => [
                'site_name'      => 'My Store',
                'admin_name'     => 'Admin',
                'admin_email'    => 'admin@demo.com',
                'admin_password' => '123456',
            ],
        ]);
    }

    public function store(Request $request)
    {
        // Never allow a second install or a direct POST to bypass the route
        // middleware once setup data/user data already exists.
        if (self::isInstalled()) {
            return redirect()->route('login')->with('error', 'Application is already installed.');
        }

        $request->validate([
            'site_name'            => ['required', 'string', 'max:55'],
            'admin_name'           => ['required', 'string', 'max:255'],
            'admin_email'          => ['required', 'email', 'max:255'],
            'admin_password'       => ['required', 'string', 'min:6', 'confirmed'],
            'clean_database'       => ['nullable', 'boolean'],
            'seed_demo'            => ['nullable', 'boolean'],
        ]);

        try {
            // 🔄 AUTO CLEAN — if the DB already has leftover tables (partial/old
            // install) the wizard drops everything and re-runs migrations so the
            // setup is always clean. The "clean_database" checkbox forces the same.
            $needsClean = $request->boolean('clean_database') || $this->databaseHasTables();

            if ($needsClean) {
                Artisan::call('migrate:fresh', ['--force' => true]);
            } else {
                Artisan::call('migrate', ['--force' => true]);
            }

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

    /**
     * True when the configured database already contains any app tables
     * (partial/old install). Used to auto-clean before re-migrating.
     */
    private function databaseHasTables(): bool
    {
        try {
            foreach (['migrations', 'general_settings', 'users', 'products'] as $table) {
                if (Schema::hasTable($table)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // DB unreachable / not configured — nothing to clean
        }
        return false;
    }
}
