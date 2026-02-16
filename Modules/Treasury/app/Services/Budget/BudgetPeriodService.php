<?php

namespace Modules\Treasury\Services\Budget;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Modules\Treasury\Models\Transaction;

/**
 * Budget Period Service
 *
 * Handles the lifecycle of manual budget overrides (BudgetPeriods).
 * Also manages the determination of available historical periods for navigation.
 */
class BudgetPeriodService
{
    /**
     * Get or create a budget period instance (Lazy creation).
     */
    public function getOrCreatePeriod(Budget $budget, int $month, int $year): BudgetPeriod
    {
        $periodString = BudgetPeriod::formatPeriod($month, $year);

        return BudgetPeriod::firstOrCreate(
            ['budget_id' => $budget->id, 'period' => $periodString],
            ['amount' => $budget->amount]
        );
    }

    /**
     * Update a budget period's amount and description.
     * Implements semantic auto-cleanup: if the override matches the template, the record is deleted.
     */
    public function updatePeriodAmount(BudgetPeriod $period, float $amount, ?string $description = null): ?BudgetPeriod
    {
        $budget = $period->budget;

        // Semantic Auto-Cleanup: If it matches the template default, don't keep a redundant DB row.
        if (bccomp((string) $amount, (string) $budget->amount, 2) === 0 && empty($description)) {
            $period->delete();

            return null;
        }

        $period->update([
            'amount' => $amount,
            'description' => $description,
        ]);

        return $period->fresh();
    }

    /**
     * Get available periods for a user, including those with transaction history or budget overrides.
     */
    public function getAvailablePeriods(User $user, int $limit = 12): Collection
    {
        // Get periods from saved overrides
        $periodMonths = BudgetPeriod::whereHas('budget', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->select('period')
            ->distinct()
            ->pluck('period');

        // Get periods from transaction history (using database-agnostic scope)
        // Include expenses and goal allocation transfers (transfers with goal_id)
        $transactionMonths = Transaction::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->selectPeriod()
            ->distinct()
            ->pluck('period');

        return $periodMonths->merge($transactionMonths)
            ->unique()
            ->sortDesc()
            ->take($limit)
            ->values()
            ->map(function ($period) {
                $date = Carbon::createFromFormat('Y-m', $period);

                return [
                    'value' => $period,
                    'label' => $date->format('F Y'),
                    'month' => $date->month,
                    'year' => $date->year,
                ];
            });
    }
}
