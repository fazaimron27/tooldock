<?php

namespace Modules\Treasury\Services\Goal\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Goal Completed Handler
 *
 * Returns signal data when a savings goal is fully completed.
 */
class GoalCompletedHandler implements SignalHandlerInterface
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
        return 'GoalCompletedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return $data instanceof Transaction && $data->goal_id !== null;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var Transaction $transaction */
        $transaction = $data;
        $user = $transaction->user;

        if (! $user || ! $transaction->goal_id) {
            return null;
        }

        $goal = TreasuryGoal::find($transaction->goal_id);
        if (! $goal) {
            return null;
        }

        // Refresh to get latest saved_amount
        $goal->refresh();

        // Check if goal just reached 100%
        $percentage = $goal->target_amount > 0
            ? ($goal->saved_amount / $goal->target_amount) * 100
            : 0;

        if ($percentage < 100) {
            return null;
        }

        $cacheKey = "goal_completed_{$goal->id}";
        if (Cache::has($cacheKey)) {
            return null;
        }

        Cache::forever($cacheKey, true);

        $formattedAmount = $this->currencyFormatter->format((float) $goal->target_amount, $goal->currency);

        Log::info('GoalCompletedHandler: Goal completed!', [
            'goal_id' => $goal->id,
            'name' => $goal->name,
        ]);

        return [
            'type' => 'success',
            'title' => 'Goal Complete',
            'message' => "Congratulations! You've achieved your \"{$goal->name}\" goal! Total saved: {$formattedAmount}.",
            'url' => route('treasury.goals.show', $goal->id),
            'category' => 'treasury_goal',
        ];
    }
}
