<?php

/**
 * Reference Currency Changed Handler
 *
 * Handles automatic conversion of budget amounts and threshold settings
 * when the user changes their reference currency.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Settings;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Settings\Models\Setting;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

/**
 * Reference Currency Changed Handler
 *
 * Handles automatic conversion of budget amounts and threshold settings when
 * the user changes their reference currency. This ensures all amounts maintain
 * their real-world value after the currency change.
 *
 * Note: Goals are not converted as they use their linked wallet's currency.
 */
class ReferenceCurrencyChangedHandler implements SignalHandlerInterface
{
    /**
     * Threshold settings that should be converted with currency changes.
     * These are stored as amounts in the reference currency.
     */
    private const THRESHOLD_SETTINGS = [
        'treasury_wallet_low_balance_threshold',
        'treasury_wallet_critical_threshold',
        'treasury_large_transaction_threshold',
        'treasury_rollover_debt_threshold',
        'treasury_unbudgeted_spending_threshold',
    ];

    public function __construct(
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['settings.changed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'ReferenceCurrencyChangedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        if (! is_array($data) || ! isset($data['user']) || ! ($data['user'] instanceof User)) {
            return false;
        }

        $changedKeys = $data['changed_keys'] ?? [];

        return in_array('treasury_reference_currency', $changedKeys, true);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        $user = $data['user'];

        $oldCurrency = $data['old_values']['treasury_reference_currency'] ?? null;
        $newCurrency = settings('treasury_reference_currency', 'IDR');

        if (! $oldCurrency || $oldCurrency === $newCurrency) {
            return null;
        }

        Log::info('ReferenceCurrencyChangedHandler: Converting currencies', [
            'user_id' => $user->id,
            'old_currency' => $oldCurrency,
            'new_currency' => $newCurrency,
        ]);

        [$convertedBudgets, $convertedPeriods] = $this->convertBudgets($user, $oldCurrency, $newCurrency);
        $convertedThresholds = $this->convertThresholds($user, $oldCurrency, $newCurrency);

        if ($convertedBudgets === 0 && $convertedThresholds === 0) {
            return null;
        }

        $parts = [];
        if ($convertedBudgets > 0) {
            $periodInfo = $convertedPeriods > 0 ? " and {$convertedPeriods} period override(s)" : '';
            $parts[] = "{$convertedBudgets} budget(s){$periodInfo}";
        }
        if ($convertedThresholds > 0) {
            $parts[] = "{$convertedThresholds} threshold setting(s)";
        }

        return [
            'type' => 'info',
            'title' => 'Currency Conversion Complete',
            'message' => "Your reference currency has been changed from {$oldCurrency} to {$newCurrency}. "
                .implode(' and ', $parts).' have been converted to maintain their value.',
            'url' => route('treasury.budgets.index'),
            'category' => 'treasury_settings',
        ];
    }

    /**
     * Convert all budget amounts and their period overrides from old currency to new currency.
     *
     * @param  User  $user
     * @param  string  $oldCurrency
     * @param  string  $newCurrency
     * @return array{int, int} [budgetCount, periodCount]
     */
    private function convertBudgets(User $user, string $oldCurrency, string $newCurrency): array
    {
        $budgets = Budget::where('user_id', $user->id)
            ->where('currency', $oldCurrency)
            ->get();

        $budgetCount = 0;
        $periodCount = 0;

        foreach ($budgets as $budget) {
            $convertedAmount = $this->currencyConverter->convert(
                (float) $budget->amount,
                $oldCurrency,
                $newCurrency
            );

            if ($convertedAmount !== null) {
                $budget->update([
                    'amount' => $convertedAmount,
                    'currency' => $newCurrency,
                ]);
                $budgetCount++;

                $periodCount += $this->convertBudgetPeriods($budget, $oldCurrency, $newCurrency);
            }
        }

        Log::info('ReferenceCurrencyChangedHandler: Converted budgets and periods', [
            'user_id' => $user->id,
            'budget_count' => $budgetCount,
            'period_count' => $periodCount,
        ]);

        return [$budgetCount, $periodCount];
    }

    /**
     * Convert all period overrides for a budget.
     *
     * @param  Budget  $budget
     * @param  string  $oldCurrency
     * @param  string  $newCurrency
     * @return int
     */
    private function convertBudgetPeriods(Budget $budget, string $oldCurrency, string $newCurrency): int
    {
        $periods = BudgetPeriod::where('budget_id', $budget->id)->get();

        $count = 0;
        foreach ($periods as $period) {
            $convertedAmount = $this->currencyConverter->convert(
                (float) $period->amount,
                $oldCurrency,
                $newCurrency
            );

            if ($convertedAmount !== null) {
                $period->update([
                    'amount' => $convertedAmount,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Convert threshold settings from old currency to new currency.
     *
     * @param  User  $user
     * @param  string  $oldCurrency
     * @param  string  $newCurrency
     * @return int
     */
    private function convertThresholds(User $user, string $oldCurrency, string $newCurrency): int
    {
        $count = 0;

        foreach (self::THRESHOLD_SETTINGS as $key) {
            $currentValue = settings($key);

            if ($currentValue === null) {
                continue;
            }

            $convertedValue = $this->currencyConverter->convert(
                (float) $currentValue,
                $oldCurrency,
                $newCurrency
            );

            if ($convertedValue !== null) {
                Setting::where('user_id', $user->id)
                    ->where('key', $key)
                    ->update(['value' => round($convertedValue, 2)]);

                $count++;

                Log::debug('ReferenceCurrencyChangedHandler: Converted threshold', [
                    'key' => $key,
                    'old_value' => $currentValue,
                    'new_value' => $convertedValue,
                ]);
            }
        }

        Log::info('ReferenceCurrencyChangedHandler: Converted thresholds', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        return $count;
    }
}
