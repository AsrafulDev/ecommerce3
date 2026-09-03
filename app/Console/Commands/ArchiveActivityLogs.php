<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveActivityLogs extends Command
{
    protected $signature = 'logs:archive-activity {--days=90 : Keep logs newer than this many days} {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Archive old activity logs (delete after retention period)';

    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $cutoffDate = now()->subDays($days)->toDateTimeString();

        $count = ActivityLog::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('No activity logs to archive.');
            return 0;
        }

        $this->warn("Found {$count} activity log entries older than {$days} days (before {$cutoffDate}).");

        if ($dryRun) {
            $this->info('Dry run mode — no records deleted.');
            return 0;
        }

        $this->line('Deleting old activity logs...');

        try {
            ActivityLog::where('created_at', '<', $cutoffDate)->delete();
            $this->info("✓ Archived {$count} activity log entries.");
        } catch (\Throwable $e) {
            $this->error('Archive failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
