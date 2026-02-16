<?php

/**
 * Wallet Balance Handler
 *
 * Signal handler that returns data when a wallet balance drops below
 * configured low or critical thresholds.
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
 * Wallet Balance Handler
 *
 * Returns signal data when wallet balance drops below thresholds.
 */
class WalletBalanceHandler implements SignalHandlerInterface
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
        return 'WalletBalanceHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        if (! $data instanceof Transaction) {
            return false;
        }

        if ($data->type === 'expense') {
            return true;
        }

        if ($data->type === 'transfer') {
            return true;
        }

        if ($event === 'transaction.deleted' && $data->type === 'income') {
            return true;
        }

        if ($event === 'transaction.updated' && $data->type === 'income') {
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

        $balance = (float) $wallet->balance;
        $walletCurrency = $wallet->currency ?? settings('treasury_reference_currency', 'IDR');
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $criticalThresholdRef = (float) settings('treasury_wallet_critical_threshold', 100000);
        $lowThresholdRef = (float) settings('treasury_wallet_low_balance_threshold', 500000);

        $balanceInReference = $walletCurrency !== $referenceCurrency
            ? $this->currencyConverter->convert($balance, $walletCurrency, $referenceCurrency)
            : $balance;

        if ($balanceInReference >= $lowThresholdRef) {
            return null;
        }

        $criticalThreshold = $walletCurrency !== $referenceCurrency
            ? $this->currencyConverter->convert($criticalThresholdRef, $referenceCurrency, $walletCurrency)
            : $criticalThresholdRef;
        $lowThreshold = $walletCurrency !== $referenceCurrency
            ? $this->currencyConverter->convert($lowThresholdRef, $referenceCurrency, $walletCurrency)
            : $lowThresholdRef;

        $formattedBalance = $this->currencyFormatter->format($balance, $walletCurrency);
        $formattedLowThreshold = $this->currencyFormatter->format($lowThreshold, $walletCurrency);
        $formattedCriticalThreshold = $this->currencyFormatter->format($criticalThreshold, $walletCurrency);

        if ($balanceInReference < $criticalThresholdRef) {
            $cacheKey = "wallet_signal_{$wallet->id}_critical";
            if (Cache::has($cacheKey)) {
                return null;
            }

            Cache::put($cacheKey, true, now()->endOfDay());
            Cache::put("wallet_signal_{$wallet->id}_critical_sent", true, now()->addDays(7));

            Log::info('WalletBalanceHandler: Critical balance', [
                'wallet_id' => $wallet->id,
                'balance' => $balance,
                'threshold' => $criticalThreshold,
            ]);

            return [
                'type' => 'alert',
                'title' => 'Critical Balance Alert',
                'message' => "Your '{$wallet->name}' wallet has dropped to {$formattedBalance}, below the critical threshold of {$formattedCriticalThreshold}. Immediate attention recommended.",
                'url' => route('treasury.wallets.show', $wallet->id),
                'category' => 'treasury_wallet',
            ];
        }

        $cacheKey = "wallet_signal_{$wallet->id}_low_balance";
        if (Cache::has($cacheKey)) {
            return null;
        }

        Cache::put($cacheKey, true, now()->endOfDay());
        Cache::put("wallet_signal_{$wallet->id}_low_balance_sent", true, now()->addDays(7));

        Log::info('WalletBalanceHandler: Low balance', [
            'wallet_id' => $wallet->id,
            'balance' => $balance,
            'threshold' => $lowThreshold,
        ]);

        return [
            'type' => 'warning',
            'title' => 'Low Wallet Balance',
            'message' => "Your '{$wallet->name}' wallet is at {$formattedBalance}, below the {$formattedLowThreshold} threshold. Consider adding funds.",
            'url' => route('treasury.wallets.show', $wallet->id),
            'category' => 'treasury_wallet',
        ];
    }
}
