<?php

/**
 * Routine Hook Registrar
 *
 * Registers Routine module models as hookable outbound webhook triggers.
 * Lives in Routine so that Hook remains fully optional — Routine is the
 * one that knows it wants to integrate with Hook, not the other way around.
 *
 * Loaded by RoutineServiceProvider only when Hook is installed and active.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Services\Registry\HookEventRegistryInterface;
use Carbon\Carbon;
use Modules\Routine\Models\HabitLog;

/**
 * Class RoutineHookRegistrar
 *
 * Registers Routine model lifecycle triggers with the Hook event registry.
 * Called from RoutineServiceProvider, guarded by app()->bound() so it is
 * silently skipped when Hook is uninstalled.
 */
class RoutineHookRegistrar
{
    /**
     * Register Routine model triggers with the Hook event registry.
     *
     * @param  HookEventRegistryInterface  $registry
     * @return void
     */
    public function register(HookEventRegistryInterface $registry): void
    {
        $registry->register(
            key: 'routine.habit_completed',
            label: 'Routine: Habit Completed',
            modelClass: HabitLog::class,
            on: 'created',
            payloadSchema: ['habit_name', 'date', 'value'],
            formatter: function (HabitLog $log): array {
                $habit = $log->habit;

                return [
                    'habit_name' => $habit?->name ?? 'Unknown habit',
                    'date' => $log->completed_at instanceof Carbon
                        ? $log->completed_at->format('d M Y')
                        : date('d M Y', strtotime((string) $log->completed_at)),
                    'value' => $log->value ?? 1,
                ];
            },
        );
    }
}
