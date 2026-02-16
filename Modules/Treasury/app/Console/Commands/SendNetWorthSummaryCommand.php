<?php

/**
 * Send Net Worth Summary Command
 *
 * Generates and sends monthly net worth summaries including total assets
 * across all wallets, period-over-period changes, and wallet-level
 * breakdowns. Dispatches through the signal handler registry.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Console\Commands;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Console\Command;
use Modules\Core\Models\User;

/**
 * Send Net Worth Summary Command
 *
 * Sends monthly net worth summary notifications to all users with wallets.
 * Should be scheduled to run at the end of each month.
 */
class SendNetWorthSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:send-networth-summary
                            {--user= : Send for specific user ID only}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send monthly net worth summary notifications to users';

    /**
     * Execute the console command.
     *
     * @param  SignalHandlerRegistry  $registry  The signal handler registry
     * @return int
     */
    public function handle(SignalHandlerRegistry $registry): int
    {
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        $this->info('Sending net worth summaries...'.($dryRun ? ' (DRY RUN)' : ''));

        if ($userId) {
            $users = collect([User::find($userId)])->filter();
        } else {
            $users = User::whereHas('wallets', function ($query) {
                $query->where('is_active', true);
            })->get();
        }

        $totalSignals = 0;

        foreach ($users as $user) {
            $results = $registry->dispatch('scheduled.monthly', $user, $dryRun);

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
