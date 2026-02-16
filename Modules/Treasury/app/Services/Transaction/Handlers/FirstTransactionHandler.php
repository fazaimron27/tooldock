<?php

/**
 * First Transaction Handler
 *
 * Signal handler that returns data when a user makes their first
 * transaction of a particular type (income in wallet, spending in category).
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Transaction\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * First Transaction Handler
 *
 * Returns signal data when user makes their first transaction of a type.
 */
class FirstTransactionHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['transaction.created'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'FirstTransactionHandler';
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

        if ($transaction->type === 'income' && $transaction->wallet_id) {
            $cacheKey = "transaction_first_income_wallet_{$transaction->wallet_id}";

            if (! Cache::has($cacheKey)) {
                $count = Transaction::where('wallet_id', $transaction->wallet_id)
                    ->where('type', 'income')
                    ->where('id', '!=', $transaction->id)
                    ->count();

                if ($count === 0) {
                    $walletName = $transaction->wallet?->name ?? 'wallet';
                    Cache::forever($cacheKey, true);

                    Log::info('FirstTransactionHandler: First income in wallet', [
                        'wallet_id' => $transaction->wallet_id,
                    ]);

                    return [
                        'type' => 'success',
                        'title' => 'First Income Recorded',
                        'message' => "Congratulations! You've recorded your first income in the '{$walletName}' wallet. Your financial journey is underway!",
                        'url' => route('treasury.wallets.show', $transaction->wallet_id),
                        'category' => 'treasury_transaction',
                    ];
                }

                Cache::forever($cacheKey, true);
            }
        }

        $isExpenseOrGoalAllocation = $transaction->type === 'expense'
            || ($transaction->type === 'transfer' && $transaction->goal_id !== null);

        if ($isExpenseOrGoalAllocation && $transaction->category_id) {
            $cacheKey = "transaction_first_spending_category_{$user->id}_{$transaction->category_id}";

            if (! Cache::has($cacheKey)) {
                $count = Transaction::where('user_id', $user->id)
                    ->where('category_id', $transaction->category_id)
                    ->where(function ($q) {
                        $q->where('type', 'expense')
                            ->orWhere(function ($q2) {
                                $q2->where('type', 'transfer')->whereNotNull('goal_id');
                            });
                    })
                    ->where('id', '!=', $transaction->id)
                    ->count();

                if ($count === 0) {
                    $categoryName = $transaction->category?->name ?? 'category';
                    Cache::forever($cacheKey, true);

                    $isGoalAllocation = $transaction->type === 'transfer' && $transaction->goal_id !== null;
                    $messageType = $isGoalAllocation ? 'goal allocation' : 'expense';

                    return [
                        'type' => 'info',
                        'title' => 'New Spending Category',
                        'message' => "You've recorded your first {$messageType} in the '{$categoryName}' category. This category will now be tracked for budget and analysis.",
                        'url' => route('treasury.transactions.index'),
                        'category' => 'treasury_transaction',
                    ];
                }

                Cache::forever($cacheKey, true);
            }
        }

        return null;
    }
}
