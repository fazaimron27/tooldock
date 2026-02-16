<?php

/**
 * Wallet Recovery Handler
 *
 * Signal handler that returns data when a wallet recovers from
 * a previously reported low or critical balance state.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

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

        if ($data->type === 'income') {
            return true;
        }

        if ($event === 'transaction.deleted' && $data->type === 'expense') {
            return true;
        }

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

        $hadCritical = Cache::has("wallet_signal_{$wallet->id}_critical_sent");
        $hadLow = Cache::has("wallet_signal_{$wallet->id}_low_balance_sent");

        if (! $hadCritical && ! $hadLow) {
            return null;
        }

        $balance = (float) $wallet->balance;
        $walletCurrency = $wallet->currency ?? settings('treasury_reference_currency', 'IDR');
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $lowThreshold = (float) settings('treasury_wallet_low_balance_threshold', 500000);

        $balanceInReference = $walletCurrency !== $referenceCurrency
            ? $this->currencyConverter->convert($balance, $walletCurrency, $referenceCurrency)
            : $balance;

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
