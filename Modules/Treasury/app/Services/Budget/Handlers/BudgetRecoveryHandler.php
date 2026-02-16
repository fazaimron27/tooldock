<?php

/**
 * Budget Recovery Handler
 *
 * Signal handler that returns data when a budget recovers from overbudget
 * to safe status. Triggers on transaction changes, budget period updates,
 * and budget template updates.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Budget\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Budget\BudgetReportingService;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Budget Recovery Handler
 *
 * Returns signal data when budget recovers from overbudget to safe status.
 * Triggers on:
 * - Transaction changes (expenses reduced/deleted)
 * - Budget period updates (limit increased)
 */
class BudgetRecoveryHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly BudgetReportingService $reportingService,
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return [
            'transaction.updated',
            'transaction.deleted',
            'budgetperiod.created',
            'budgetperiod.updated',
            'budget.updated',
        ];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'BudgetRecoveryHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        if ($data instanceof Transaction) {
            return $data->type === 'expense'
                || ($data->type === 'transfer' && $data->goal_id !== null);
        }

        if ($data instanceof BudgetPeriod) {
            return true;
        }

        if (is_array($data) && isset($data['budgetPeriod']) && $data['budgetPeriod'] instanceof BudgetPeriod) {
            return true;
        }
        if (is_array($data) && isset($data['budget']) && $data['budget'] instanceof Budget) {
            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        if ($data instanceof Transaction) {
            return $this->handleTransaction($data);
        }

        if ($data instanceof BudgetPeriod) {
            return $this->handleBudgetPeriod($data);
        }

        if (is_array($data) && isset($data['budgetPeriod']) && $data['budgetPeriod'] instanceof BudgetPeriod) {
            return $this->handleBudgetPeriod($data['budgetPeriod']);
        }
        if (is_array($data) && isset($data['budget']) && $data['budget'] instanceof Budget) {
            return $this->handleBudget($data['budget']);
        }

        return null;
    }

    /**
     * Handle recovery check triggered by transaction change.
     *
     * @param  Transaction  $transaction
     * @return array|null
     */
    private function handleTransaction(Transaction $transaction): ?array
    {
        $user = $transaction->user;

        if (! $user || ! $transaction->category_id) {
            return null;
        }

        $budget = Budget::where('user_id', $user->id)
            ->where('category_id', $transaction->category_id)
            ->first();

        if (! $budget) {
            return null;
        }

        $month = (int) $transaction->date->format('m');
        $year = (int) $transaction->date->format('Y');

        return $this->checkRecovery($budget, $user, $month, $year);
    }

    /**
     * Handle recovery check triggered by budget period update.
     *
     * @param  BudgetPeriod  $budgetPeriod
     * @return array|null
     */
    private function handleBudgetPeriod(BudgetPeriod $budgetPeriod): ?array
    {
        $budget = $budgetPeriod->budget;
        if (! $budget) {
            return null;
        }

        $user = $budget->user;
        if (! $user) {
            return null;
        }

        $periodDate = $budgetPeriod->getPeriodDate();
        $month = (int) $periodDate->month;
        $year = (int) $periodDate->year;

        return $this->checkRecovery($budget, $user, $month, $year);
    }

    /**
     * Handle recovery check triggered by budget template update.
     *
     * @param  Budget  $budget
     * @return array|null
     */
    private function handleBudget(Budget $budget): ?array
    {
        $user = $budget->user;
        if (! $user) {
            return null;
        }

        $month = (int) now()->month;
        $year = (int) now()->year;

        return $this->checkRecovery($budget, $user, $month, $year);
    }

    /**
     * Check if budget has recovered and return signal data if so.
     *
     * @param  Budget  $budget
     * @param  User  $user
     * @param  int  $month
     * @param  int  $year
     * @return array|null
     */
    private function checkRecovery(Budget $budget, User $user, int $month, int $year): ?array
    {
        $period = BudgetPeriod::formatPeriod($month, $year);

        $hadOverbudget = Cache::has("budget_signal_{$budget->id}_{$period}_overbudget_sent");
        $hadWarning = Cache::has("budget_signal_{$budget->id}_{$period}_warning_sent");

        if (! $hadOverbudget && ! $hadWarning) {
            return null;
        }

        $details = $this->reportingService->getCategoryBudgetDetails($budget, $month, $year);
        if (! $details) {
            return null;
        }

        $percentage = $details['health'] ?? 0;
        $warnThreshold = (float) settings('treasury_budget_warning_threshold', 80);

        if ($percentage >= $warnThreshold) {
            return null;
        }

        $cacheKey = "budget_signal_{$budget->id}_{$period}_recovered";
        if (Cache::has($cacheKey)) {
            return null;
        }

        Cache::put($cacheKey, true, now()->endOfMonth());

        Cache::forget("budget_signal_{$budget->id}_{$period}_overbudget_sent");
        Cache::forget("budget_signal_{$budget->id}_{$period}_warning_sent");

        $budgetName = $budget->category?->name ?? 'Budget';
        $currency = $details['currency'] ?? settings('treasury_reference_currency', 'IDR');
        $remaining = ($details['total_limit'] ?? 0) - ($details['spent'] ?? 0);
        $formattedRemaining = $this->currencyFormatter->format($remaining, $currency);

        Log::info('BudgetRecoveryHandler: Budget recovered', [
            'budget_id' => $budget->id,
            'percentage' => $percentage,
        ]);

        return [
            'type' => 'success',
            'title' => 'Budget Recovered',
            'message' => "Your '{$budgetName}' budget has returned to safe levels with {$formattedRemaining} remaining. Great job managing your spending!",
            'url' => route('treasury.budgets.index'),
            'category' => 'treasury_budget',
        ];
    }
}
