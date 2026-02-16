<?php

namespace Modules\Treasury\Services\Goal\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Goal Stagnation Handler
 *
 * Returns signal data when goals have no progress for extended period (scheduled weekly).
 */
class GoalStagnationHandler implements SignalHandlerInterface
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
        return 'GoalStagnationHandler';
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

        $stagnantGoals = TreasuryGoal::where('user_id', $user->id)
            ->where('is_completed', false)
            ->whereDoesntHave('transactions', function ($query) {
                $query->where('date', '>=', now()->subDays(14));
            })
            ->get();

        if ($stagnantGoals->isEmpty()) {
            return null;
        }

        $names = $stagnantGoals->pluck('name')->take(2)->implode(', ');
        $count = $stagnantGoals->count();
        $extra = $count > 2 ? ' and '.($count - 2).' more' : '';

        Log::info('GoalStagnationHandler: Stagnant goals', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        return [
            'type' => 'info',
            'title' => 'Inactive Goals',
            'message' => "{$count} goal(s) have had no contributions in the past 14 days: {$names}{$extra}. Consider making a deposit to keep progressing.",
            'url' => route('treasury.goals.index'),
            'category' => 'treasury_goal',
        ];
    }
}
