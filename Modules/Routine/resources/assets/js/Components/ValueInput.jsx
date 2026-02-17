/**
 * ValueInput Component
 *
 * Popover input for entering numeric values on measurable habits.
 * Displays current value + unit, click to open inline editor.
 * Submits via router.post to the toggle endpoint with value param.
 */
import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

export default function ValueInput({ habit, dateKey, currentValue, onOptimistic }) {
  const [open, setOpen] = useState(false);
  const [inputValue, setInputValue] = useState(currentValue?.toString() || '');

  const handleSubmit = (e) => {
    e.preventDefault();

    const numericValue = parseFloat(inputValue) || 0;

    onOptimistic(dateKey, numericValue > 0 ? numericValue : null);

    router.post(
      route('routine.toggle', habit.id),
      { date: dateKey, value: numericValue },
      {
        preserveScroll: true,
        preserveState: true,
        onError: () => onOptimistic(dateKey, undefined),
        onSuccess: () => onOptimistic(dateKey, undefined),
      }
    );

    setOpen(false);
  };

  const displayValue = currentValue != null && currentValue > 0;

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <button
          type="button"
          className={`flex h-8 flex-1 items-center justify-center rounded-md text-xs font-semibold transition-all ${
            displayValue
              ? 'text-white shadow-sm hover:opacity-90'
              : 'bg-muted/50 text-muted-foreground hover:bg-muted'
          }`}
          style={displayValue ? { backgroundColor: habit.color } : undefined}
        >
          {displayValue ? (
            <span>
              {currentValue}
              <span className="ml-0.5 text-xs font-medium opacity-90">{habit.unit}</span>
            </span>
          ) : (
            <span className="text-muted-foreground/50">—</span>
          )}
        </button>
      </PopoverTrigger>
      <PopoverContent className="w-48 p-3" side="top" align="center">
        <form onSubmit={handleSubmit} className="space-y-2">
          <div className="flex items-center gap-2">
            <Input
              type="number"
              step="0.1"
              min="0"
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              placeholder="0"
              className="h-8 text-sm"
              autoFocus
            />
            <span className="shrink-0 text-xs text-muted-foreground">{habit.unit}</span>
          </div>
          <div className="flex gap-1.5">
            <Button type="submit" size="sm" className="h-7 flex-1 text-xs">
              Save
            </Button>
            {displayValue && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="h-7 text-xs text-destructive"
                onClick={() => {
                  setInputValue('0');
                  onOptimistic(dateKey, null);
                  router.post(
                    route('routine.toggle', habit.id),
                    { date: dateKey, value: 0 },
                    {
                      preserveScroll: true,
                      preserveState: true,
                      onError: () => onOptimistic(dateKey, undefined),
                      onSuccess: () => onOptimistic(dateKey, undefined),
                    }
                  );
                  setOpen(false);
                }}
              >
                Clear
              </Button>
            )}
          </div>
        </form>
      </PopoverContent>
    </Popover>
  );
}
