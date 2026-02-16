<?php

namespace Modules\Treasury\Console\Commands;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Console\Command;
use Modules\Core\Models\User;

/**
 * Send Daily Transaction Summary Command
 *
 * Sends end-of-day transaction summary to all users with transactions today.
 * Should be scheduled to run at end of day (e.g., 8 PM).
 */
class SendDailyTransactionSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:send-daily-summary
                            {--user= : Send for specific user ID only}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily transaction summary notifications to users';

    /**
     * Execute the console command.
     */
    public function handle(SignalHandlerRegistry $registry): int
    {
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');
        $date = now()->format('Y-m-d');

        $this->info("Sending daily summaries for {$date}...".($dryRun ? ' (DRY RUN)' : ''));

        if ($userId) {
            $users = collect([User::find($userId)])->filter();
        } else {
            // Get users who have transactions today
            $users = User::whereHas('transactions', function ($query) use ($date) {
                $query->whereDate('date', $date);
            })->get();
        }

        $totalSignals = 0;

        foreach ($users as $user) {
            $results = $registry->dispatch('scheduled.daily', $user, $dryRun);

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
