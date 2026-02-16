<?php

/**
 * Transaction Milestone Handler
 *
 * Signal handler that returns data when a user reaches transaction
 * count milestones (10, 25, 50, 100, etc.).
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
 * Transaction Milestone Handler
 *
 * Returns signal data when user reaches transaction count milestones.
 */
class TransactionMilestoneHandler implements SignalHandlerInterface
{
    private const MILESTONES = [10, 25, 50, 100, 250, 500, 1000];

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
        return 'TransactionMilestoneHandler';
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

        $cacheKey = "transaction_count_user_{$user->id}";
        $cachedCount = Cache::get($cacheKey);

        $estimatedCount = $cachedCount !== null ? $cachedCount + 1 : null;
        $nearMilestone = $estimatedCount !== null && in_array($estimatedCount, self::MILESTONES, true);

        if ($cachedCount === null || $nearMilestone) {
            $count = Transaction::where('user_id', $user->id)->count();
        } else {
            $count = $cachedCount + 1;
        }

        Cache::put($cacheKey, $count, now()->addHour());

        if (in_array($count, self::MILESTONES, true)) {
            Log::info('TransactionMilestoneHandler: Milestone reached', [
                'user_id' => $user->id,
                'count' => $count,
            ]);

            return [
                'type' => 'success',
                'title' => "Transaction Milestone Reached: {$count}",
                'message' => "Congratulations! You've recorded {$count} transactions. Your consistent tracking is helping build a clear picture of your finances.",
                'url' => route('treasury.dashboard'),
                'category' => 'treasury_transaction',
            ];
        }

        return null;
    }
}
