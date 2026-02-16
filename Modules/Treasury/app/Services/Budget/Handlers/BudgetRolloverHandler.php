<?php

namespace Modules\Treasury\Services\Budget\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Services\Budget\BudgetRolloverService;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Budget Rollover Handler
 *
 * Returns signal data when rollover debt is accumulating (scheduled monthly).
 */
class BudgetRolloverHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly BudgetRolloverService $rolloverService,
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['scheduled.monthly'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'BudgetRolloverHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return $data instanceof User;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data;
        $currency = settings('treasury_reference_currency', 'IDR');
        $month = (int) now()->format('m');
        $year = (int) now()->format('Y');

        // Calculate total negative rollover (debt) across all budgets
        $budgets = Budget::where('user_id', $user->id)
            ->where('rollover_enabled', true)
            ->get();

        $totalDebt = 0;
        foreach ($budgets as $budget) {
            $rollover = $this->rolloverService->calculateRollover($budget, $month, $year);
            if ($rollover < 0) {
                $totalDebt += abs($rollover);
            }
        }

        // Minimum threshold to trigger alert (in reference currency)
        $threshold = (float) settings('treasury_rollover_debt_threshold', 100000);

        if ($totalDebt < $threshold) {
            return null;
        }

        $formattedAmount = $this->currencyFormatter->format($totalDebt, $currency);

        Log::info('BudgetRolloverHandler: Rollover debt', [
            'user_id' => $user->id,
            'amount' => $totalDebt,
        ]);

        return [
            'type' => 'warning',
            'title' => 'Budget Rollover Debt Alert',
            'message' => "You have {$formattedAmount} in accumulated budget debt carried forward from previous months. Consider adjusting your spending or budget limits.",
            'url' => route('treasury.budgets.index'),
            'category' => 'treasury_budget',
        ];
    }
}
