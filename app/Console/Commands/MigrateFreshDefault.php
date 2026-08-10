<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Shortcut for `php artisan migrate:fresh --seed --seeder=DefaultDatabaseSeeder`.
 * Wipes the database and seeds ONLY the default data (admin + settings),
 * WITHOUT demo products / categories / brands / warranty demo data.
 *
 * Usage: php artisan migrate:fresh:default
 */
class MigrateFreshDefault extends Command
{
    protected $signature = 'migrate:fresh:default';

    protected $description = 'Fresh migrate + seed only default data (admin + settings, no demo products/categories/brands)';

    public function handle(): int
    {
        if (!app()->environment('production') || $this->confirm('This will wipe the database. Continue?')) {
            $this->call('migrate:fresh', ['--force' => true]);
            $this->call('db:seed', [
                '--class' => \Database\Seeders\DefaultDatabaseSeeder::class,
                '--force' => true,
            ]);
            $this->info('Done. Default data seeded (admin + settings only). No demo products/categories/brands were created.');
            return self::SUCCESS;
        }

        $this->warn('Migration aborted.');
        return self::FAILURE;
    }
}
