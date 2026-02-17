/**
 * HabitRow Component
 *
 * Renders a single habit row in the multi-day grid layout.
 * Supports both boolean (✓/✕ toggle) and measurable (numeric value) habits.
 * Shows weekly progress indicator and streak badge.
 */
import ValueInput from '@Routine/Components/ValueInput';
import { getHabitIcon } from '@Routine/Utils/habitIcons';
import { router } from '@inertiajs/react';
import { format, isAfter, startOfToday } from 'date-fns';
import {
  Archive,
  Check,
  Flame,
  MoreHorizontal,
  Pause,
  Pencil,
  Play,
  Trash2,
  X,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

export default function HabitRow({ habit, days, onEdit, onDelete, onStatusChange }) {
  const [optimistic, setOptimistic] = useState({});
  const today = startOfToday();

  const IconComponent = getHabitIcon(habit.icon);

  const logMap = useMemo(() => {
    const map = {};
    (habit.logs || []).forEach((log) => {
      const key = format(new Date(log.completed_at), 'yyyy-MM-dd');
      map[key] = {
        completed: true,
        value: log.value != null ? parseFloat(log.value) : null,
      };
    });
    return map;
  }, [habit.logs]);

  const getLogState = useCallback(
    (dateKey) => {
      if (dateKey in optimistic) {
        const opt = optimistic[dateKey];
        if (opt === undefined) return logMap[dateKey] || { completed: false, value: null };
        if (opt === null) return { completed: false, value: null };
        if (typeof opt === 'number') return { completed: true, value: opt };
        return { completed: opt, value: null };
      }
      return logMap[dateKey] || { completed: false, value: null };
    },
    [logMap, optimistic]
  );

  const handleOptimistic = useCallback((dateKey, value) => {
    if (value === undefined) {
      setOptimistic((prev) => {
        const next = { ...prev };
        delete next[dateKey];
        return next;
      });
    } else {
      setOptimistic((prev) => ({ ...prev, [dateKey]: value }));
    }
  }, []);

  const handleBooleanToggle = (date) => {
    if (isAfter(date, today)) return;

    const key = format(date, 'yyyy-MM-dd');
    const currentState = getLogState(key);
    const newState = !currentState.completed;

    handleOptimistic(key, newState);

    router.post(
      route('routine.toggle', habit.id),
      { date: key },
      {
        preserveScroll: true,
        preserveState: true,
        onError: () => handleOptimistic(key, undefined),
        onSuccess: () => handleOptimistic(key, undefined),
      }
    );
  };

  const weeklyCompletions = useMemo(() => {
    return days.reduce((count, day) => {
      const key = format(day, 'yyyy-MM-dd');
      const state = getLogState(key);
      if (habit.is_measurable) {
        return (state.value ?? 0) > 0 ? count + 1 : count;
      }
      return state.completed ? count + 1 : count;
    }, 0);
  }, [days, getLogState, habit.is_measurable]);

  const progressPercent =
    habit.goal_per_week > 0
      ? Math.min(100, Math.round((weeklyCompletions / habit.goal_per_week) * 100))
      : 0;

  return (
    <div className="flex items-center gap-3 rounded-lg border border-transparent px-3 py-2.5 transition-colors hover:border-border hover:bg-muted/30">
      {/* Icon + Name + Progress */}
      <div className="flex w-56 shrink-0 items-center gap-3">
        {/* Progress Ring */}
        <div className="relative flex h-9 w-9 shrink-0 items-center justify-center">
          <svg className="absolute h-9 w-9 -rotate-90" viewBox="0 0 36 36">
            <circle cx="18" cy="18" r="15" fill="none" className="stroke-muted" strokeWidth="2.5" />
            <circle
              cx="18"
              cy="18"
              r="15"
              fill="none"
              strokeWidth="2.5"
              strokeDasharray={`${progressPercent * 0.9425} 94.25`}
              strokeLinecap="round"
              style={{ stroke: habit.color }}
            />
          </svg>
          <IconComponent className="relative h-4 w-4" style={{ color: habit.color }} />
        </div>

        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <span className="truncate text-sm font-medium">{habit.name}</span>
            {habit.current_streak > 0 && (
              <Badge variant="secondary" className="shrink-0 gap-0.5 px-1.5 text-xs">
                <Flame className="h-3 w-3 text-orange-500" />
                {habit.current_streak}
              </Badge>
            )}
          </div>
          {habit.is_measurable && habit.target_value && (
            <span className="block text-xs text-muted-foreground">
              {parseFloat(habit.target_value)} {habit.unit} / day
            </span>
          )}
          <span className="block text-xs text-muted-foreground">{habit.goal_per_week}x / week</span>
        </div>
      </div>

      {/* Day Cells */}
      <TooltipProvider delayDuration={200}>
        <div className="flex flex-1 items-center gap-1">
          {days.map((day) => {
            const isFuture = isAfter(day, today);
            const dateKey = format(day, 'yyyy-MM-dd');
            const state = getLogState(dateKey);
            const dayLabel = format(day, 'EEE');
            const dateLabel = format(day, 'MMM d');

            if (isFuture) {
              return (
                <div
                  key={dateKey}
                  className="flex h-8 flex-1 items-center justify-center rounded-md text-xs text-muted-foreground/30"
                >
                  —
                </div>
              );
            }

            if (habit.is_measurable) {
              return (
                <ValueInput
                  key={dateKey}
                  habit={habit}
                  date={day}
                  dateKey={dateKey}
                  currentValue={state.value}
                  onOptimistic={handleOptimistic}
                />
              );
            }

            return (
              <Tooltip key={dateKey}>
                <TooltipTrigger asChild>
                  <button
                    type="button"
                    onClick={() => handleBooleanToggle(day)}
                    className={`flex h-8 flex-1 items-center justify-center rounded-md text-sm font-semibold transition-all ${
                      state.completed
                        ? 'text-white shadow-sm hover:opacity-90'
                        : 'bg-muted/50 text-muted-foreground/40 hover:bg-muted hover:text-muted-foreground'
                    }`}
                    style={state.completed ? { backgroundColor: habit.color } : undefined}
                  >
                    {state.completed ? <Check className="h-4 w-4" /> : <X className="h-4 w-4" />}
                  </button>
                </TooltipTrigger>
                <TooltipContent side="top" className="text-xs">
                  <span>
                    {dayLabel}, {dateLabel}
                    {state.completed ? ' — Completed' : ''}
                  </span>
                </TooltipContent>
              </Tooltip>
            );
          })}
        </div>
      </TooltipProvider>

      {/* Actions Dropdown */}
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" size="icon" className="h-7 w-7 shrink-0">
            <MoreHorizontal className="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem onClick={() => onEdit(habit)}>
            <Pencil className="mr-2 h-4 w-4" />
            Edit
          </DropdownMenuItem>
          {habit.status !== 'paused' && (
            <DropdownMenuItem onClick={() => onStatusChange(habit, 'paused')}>
              <Pause className="mr-2 h-4 w-4" />
              Pause
            </DropdownMenuItem>
          )}
          {habit.status !== 'archived' && (
            <DropdownMenuItem onClick={() => onStatusChange(habit, 'archived')}>
              <Archive className="mr-2 h-4 w-4" />
              Archive
            </DropdownMenuItem>
          )}
          {habit.status !== 'active' && (
            <DropdownMenuItem onClick={() => onStatusChange(habit, 'active')}>
              <Play className="mr-2 h-4 w-4" />
              Restore
            </DropdownMenuItem>
          )}
          <DropdownMenuItem
            className="text-destructive focus:text-destructive"
            onClick={() => onDelete(habit)}
          >
            <Trash2 className="mr-2 h-4 w-4" />
            Delete
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
