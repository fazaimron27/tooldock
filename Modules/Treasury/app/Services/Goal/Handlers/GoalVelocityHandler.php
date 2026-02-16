<?php

/**
 * Goal Velocity Handler
 *
 * Signal handler that returns data when saving velocity indicates
 * a goal is falling behind its expected progress toward the deadline.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Goal\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Goal Velocity Handler
 *
 * Returns signal data when saving velocity indicates on-track for deadline.
 */
class GoalVelocityHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter
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
        return 'GoalVelocityHandler';
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
        if (! $goal || ! $goal->deadline || $goal->is_completed) {
            return null;
        }

        $goal->refresh();

        $remaining = $goal->target_amount - $goal->saved_amount;
        if ($remaining <= 0) {
            return null;
        }

        $daysRemaining = now()->diffInDays($goal->deadline);
        if ($daysRemaining <= 0) {
            return null;
        }

        $created = $goal->created_at ?? now()->subMonth();
        $totalDays = $created->diffInDays($goal->deadline);
        $daysElapsed = $created->diffInDays(now());

        if ($daysElapsed <= 0 || $totalDays <= 0) {
            return null;
        }

        $expectedProgress = ($daysElapsed / $totalDays) * 100;
        $actualProgress = ($goal->saved_amount / $goal->target_amount) * 100;

        if ($actualProgress < ($expectedProgress - 15)) {
            $difference = (int) ($expectedProgress - $actualProgress);
            $actualInt = (int) $actualProgress;
            $expectedInt = (int) $expectedProgress;

            Log::info('GoalVelocityHandler: Behind schedule', [
                'goal_id' => $goal->id,
                'actual' => $actualProgress,
                'expected' => $expectedProgress,
                'behind' => $difference,
            ]);

            return [
                'type' => 'warning',
                'title' => 'Goal Progress Behind Schedule',
                'message' => "Your \"{$goal->name}\" goal is at {$actualInt}%, but should be at {$expectedInt}% by now ({$difference}% behind). Increase contributions to get back on track.",
                'url' => route('treasury.goals.show', $goal->id),
                'category' => 'treasury_goal',
            ];
        }

        return null;
    }
}
