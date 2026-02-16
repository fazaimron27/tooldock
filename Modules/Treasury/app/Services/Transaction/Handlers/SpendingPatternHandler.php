<?php

namespace Modules\Treasury\Services\Transaction\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Spending Pattern Handler
 *
 * Returns signal data when category spending significantly exceeds previous month.
 * Uses month-over-month comparison for more reliable pattern detection.
 */
class SpendingPatternHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter,
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return [
            'transaction.created',
            'transaction.updated',
            'transaction.deleted',
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
        return 'SpendingPatternHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        if (! $data instanceof Transaction) {
            return false;
        }

        // Support expenses and goal allocation transfers (transfers with goal_id)
        return $data->type === 'expense'
            || ($data->type === 'transfer' && $data->goal_id !== null);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var Transaction $transaction */
        $transaction = $data;

        if (! $transaction->category_id) {
            return null;
        }

        $user = $transaction->user;
        if (! $user) {
            return null;
        }

        // Only check after day 5 of the month to have meaningful data
        if (now()->day < 5) {
            return null;
        }

        // Cache to prevent spam - one alert per category per month
        $cacheKey = "spending_pattern_{$transaction->category_id}_".now()->format('Y-m');
        if (Cache::has($cacheKey)) {
            return null;
        }

        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        // Compare same period: Day 1 to current day of this month vs last month
        $dayOfMonth = now()->day;

        $thisMonthStart = now()->startOfMonth();
        $thisMonthSpending = $this->getSpendingForPeriod(
            $user,
            $transaction->category_id,
            $thisMonthStart,
            now(),
            $referenceCurrency
        );

        // Same period last month (day 1 to same day)
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->startOfMonth()->addDays($dayOfMonth - 1)->endOfDay();
        $lastMonthSpending = $this->getSpendingForPeriod(
            $user,
            $transaction->category_id,
            $lastMonthStart,
            $lastMonthEnd,
            $referenceCurrency
        );

        // Need meaningful baseline (at least 50k in reference currency)
        $minThreshold = (float) settings('treasury_spending_pattern_min_threshold', 50000);
        if ($lastMonthSpending < $minThreshold) {
            return null;
        }

        $changePercent = (($thisMonthSpending - $lastMonthSpending) / $lastMonthSpending) * 100;

        // Require significant increase (50%+) to reduce noise
        $changeThreshold = (float) settings('treasury_spending_pattern_change_threshold', 50);
        if ($changePercent >= $changeThreshold) {
            $categoryName = $transaction->category?->name ?? 'Unknown';
            $formattedThisMonth = $this->currencyFormatter->format($thisMonthSpending, $referenceCurrency);
            $formattedLastMonth = $this->currencyFormatter->format($lastMonthSpending, $referenceCurrency);
            $changeInt = (int) $changePercent;

            Cache::put($cacheKey, true, now()->endOfMonth());

            Log::info('SpendingPatternHandler: Category overspending detected', [
                'category_id' => $transaction->category_id,
                'this_month' => $thisMonthSpending,
                'last_month' => $lastMonthSpending,
                'change' => $changePercent,
                'day_of_month' => $dayOfMonth,
            ]);

            return [
                'type' => 'warning',
                'title' => 'Spending Pattern Change',
                'message' => "Your '{$categoryName}' spending is up {$changeInt}% this month: {$formattedThisMonth} (Day 1-{$dayOfMonth}) vs {$formattedLastMonth} same period last month.",
                'url' => route('treasury.transactions.index'),
                'category' => 'treasury_transaction',
            ];
        }

        return null;
    }

    private function getSpendingForPeriod($user, $categoryId, $start, $end, $referenceCurrency): float
    {
        $transactions = Transaction::where('user_id', $user->id)
            ->where('category_id', $categoryId)
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->whereBetween('date', [$start, $end])
            ->with('wallet')
            ->get();

        $total = 0;
        foreach ($transactions as $txn) {
            $currency = $txn->wallet?->currency ?? $referenceCurrency;
            if ($currency !== $referenceCurrency) {
                $total += $this->currencyConverter->convert(
                    (float) $txn->amount,
                    $currency,
                    $referenceCurrency
                );
            } else {
                $total += (float) $txn->amount;
            }
        }

        return $total;
    }
}
