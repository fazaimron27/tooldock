<?php

/**
 * Budget Unbudgeted Handler
 *
 * Signal handler that returns data when there is significant unbudgeted
 * spending detected during the daily scheduled check.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Budget\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Services\Budget\BudgetReportingService;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Budget Unbudgeted Handler
 *
 * Returns signal data when there is significant unbudgeted spending (scheduled).
 */
class BudgetUnbudgetedHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly BudgetReportingService $reportingService,
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['scheduled.daily'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'BudgetUnbudgetedHandler';
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

        $summary = $this->reportingService->getMonthlySummary($user, $month, $year);
        $unbudgeted = $summary['total_unbudgeted_spent'] ?? 0;

        $threshold = (float) settings('treasury_unbudgeted_spending_threshold', 100000);

        if ($unbudgeted < $threshold) {
            return null;
        }

        $formattedAmount = $this->currencyFormatter->format($unbudgeted, $currency);

        Log::info('BudgetUnbudgetedHandler: Unbudgeted spending', [
            'user_id' => $user->id,
            'amount' => $unbudgeted,
        ]);

        return [
            'type' => 'info',
            'title' => 'Unbudgeted Spending Detected',
            'message' => "You have spent {$formattedAmount} this month in categories without assigned budgets. Consider creating budgets for better financial tracking.",
            'url' => route('treasury.budgets.index'),
            'category' => 'treasury_budget',
        ];
    }
}
