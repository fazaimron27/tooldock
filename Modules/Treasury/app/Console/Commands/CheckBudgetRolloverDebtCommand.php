<?php

/**
 * Check Budget Rollover Debt Command
 *
 * Checks all active budgets with rollover enabled for accumulated debt
 * and dispatches notifications via the signal handler registry.
 * Supports filtering by user and dry-run mode for testing.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Console\Commands;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Console\Command;
use Modules\Core\Models\User;

/**
 * Check Budget Rollover Debt Command
 *
 * Sends notifications to users who have budgets with negative rollover (debt)
 * from the previous month. Should be scheduled to run at the start of each month.
 */
class CheckBudgetRolloverDebtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:check-rollover-debt
                            {--user= : Check for specific user ID only}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for budgets with rollover debt and send notifications';

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

        $this->info('Checking budgets for rollover debt...'.($dryRun ? ' (DRY RUN)' : ''));

        if ($userId) {
            $users = collect([User::find($userId)])->filter();
        } else {
            $users = User::whereHas('budgets', function ($query) {
                $query->where('is_active', true)
                    ->where('rollover_enabled', true);
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
            }
        }

        $this->info("Completed: {$totalSignals} signals ".($dryRun ? 'would be sent.' : 'sent.'));

        return Command::SUCCESS;
    }
}
