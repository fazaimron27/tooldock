<?php

/**
 * Recurring Transaction Handler
 *
 * Signal handler that returns data about upcoming recurring transactions
 * scheduled for the next week (scheduled weekly).
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Transaction\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Recurring Transaction Handler
 *
 * Returns signal data for upcoming recurring transactions (scheduled).
 */
class RecurringTransactionHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['scheduled.weekly'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'RecurringTransactionHandler';
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

        $recurring = Transaction::where('user_id', $user->id)
            ->where('is_recurring', true)
            ->whereNotNull('recurring_frequency')
            ->get();

        if ($recurring->isEmpty()) {
            return null;
        }

        $upcomingCount = 0;
        $weekFromNow = now()->addWeek();

        foreach ($recurring as $txn) {
            if (
                $txn->next_occurrence_date &&
                $txn->next_occurrence_date->isBetween(now(), $weekFromNow)
            ) {
                $upcomingCount++;
            }
        }

        if ($upcomingCount === 0) {
            return null;
        }

        Log::info('RecurringTransactionHandler: Upcoming recurring', [
            'user_id' => $user->id,
            'count' => $upcomingCount,
        ]);

        return [
            'type' => 'info',
            'title' => 'Upcoming Recurring Transactions',
            'message' => "You have {$upcomingCount} recurring transaction(s) scheduled for next week. Please ensure sufficient funds are available.",
            'url' => route('treasury.transactions.index'),
            'category' => 'treasury_transaction',
        ];
    }
}
