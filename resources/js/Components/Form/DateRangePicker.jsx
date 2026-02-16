import { cn } from '@/Utils/utils';
import {
  differenceInDays,
  endOfMonth,
  endOfYear,
  format,
  isSameDay,
  parseISO,
  startOfMonth,
  startOfToday,
  startOfYear,
  subDays,
  subMonths,
} from 'date-fns';
import {
  ArrowRight,
  CalendarIcon,
  ChevronRight,
  CircleDot,
  Clock,
  Sparkles,
  X,
} from 'lucide-react';
import * as React from 'react';

import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

export function DateRangePicker({
  startDate,
  endDate,
  onRangeChange,
  className,
  placeholder = 'Select your duration',
  resolution = 'daily',
}) {
  const [date, setDate] = React.useState({
    from: startDate ? parseISO(startDate) : undefined,
    to: endDate ? parseISO(endDate) : undefined,
  });

  const [isOpen, setIsOpen] = React.useState(false);

  // Sync with props if they change externally
  React.useEffect(() => {
    setDate({
      from: startDate ? parseISO(startDate) : undefined,
      to: endDate ? parseISO(endDate) : undefined,
    });
  }, [startDate, endDate]);

  const handleSelect = (range) => {
    // If selecting the same start date, clear it
    if (range?.from && date?.from && isSameDay(range.from, date.from) && !range.to) {
      setDate({ from: undefined, to: undefined });
      return;
    }
    setDate(range);
  };

  const applyRange = () => {
    if (date?.from && date?.to) {
      onRangeChange?.(format(date.from, 'yyyy-MM-dd'), format(date.to, 'yyyy-MM-dd'));
      setIsOpen(false);
    } else if (!date?.from && !date?.to) {
      onRangeChange?.(null, null);
      setIsOpen(false);
    }
  };

  const clearRange = (e) => {
    e?.stopPropagation();
    setDate({ from: undefined, to: undefined });
    onRangeChange?.(null, null);
    if (!e) setIsOpen(false);
  };

  const today = startOfToday();

  const presets = React.useMemo(() => {
    const common = [
      { label: 'Today', from: today, to: today },
      { label: '7D', from: subDays(today, 6), to: today },
      { label: '30D', from: subDays(today, 29), to: today },
    ];

    if (resolution === 'monthly') {
      return [
        ...common,
        { label: 'Quarter', from: startOfMonth(subMonths(today, 2)), to: endOfMonth(today) },
        { label: 'Year', from: startOfYear(today), to: endOfYear(today) },
      ];
    }

    if (resolution === 'yearly') {
      return [
        { label: 'Year', from: startOfYear(today), to: endOfYear(today) },
        { label: '2 Years', from: startOfYear(subMonths(today, 23)), to: endOfYear(today) },
        { label: 'All', from: parseISO('2000-01-01'), to: today },
      ];
    }

    return [...common, { label: 'Month', from: startOfMonth(today), to: endOfMonth(today) }];
  }, [resolution, today]);

  const selectionDiff = date?.from && date?.to ? differenceInDays(date.to, date.from) + 1 : 0;

  return (
    <div className={cn('grid gap-2', className)}>
      <Popover open={isOpen} onOpenChange={setIsOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            className={cn(
              'w-full h-12 p-0 bg-card/50 border-border/40 hover:border-primary/40 hover:bg-card transition-all duration-300 shadow-sm overflow-hidden group',
              isOpen && 'ring-2 ring-primary/20 border-primary/40 shadow-lg shadow-primary/5'
            )}
          >
            <div className="flex items-center h-full w-full">
              <div className="px-4 flex items-center justify-center border-r border-border/30 h-full bg-muted/20 group-hover:bg-primary/5 transition-colors">
                <CalendarIcon
                  className={cn(
                    'w-4 h-4 transition-colors',
                    isOpen ? 'text-primary' : 'text-muted-foreground'
                  )}
                />
              </div>

              <div className="flex-1 px-4 flex items-center justify-between overflow-hidden">
                {!date.from ? (
                  <span className="text-sm font-medium text-muted-foreground italic">
                    {placeholder}
                  </span>
                ) : (
                  <div className="flex items-center gap-3 w-full">
                    <div className="flex flex-col items-start leading-none shrink-0">
                      <span className="text-xs text-muted-foreground uppercase font-black tracking-widest opacity-60 mb-1">
                        From
                      </span>
                      <span className="text-sm font-bold tabular-nums">
                        {format(date.from, 'MMM dd, yyyy')}
                      </span>
                    </div>

                    <div className="flex-1 flex items-center justify-center text-primary/30">
                      <ArrowRight className="w-6 h-6 stroke-[2.5] shrink-0" />
                    </div>

                    <div className="flex flex-col items-end leading-none shrink-0 text-right">
                      <span className="text-xs text-muted-foreground uppercase font-black tracking-widest opacity-60 mb-1">
                        To
                      </span>
                      <span className="text-sm font-bold tabular-nums">
                        {date.to ? format(date.to, 'MMM dd, yyyy') : '...'}
                      </span>
                    </div>
                  </div>
                )}
              </div>

              {date.from && (
                <div
                  onClick={clearRange}
                  className="px-3 h-full flex items-center justify-center text-muted-foreground/40 hover:text-rose-500 hover:bg-rose-500/5 transition-all"
                >
                  <X className="w-4 h-4" />
                </div>
              )}
            </div>
          </Button>
        </PopoverTrigger>

        <PopoverContent
          className="w-auto p-0 bg-background/95 backdrop-blur-xl border-border/40 shadow-2xl rounded-2xl overflow-hidden"
          align="start"
          sideOffset={8}
        >
          {/* Popover Header */}
          <div className="p-4 border-b border-border/30 flex items-center justify-between bg-muted/10">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                <Sparkles className="w-4 h-4" />
              </div>
              <div>
                <h4 className="text-sm font-black uppercase tracking-tight leading-none">
                  Smart Range
                </h4>
                <p className="text-[10px] text-muted-foreground font-medium mt-1">
                  {selectionDiff > 0
                    ? `Selected duration: ${selectionDiff} days`
                    : 'Pick any custom range on the calendars'}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-1.5 p-1 bg-muted/30 rounded-full border border-border/50">
              {presets.map((p) => (
                <button
                  key={p.label}
                  onClick={() => handleSelect({ from: p.from, to: p.to })}
                  className={cn(
                    'px-3 py-1.5 text-[10px] font-black uppercase tracking-tight rounded-full transition-all',
                    date.from && date.to && isSameDay(date.from, p.from) && isSameDay(date.to, p.to)
                      ? 'bg-primary text-primary-foreground shadow-sm shadow-primary/20'
                      : 'hover:bg-primary/10 text-muted-foreground hover:text-primary'
                  )}
                >
                  {p.label}
                </button>
              ))}
            </div>
          </div>

          {/* Popover Calendars */}
          <div className="flex flex-col md:flex-row divide-x divide-border/30 p-2">
            <Calendar
              initialFocus
              mode="range"
              defaultMonth={date?.from}
              selected={date}
              onSelect={handleSelect}
              numberOfMonths={2}
              className="p-3"
            />
          </div>

          {/* Popover Footer */}
          <div className="p-4 border-t border-border/30 flex items-center justify-between bg-muted/20">
            <Button
              variant="ghost"
              size="sm"
              className="text-xs font-bold text-muted-foreground hover:text-rose-500"
              onClick={() => {
                setDate({ from: undefined, to: undefined });
              }}
            >
              Reset
            </Button>

            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                className="text-xs font-bold bg-background shadow-sm"
                onClick={() => setIsOpen(false)}
              >
                Cancel
              </Button>
              <Button
                size="sm"
                className="text-xs font-bold px-6 shadow-md shadow-primary/20"
                onClick={applyRange}
                disabled={!date?.from || !date?.to}
              >
                Apply Selection
              </Button>
            </div>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  );
}
