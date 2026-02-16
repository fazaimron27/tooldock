<?php

/**
 * Large Transaction Handler
 *
 * Signal handler that returns data when a transaction amount exceeds
 * the user's configured threshold.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Transaction\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Large Transaction Handler
 *
 * Returns signal data when a transaction exceeds a configured threshold.
 */
class LargeTransactionHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter,
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['transaction.created', 'transaction.updated'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'LargeTransactionHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return $data instanceof Transaction;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var Transaction $transaction */
        $transaction = $data;
        $user = $transaction->user;

        if (! $user) {
            return null;
        }

        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $thresholdRef = (float) settings(
            'treasury_large_transaction_threshold',
            1000000,
            $user->id
        );

        $amount = (float) $transaction->amount;
        $walletCurrency = $transaction->wallet?->currency ?? $referenceCurrency;

        $amountInReference = $walletCurrency !== $referenceCurrency
            ? $this->currencyConverter->convert($amount, $walletCurrency, $referenceCurrency)
            : $amount;
        if ($amountInReference < $thresholdRef) {
            return null;
        }

        $threshold = $walletCurrency !== $referenceCurrency
            ? $this->currencyConverter->convert($thresholdRef, $referenceCurrency, $walletCurrency)
            : $thresholdRef;
        $formattedAmount = $this->currencyFormatter->format($amount, $walletCurrency);
        $formattedThreshold = $this->currencyFormatter->format($threshold, $walletCurrency);
        $name = $transaction->name ?? 'Transaction';
        $walletName = $transaction->wallet?->name ?? 'Unknown';

        Log::info('LargeTransactionHandler: Large transaction detected', [
            'transaction_id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => $amount,
            'threshold' => $threshold,
        ]);

        return match ($transaction->type) {
            'expense' => [
                'type' => 'warning',
                'title' => 'Large Expense Alert',
                'message' => "{$formattedAmount} spent on \"{$name}\" exceeds your {$formattedThreshold} threshold. Please review this transaction.",
                'url' => route('treasury.transactions.index'),
                'category' => 'treasury_transaction',
            ],
            'income' => [
                'type' => 'success',
                'title' => 'Large Income Received',
                'message' => "Nice! You received {$formattedAmount} \"{$name}\" in your {$walletName} wallet.",
                'url' => route('treasury.transactions.index'),
                'category' => 'treasury_transaction',
            ],
            'transfer' => [
                'type' => 'info',
                'title' => 'Large Transfer Noted',
                'message' => "{$formattedAmount} transferred for \"{$name}\". Your funds have been reorganized.",
                'url' => route('treasury.transactions.index'),
                'category' => 'treasury_transaction',
            ],
            default => [
                'type' => 'info',
                'title' => 'Large Transaction Detected',
                'message' => "{$formattedAmount} \"{$name}\" in {$walletName} exceeds the {$formattedThreshold} threshold.",
                'url' => route('treasury.transactions.index'),
                'category' => 'treasury_transaction',
            ],
        };
    }
}
