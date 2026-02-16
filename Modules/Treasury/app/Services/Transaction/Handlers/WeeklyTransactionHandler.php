<?php

namespace Modules\Treasury\Services\Transaction\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Weekly Transaction Handler
 *
 * Returns signal data for weekly transaction summary (scheduled).
 */
class WeeklyTransactionHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter,
        private readonly CurrencyConverter $currencyConverter
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
        return 'WeeklyTransactionHandler';
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
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$lastWeekStart, $lastWeekEnd])
            ->with('wallet')
            ->get();

        if ($transactions->isEmpty()) {
            return null;
        }

        $income = 0;
        $expense = 0;

        foreach ($transactions as $txn) {
            $currency = $txn->wallet?->currency ?? $referenceCurrency;
            $converted = $currency !== $referenceCurrency
                ? $this->currencyConverter->convert((float) $txn->amount, $currency, $referenceCurrency)
                : (float) $txn->amount;

            if ($txn->type === 'income') {
                $income += $converted;
            } elseif ($txn->type === 'expense') {
                $expense += $converted;
            }
        }

        $formattedIncome = $this->currencyFormatter->format($income, $referenceCurrency);
        $formattedExpense = $this->currencyFormatter->format($expense, $referenceCurrency);
        $net = $income - $expense;
        $netFormatted = $this->currencyFormatter->format(abs($net), $referenceCurrency);
        $netStatus = $net >= 0 ? 'positive' : 'negative';

        return [
            'type' => 'info',
            'title' => 'Weekly Transaction Summary',
            'message' => "Last week: {$transactions->count()} transactions recorded. Income: {$formattedIncome}, Expenses: {$formattedExpense}. Net {$netStatus}: {$netFormatted}.",
            'url' => route('treasury.dashboard'),
            'category' => 'treasury_transaction',
        ];
    }
}
