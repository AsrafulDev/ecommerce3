<?php

namespace App\Console\Commands;

use App\Models\GeneralSetting;
use App\Support\AdminLoginPath;
use Illuminate\Console\Command;

/**
 * Show or restore the admin login address. Recovery for a forgotten address:
 * needs shell access to the server, so it is not reachable from the web.
 */
class AdminLoginPathCommand extends Command
{
    protected $signature = 'admin:login-path {--reset : Restore the default login address (/login)}';

    protected $description = 'Show the admin login address, or restore the default with --reset';

    public function handle(): int
    {
        $setting = GeneralSetting::query()->orderByDesc('id')->first();
        if (! $setting) {
            $this->error('No general settings row exists yet.');
            return self::FAILURE;
        }

        if ($this->option('reset')) {
            $setting->forceFill(['admin_login_path' => null])->save();
            AdminLoginPath::forget();
            $this->info('Admin login address restored to /'.AdminLoginPath::DEFAULT.'.');
            return self::SUCCESS;
        }

        $this->line('Admin login address: /'.AdminLoginPath::current());
        return self::SUCCESS;
    }
}
