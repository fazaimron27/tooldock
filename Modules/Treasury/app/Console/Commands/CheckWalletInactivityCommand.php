<?php

namespace Modules\Treasury\Console\Commands;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Console\Command;
use Modules\Core\Models\User;

/**
 * Check Wallet Inactivity Command
 *
 * Sends notifications to users who have wallets with no activity for a configurable period.
 * Should be scheduled to run weekly.
 */
class CheckWalletInactivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:check-wallet-inactivity
                            {--user= : Check for specific user ID only}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for inactive wallets and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(SignalHandlerRegistry $registry): int
    {
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        $this->info('Checking wallets for inactivity...'.($dryRun ? ' (DRY RUN)' : ''));

        if ($userId) {
            $users = collect([User::find($userId)])->filter();
        } else {
            // Get all users who have at least one active wallet
            $users = User::whereHas('wallets', function ($query) {
                $query->where('is_active', true);
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
