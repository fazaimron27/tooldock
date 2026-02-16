<?php

/**
 * Income Streak Handler
 *
 * Signal handler that returns data when users maintain a consecutive
 * income recording streak, reaching predefined milestones.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Transaction\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;

/**
 * Income Streak Handler
 *
 * Returns signal data when user maintains a consecutive income recording streak.
 */
class IncomeStreakHandler implements SignalHandlerInterface
{
    private const STREAK_MILESTONES = [3, 7, 14, 30, 60, 90, 180, 365];

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
        return 'IncomeStreakHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return $data instanceof Transaction && $data->type === 'income';
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

        $cacheKey = "transaction_streak_user_{$user->id}";
        $streakData = Cache::get($cacheKey, ['count' => 0, 'last_date' => null]);

        $today = now()->format('Y-m-d');
        $lastDate = $streakData['last_date'];

        if ($lastDate === null) {
            $newStreak = 1;
        } elseif ($lastDate === $today) {
            return null;
        } elseif ($lastDate === now()->subDay()->format('Y-m-d')) {
            $newStreak = $streakData['count'] + 1;
        } else {
            $newStreak = 1;
        }

        Cache::put($cacheKey, [
            'count' => $newStreak,
            'last_date' => $today,
        ], now()->addDays(35));

        if (in_array($newStreak, self::STREAK_MILESTONES, true)) {
            Log::info('IncomeStreakHandler: Streak milestone reached', [
                'user_id' => $user->id,
                'streak' => $newStreak,
            ]);

            return [
                'type' => 'success',
                'title' => "Income Streak: {$newStreak} Consecutive Days",
                'message' => "You've recorded income for {$newStreak} consecutive days. Keep up the excellent financial discipline!",
                'url' => route('treasury.dashboard'),
                'category' => 'treasury_transaction',
            ];
        }

        return null;
    }
}
