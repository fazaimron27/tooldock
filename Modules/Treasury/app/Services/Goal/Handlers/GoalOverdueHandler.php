<?php

/**
 * Goal Overdue Handler
 *
 * Signal handler that returns data when goals are past their
 * deadline and still incomplete (scheduled weekly).
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Goal\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Goal Overdue Handler
 *
 * Returns signal data when goals are past their deadline (scheduled weekly).
 */
class GoalOverdueHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['scheduled.weekly'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'GoalOverdueHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return $data instanceof User;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data;

        $overdueGoals = TreasuryGoal::where('user_id', $user->id)
            ->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->where('is_completed', false)
            ->get();

        if ($overdueGoals->isEmpty()) {
            return null;
        }

        $names = $overdueGoals->pluck('name')->take(2)->implode(', ');
        $count = $overdueGoals->count();
        $extra = $count > 2 ? ' and '.($count - 2).' more' : '';

        Log::info('GoalOverdueHandler: Overdue goals', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        return [
            'type' => 'warning',
            'title' => 'Overdue Goals Require Attention',
            'message' => "{$count} goal(s) have passed their deadline: {$names}{$extra}. Consider extending the deadline or increasing contributions.",
            'url' => route('treasury.goals.index'),
            'category' => 'treasury_goal',
        ];
    }
}
