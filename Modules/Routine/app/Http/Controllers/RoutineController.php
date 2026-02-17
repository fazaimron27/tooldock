<?php

/**
 * Routine Controller
 *
 * Handles habit CRUD operations and daily completion toggling.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Routine\Http\Requests\StoreHabitRequest;
use Modules\Routine\Http\Requests\UpdateHabitRequest;
use Modules\Routine\Models\Habit;
use Modules\Routine\Services\HabitService;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Class RoutineController
 *
 * Provides methods for listing habits, creating/updating/deleting habits,
 * toggling daily completions, and fetching heatmap data.
 */
class RoutineController extends Controller
{
    public function __construct(
        private readonly HabitService $habitService
    ) {}

    /**
     * Display the habit tracker index page.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Habit::class);

        $habits = $this->habitService->getActiveHabitsWithLogs();
        $stats = $this->habitService->computeStats($habits);
        $inactiveHabits = $this->habitService->getInactiveHabits();

        return Inertia::render('Modules::Routine/Index', [
            'habits' => $habits,
            'inactiveHabits' => $inactiveHabits,
            'stats' => $stats,
            'settings' => [
                'week_start' => settings('routine_week_start', 'monday'),
                'default_goal_per_week' => (int) settings('routine_default_goal_per_week', 7),
                'default_habit_type' => settings('routine_default_habit_type', 'boolean'),
            ],
        ]);
    }

    /**
     * Store a newly created habit.
     *
     * @param  StoreHabitRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreHabitRequest $request): RedirectResponse
    {
        Habit::create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return redirect()->route('routine.index')
            ->with('success', 'Habit created successfully.');
    }

    /**
     * Update the specified habit.
     *
     * @param  UpdateHabitRequest  $request
     * @param  Habit  $routine
     * @return RedirectResponse
     */
    public function update(UpdateHabitRequest $request, Habit $routine): RedirectResponse
    {
        $validated = $request->validated();

        if (isset($validated['status']) && $validated['status'] !== $routine->status) {
            $this->habitService->handleStatusTransition($routine, $validated['status'], $validated);
        } else {
            $routine->update($validated);
        }

        return redirect()->route('routine.index')
            ->with('success', 'Habit updated successfully.');
    }

    /**
     * Remove the specified habit.
     *
     * @param  Habit  $routine
     * @return RedirectResponse
     */
    public function destroy(Habit $routine): RedirectResponse
    {
        $this->authorize('delete', $routine);

        $routine->delete();

        return redirect()->route('routine.index')
            ->with('success', 'Habit deleted successfully.');
    }

    /**
     * Toggle a habit completion for a specific date.
     *
     * Creates a log entry if none exists for the given date,
     * or deletes it if one already exists (un-toggle).
     *
     * @param  Request  $request
     * @param  Habit  $habit
     * @return RedirectResponse
     */
    public function toggle(Request $request, Habit $habit): RedirectResponse
    {
        $this->authorize('update', $habit);

        abort_unless(
            $habit->status === 'active',
            HttpResponse::HTTP_FORBIDDEN,
            'Cannot toggle an inactive habit. Restore it first.'
        );

        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'value' => 'nullable|numeric|min:0',
        ]);

        $message = $this->habitService->toggleCompletion($habit, $validated);

        if ($message === null) {
            return redirect()->back();
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Get aggregated heatmap data for the last 365 days.
     *
     * Returns a JSON array of { date, count } objects for all active
     * habits belonging to the authenticated user.
     *
     * @return JsonResponse
     */
    public function heatmapData(): JsonResponse
    {
        $this->authorize('viewAny', Habit::class);

        $data = $this->habitService->getHeatmapData();

        return response()->json($data);
    }
}
