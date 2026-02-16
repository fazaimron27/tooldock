<?php

namespace Modules\Treasury\Services\Goal;

use Modules\Core\Models\User;
use Modules\Treasury\Models\TreasuryGoal;

class GoalProgressService
{
    /**
     * Get all goals with progress.
     */
    public function getGoals(User $user, array $filters = []): array
    {
        $query = TreasuryGoal::where('user_id', $user->id)
            ->with(['category', 'wallet'])
            ->incomplete();

        $this->applyFilters($query, $filters);

        return $query->orderBy('deadline')
            ->get()
            ->map(fn ($goal) => [
                'id' => $goal->id,
                'name' => $goal->name,
                'currency' => $goal->currency,
                'target_amount' => $goal->target_amount,
                'saved_amount' => $goal->saved_amount,
                'remaining' => (string) $goal->remaining_amount,
                'progress' => $goal->progress_percentage,
                'deadline' => $goal->deadline,
                'is_overdue' => $goal->is_overdue,
                'is_completed' => $goal->is_completed,
                'category' => $goal->category ? [
                    'id' => $goal->category->id,
                    'name' => $goal->category->name,
                    'slug' => $goal->category->slug,
                    'color' => $goal->category->color,
                ] : null,
                'wallet' => $goal->wallet ? [
                    'id' => $goal->wallet->id,
                    'name' => $goal->wallet->name,
                    'balance' => $goal->wallet->balance,
                ] : null,
            ])
            ->toArray();
    }

    /**
     * Apply filters to goal queries.
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }
    }
}
