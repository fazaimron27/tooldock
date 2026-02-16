<?php

/**
 * Goal Summary Handler
 *
 * Signal handler that returns data for the monthly goal performance
 * summary, including completion counts and total savings.
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
 * Goal Summary Handler
 *
 * Returns signal data for monthly goal summary (scheduled).
 */
class GoalSummaryHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['scheduled.monthly'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'GoalSummaryHandler';
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
        $currency = settings('treasury_reference_currency', 'IDR');

        $goals = TreasuryGoal::where('user_id', $user->id)->get();

        if ($goals->isEmpty()) {
            return null;
        }

        $totalGoals = $goals->count();
        $completed = $goals->where('is_completed', true)->count();
        $totalSaved = $goals->sum('saved_amount');
        $totalTarget = $goals->sum('target_amount');

        $formattedSaved = $this->currencyFormatter->format((float) $totalSaved, $currency);

        Log::info('GoalSummaryHandler: Monthly summary', [
            'user_id' => $user->id,
            'goals' => $totalGoals,
        ]);

        return [
            'type' => 'info',
            'title' => 'Monthly Goals Summary',
            'message' => "You have {$completed} of {$totalGoals} goals completed. Total amount saved across all goals: {$formattedSaved}.",
            'url' => route('treasury.goals.index'),
            'category' => 'treasury_goal',
        ];
    }
}
