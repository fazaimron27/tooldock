<?php

/**
 * Check Goal Status Command
 *
 * Checks savings goals for approaching deadlines, overdue status, and
 * stagnation (no contributions in a configurable period). Dispatches
 * notifications via the signal handler registry.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Console\Commands;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Console\Command;
use Modules\Core\Models\User;

/**
 * Check Goal Status Command
 *
 * Checks for deadlines, overdue goals, and stagnation.
 * Should be scheduled to run weekly.
 */
class CheckGoalStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:check-goal-status
                            {--user= : Check for specific user ID only}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for goal deadlines, overdue status, and stagnation';

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

        $this->info('Checking goal statuses...'.($dryRun ? ' (DRY RUN)' : ''));

        if ($userId) {
            $users = collect([User::find($userId)])->filter();
        } else {
            $users = User::whereHas('goals', function ($query) {
                $query->where('is_completed', false);
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
            }
        }

        $this->info("Completed: {$totalSignals} signals ".($dryRun ? 'would be sent.' : 'sent.'));

        return Command::SUCCESS;
    }
}
