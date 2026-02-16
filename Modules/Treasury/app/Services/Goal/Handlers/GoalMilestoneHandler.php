<?php

/**
 * Goal Milestone Handler
 *
 * Signal handler that returns data when a user reaches savings
 * milestones (25%, 50%, 75%) on a goal.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Goal\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Goal Milestone Handler
 *
 * Returns signal data when user reaches savings milestones (25%, 50%, 75%).
 */
class GoalMilestoneHandler implements SignalHandlerInterface
{
    private const MILESTONES = [25, 50, 75];

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
        return 'GoalMilestoneHandler';
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
        if (! $goal || $goal->target_amount <= 0) {
            return null;
        }

        $goal->refresh();

        $percentage = ((float) $goal->saved_amount / (float) $goal->target_amount) * 100;

        if ($percentage >= 100) {
            return null;
        }

        $reversedMilestones = array_reverse(self::MILESTONES);

        foreach ($reversedMilestones as $milestone) {
            if ($percentage >= $milestone) {
                $cacheKey = "goal_milestone_{$goal->id}_{$milestone}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                foreach (self::MILESTONES as $m) {
                    if ($m <= $milestone) {
                        Cache::forever("goal_milestone_{$goal->id}_{$m}", true);
                    }
                }

                Log::info('GoalMilestoneHandler: Milestone reached', [
                    'goal_id' => $goal->id,
                    'milestone' => $milestone,
                    'actual_percentage' => $percentage,
                ]);

                $actualPercent = (int) $percentage;
                $milestoneLabel = $actualPercent > $milestone ? "Passed {$milestone}%" : "{$milestone}% Reached";

                return [
                    'type' => 'success',
                    'title' => "Goal Milestone: {$milestoneLabel}",
                    'message' => "Great progress! Your \"{$goal->name}\" goal is now {$actualPercent}% funded. Keep up the momentum!",
                    'url' => route('treasury.goals.show', $goal->id),
                    'category' => 'treasury_goal',
                ];
            }
        }

        return null;
    }
}
