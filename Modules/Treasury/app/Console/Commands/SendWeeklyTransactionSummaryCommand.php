<?php

namespace Modules\Treasury\Console\Commands;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Console\Command;
use Modules\Core\Models\User;

/**
 * Send Weekly Transaction Summary Command
 *
 * Sends weekly transaction summary to all users.
 * Should be scheduled to run at end of week (e.g., Sunday evening).
 */
class SendWeeklyTransactionSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:send-weekly-summary
                            {--user= : Send for specific user ID only}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly transaction summary notifications to users';

    /**
     * Execute the console command.
     */
    public function handle(SignalHandlerRegistry $registry): int
    {
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        $this->info('Sending weekly summaries...'.($dryRun ? ' (DRY RUN)' : ''));

        if ($userId) {
            $users = collect([User::find($userId)])->filter();
        } else {
            // Get users who have transactions in the past week
            $weekStart = now()->subWeek()->startOfWeek()->format('Y-m-d');
            $users = User::whereHas('transactions', function ($query) use ($weekStart) {
                $query->where('date', '>=', $weekStart);
            })->get();
        }

        $totalSignals = 0;

        foreach ($users as $user) {
            $results = $registry->dispatch('scheduled.weekly', $user, $dryRun);

            if (! empty($results)) {
                foreach ($results as $handler => $signal) {
                    $totalSignals++;
                    $this->line("  User {$user->name}: {$signal['title']}");
                }
            } else {
                $this->line("  User {$user->name}: No signals");
            }
        }

        $this->info("Completed: {$totalSignals} signals ".($dryRun ? 'would be sent.' : 'sent.'));

        return Command::SUCCESS;
    }
}
