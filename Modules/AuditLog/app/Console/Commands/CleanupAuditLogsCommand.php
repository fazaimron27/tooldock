<?php

namespace Modules\AuditLog\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\AuditLog\App\Models\AuditLog;

class CleanupAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auditlog:cleanup
                            {--days= : Number of days to keep audit logs (defaults to setting value)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old audit logs older than specified days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) settings('retention_days', 90);

        $dryRun = $this->option('dry-run');

        if ($days < 1) {
            $this->error('Days must be a positive integer.');

            return Command::FAILURE;
        }

        $cutoffDate = now()->subDays($days);

        $query = AuditLog::where('created_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count === 0) {
            $this->info("No audit logs found older than {$days} days.");

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Would delete {$count} audit log(s) older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')}).");

            return Command::SUCCESS;
        }

        if (! $this->confirm("Are you sure you want to delete {$count} audit log(s) older than {$days} days?")) {
            $this->info('Cleanup cancelled.');

            return Command::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Successfully deleted {$deleted} audit log(s).");

        return Command::SUCCESS;
    }
}
