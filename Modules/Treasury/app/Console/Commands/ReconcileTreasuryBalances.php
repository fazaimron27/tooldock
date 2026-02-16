<?php

/**
 * Reconcile Treasury Balances Command
 *
 * Validates that wallet balances and goal progress match their computed
 * values from transaction history. Uses efficient aggregate queries
 * (2 queries instead of 4N) and optionally fixes discrepancies.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Console\Commands;

use App\Services\Cache\CacheService;
use Illuminate\Console\Command;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;

/**
 * Class ReconcileTreasuryBalances
 *
 * Reconciles wallet balances and goal progress from transaction history.
 */
class ReconcileTreasuryBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:reconcile {--fix : Whether to fix the discrepancies found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile wallet balances and goal progress from transaction history';

    /**
     * Execute the console command.
     *
     * @param  CacheService  $cacheService  The cache service for flushing treasury cache
     * @return void
     */
    public function handle(CacheService $cacheService): void
    {
        $this->info('Starting Treasury Reconciliation...');
        $fix = $this->option('fix');

        $this->reconcileWallets($fix);
        $this->reconcileGoals($fix);

        $cacheService->flush('treasury', 'ReconcileTreasuryBalances');

        $this->info('Reconciliation complete.');
    }

    /**
     * Reconcile all wallet balances.
     *
     * Uses aggregate queries to calculate balances efficiently.
     * Reduces 4N queries to just 2 aggregate queries.
     *
     * @param  bool  $fix  Whether to fix discrepancies found
     * @return void
     */
    private function reconcileWallets(bool $fix): void
    {
        $this->comment('Checking Wallets...');
        $wallets = Wallet::all()->keyBy('id');
        $discrepancies = 0;

        $outgoingBalances = Transaction::selectRaw('
            wallet_id,
            SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense,
            SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as transfer_out
        ', ['income', 'expense', 'transfer'])
            ->groupBy('wallet_id')
            ->get()
            ->keyBy('wallet_id');

        $incomingTransfers = Transaction::selectRaw('
            destination_wallet_id as wallet_id,
            SUM(amount) as transfer_in
        ')
            ->where('type', 'transfer')
            ->whereNotNull('destination_wallet_id')
            ->groupBy('destination_wallet_id')
            ->get()
            ->keyBy('wallet_id');

        foreach ($wallets as $wallet) {
            $outgoing = $outgoingBalances->get($wallet->id);
            $incoming = $incomingTransfers->get($wallet->id);

            $income = (float) ($outgoing->income ?? 0);
            $expense = (float) ($outgoing->expense ?? 0);
            $transferOut = (float) ($outgoing->transfer_out ?? 0);
            $transferIn = (float) ($incoming->transfer_in ?? 0);

            $calculatedBalance = $income - $expense - $transferOut + $transferIn;
            $currentBalance = (float) $wallet->balance;

            if (abs($calculatedBalance - $currentBalance) > 0.01) {
                $discrepancies++;
                $this->warn(sprintf(
                    'Wallet "%s" (%s) mismatch: DB=%0.2f, Calculated=%0.2f (Diff: %0.2f)',
                    $wallet->name,
                    $wallet->id,
                    $currentBalance,
                    $calculatedBalance,
                    $calculatedBalance - $currentBalance
                ));

                if ($fix) {
                    $wallet->update(['balance' => $calculatedBalance]);
                    $this->info('  -> Fixed.');
                }
            }
        }

        $this->info(sprintf('Wallet check done. Found %d discrepancies.', $discrepancies));
    }

    /**
     * Reconcile all goal progress.
     *
     * Since saved_amount is now computed from wallet balance, this method
     * checks that goals with wallets have consistent progress values.
     *
     * @param  bool  $fix  Whether to fix discrepancies found
     * @return void
     */
    private function reconcileGoals(bool $fix): void
    {
        $this->comment('Checking Goals...');
        $goals = TreasuryGoal::with('wallet')->get();
        $issues = 0;

        foreach ($goals as $goal) {
            if (! $goal->wallet_id) {
                $this->warn(sprintf(
                    'Goal "%s" (%s) has no wallet linked.',
                    $goal->name,
                    $goal->id
                ));
                $issues++;

                continue;
            }

            if (! $goal->wallet) {
                $this->warn(sprintf(
                    'Goal "%s" (%s) has wallet_id but wallet not found.',
                    $goal->name,
                    $goal->id
                ));
                $issues++;

                continue;
            }

            if ($goal->wallet->type !== 'savings') {
                $this->warn(sprintf(
                    'Goal "%s" (%s) is linked to non-savings wallet "%s".',
                    $goal->name,
                    $goal->id,
                    $goal->wallet->name
                ));
                $issues++;
            }

            $walletBalance = (float) $goal->wallet->balance;
            $targetAmount = (float) $goal->target_amount;
            $shouldBeCompleted = $walletBalance >= $targetAmount;

            if ($shouldBeCompleted && ! $goal->is_completed) {
                $this->warn(sprintf(
                    'Goal "%s" should be marked completed (balance %.2f >= target %.2f).',
                    $goal->name,
                    $walletBalance,
                    $targetAmount
                ));
                $issues++;

                if ($fix) {
                    $goal->update(['is_completed' => true]);
                    $this->info('  -> Fixed.');
                }
            }
        }

        $this->info(sprintf('Goal check done. Found %d issues.', $issues));
    }
}
