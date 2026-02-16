<?php

namespace Modules\Treasury\Services\Wallet\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Wallet Recovery Handler
 *
 * Returns signal data when wallet recovers from low/critical balance.
 */
class WalletRecoveryHandler implements SignalHandlerInterface
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
        return 'WalletRecoveryHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        if (! $data instanceof Transaction) {
            return false;
        }

        // Income always increases balance
        if ($data->type === 'income') {
            return true;
        }

        // Note: Transfers are NOT supported for recovery because $transaction->wallet
        // always returns the SOURCE wallet. Recovery on destination wallet would require
        // the observer to dispatch separate signals for each wallet, which is not implemented.

        // Deleted expense increases balance (money returned)
        if ($event === 'transaction.deleted' && $data->type === 'expense') {
            return true;
        }

        // Updated expense might increase balance (if amount was reduced)
        if ($event === 'transaction.updated' && $data->type === 'expense') {
            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var Transaction $transaction */
        $transaction = $data;
        $user = $transaction->user;
        $wallet = $transaction->wallet;

        if (! $user || ! $wallet) {
            return null;
        }

        // Check if we previously sent a low balance signal
        $hadCritical = Cache::has("wallet_signal_{$wallet->id}_critical_sent");
        $hadLow = Cache::has("wallet_signal_{$wallet->id}_low_balance_sent");

        if (! $hadCritical && ! $hadLow) {
            return null;
        }

        $balance = (float) $wallet->balance;
        $walletCurrency = $wallet->currency ?? settings('treasury_reference_currency', 'IDR');
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        // Threshold is in reference currency
        $lowThreshold = (float) settings('treasury_wallet_low_balance_threshold', 500000);

        // Convert balance to reference currency for comparison
        $balanceInReference = $walletCurrency !== $referenceCurrency
            ? $this->currencyConverter->convert($balance, $walletCurrency, $referenceCurrency)
            : $balance;

        // Check if balance is now above threshold (in reference currency)
        if ($balanceInReference < $lowThreshold) {
            return null;
        }

        $cacheKey = "wallet_signal_{$wallet->id}_recovered";
        if (Cache::has($cacheKey)) {
            return null;
        }

        Cache::put($cacheKey, true, now()->addDays(1));

        $formattedBalance = $this->currencyFormatter->format($balance, $walletCurrency);

        Log::info('WalletRecoveryHandler: Wallet recovered', [
            'wallet_id' => $wallet->id,
            'balance' => $balance,
        ]);

        return [
            'type' => 'success',
            'title' => 'Wallet Balance Recovered',
            'message' => "Your '{$wallet->name}' wallet has recovered to a healthy balance of {$formattedBalance}. Great job restoring your funds!",
            'url' => route('treasury.wallets.show', $wallet->id),
            'category' => 'treasury_wallet',
        ];
    }
}
