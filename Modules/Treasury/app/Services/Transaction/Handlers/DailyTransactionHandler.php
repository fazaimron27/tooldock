<?php

namespace Modules\Treasury\Services\Transaction\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Daily Transaction Handler
 *
 * Returns signal data for daily transaction summary (scheduled).
 */
class DailyTransactionHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter,
        private readonly CurrencyConverter $currencyConverter
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
        return 'DailyTransactionHandler';
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
        $yesterday = now()->subDay()->format('Y-m-d');
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $transactions = Transaction::where('user_id', $user->id)
            ->whereDate('date', $yesterday)
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

        Log::info('DailyTransactionHandler: Daily summary', [
            'user_id' => $user->id,
            'transactions' => $transactions->count(),
        ]);

        return [
            'type' => 'info',
            'title' => 'Daily Transaction Summary',
            'message' => "Yesterday you recorded {$transactions->count()} transactions. Income: {$formattedIncome}, Expenses: {$formattedExpense}.",
            'url' => route('treasury.transactions.index'),
            'category' => 'treasury_transaction',
        ];
    }
}
