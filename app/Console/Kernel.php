<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\CronJobSetting;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Courier status sync — frequency controlled from admin panel
        try {
            $setting = CronJobSetting::forKey('courier_status_sync');
        } catch (\Throwable $e) {
            // Table might not exist yet (fresh install), fall back to default
            $setting = null;
        }

        $enabled   = $setting ? $setting->is_enabled        : true;
        $frequency = $setting ? (int) $setting->frequency_minutes : 10;
        $limit     = $setting ? (int) $setting->order_limit       : 50;

        if ($enabled) {
            $job = $schedule->command("courier:check-status --limit={$limit}")
                ->withoutOverlapping()
                ->runInBackground();

            match (true) {
                $frequency <= 1  => $job->everyMinute(),
                $frequency <= 2  => $job->everyTwoMinutes(),
                $frequency <= 5  => $job->everyFiveMinutes(),
                $frequency <= 10 => $job->everyTenMinutes(),
                $frequency <= 15 => $job->everyFifteenMinutes(),
                $frequency <= 30 => $job->everyThirtyMinutes(),
                $frequency <= 60 => $job->hourly(),
                default          => $job->everyTwoHours(),
            };
        }

        // ── Warranty ──────────────────────────
        $schedule->command('warranty:expire')->hourly();
        $schedule->command('warranty:update-tiers')->dailyAt('03:00');

        // ── Stock reconciliation ──────────────────────────
        // Nightly dry-run to detect stock drift between products.stock and stock_batches.remaining_qty.
        // If mismatches are found, alert via logs (can be extended to email/Slack if monitoring is set up).
        $schedule->command('stock:sync-from-batches --dry-run')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::channel('stock')->info('Nightly stock reconcile: no mismatches detected.');
            })
            ->onFailure(function () {
                \Log::channel('stock')->warning('Nightly stock reconcile found mismatches or errored — check manually.');
            });

        // ── Log retention ──────────────────────────
        // Monthly archival of activity logs older than 90 days to prevent unbounded table growth.
        $schedule->command('logs:archive-activity --days=90')
            ->monthlyOn(1, '04:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Monthly activity log archival completed.');
            })
            ->onFailure(function () {
                \Log::warning('Monthly activity log archival failed.');
            });
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
