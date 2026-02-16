<?php

/**
 * Goal Deadline Handler
 *
 * Signal handler that returns data when goals have approaching
 * deadlines within the next 30 days (scheduled weekly).
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
 * Goal Deadline Handler
 *
 * Returns signal data when goals have approaching deadlines (scheduled weekly).
 */
class GoalDeadlineHandler implements SignalHandlerInterface
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
        return 'GoalDeadlineHandler';
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

        $upcomingGoals = TreasuryGoal::where('user_id', $user->id)
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now()->addDays(30))
            ->where('deadline', '>', now())
            ->where('is_completed', false)
            ->get();

        if ($upcomingGoals->isEmpty()) {
            return null;
        }

        $names = $upcomingGoals->pluck('name')->take(2)->implode(', ');
        $count = $upcomingGoals->count();
        $extra = $count > 2 ? ' and '.($count - 2).' more' : '';

        Log::info('GoalDeadlineHandler: Upcoming deadlines', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        return [
            'type' => 'info',
            'title' => 'Goal Deadlines Approaching',
            'message' => "You have {$count} goal(s) with deadlines within the next 30 days: {$names}{$extra}. Review your progress to stay on track.",
            'url' => route('treasury.goals.index'),
            'category' => 'treasury_goal',
        ];
    }
}
