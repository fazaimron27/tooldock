/**
 * DateTimePicker with Analog Clock
 * Features: Calendar for date, circular clock for time selection
 * Material Design inspired clock picker with smooth interactions
 */
import { cn } from '@/Utils/utils';
import { format } from 'date-fns';
import { CalendarIcon, Clock } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

/**
 * Parse an ISO datetime string to a local Date object and time components
 */
function parseDateTime(dateTimeString) {
  if (!dateTimeString) {
    const now = new Date();
    return {
      date: now,
      hours: now.getHours() === 0 ? 12 : now.getHours() > 12 ? now.getHours() - 12 : now.getHours(),
      minutes: now.getMinutes(),
      period: now.getHours() >= 12 ? 'PM' : 'AM',
    };
  }

  if (dateTimeString instanceof Date) {
    const h = dateTimeString.getHours();
    return {
      date: dateTimeString,
      hours: h === 0 ? 12 : h > 12 ? h - 12 : h,
      minutes: dateTimeString.getMinutes(),
      period: h >= 12 ? 'PM' : 'AM',
    };
  }

  try {
    const dateObj = new Date(dateTimeString);
    if (isNaN(dateObj.getTime())) {
      const parts = dateTimeString.split('-');
      if (parts.length === 3) {
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const now = new Date();
        return {
          date: new Date(year, month, day),
          hours:
            now.getHours() === 0 ? 12 : now.getHours() > 12 ? now.getHours() - 12 : now.getHours(),
          minutes: now.getMinutes(),
          period: now.getHours() >= 12 ? 'PM' : 'AM',
        };
      }
      return { date: null, hours: 12, minutes: 0, period: 'AM' };
    }

    const h = dateObj.getHours();
    return {
      date: dateObj,
      hours: h === 0 ? 12 : h > 12 ? h - 12 : h,
      minutes: dateObj.getMinutes(),
      period: h >= 12 ? 'PM' : 'AM',
    };
  } catch {
    return { date: null, hours: 12, minutes: 0, period: 'AM' };
  }
}

/**
 * Format date and time to ISO datetime string with timezone
 */
function formatDateTime(date, hours, minutes, period) {
  if (!date) return '';

  let h24 = hours;
  if (period === 'AM') {
    h24 = hours === 12 ? 0 : hours;
  } else {
    h24 = hours === 12 ? 12 : hours + 12;
  }

  const dateObj = new Date(date);
  dateObj.setHours(h24, minutes, 0, 0);

  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const day = String(dateObj.getDate()).padStart(2, '0');
  const hStr = String(dateObj.getHours()).padStart(2, '0');
  const mStr = String(dateObj.getMinutes()).padStart(2, '0');

  const tzOffset = -dateObj.getTimezoneOffset();
  const tzSign = tzOffset >= 0 ? '+' : '-';
  const tzHours = String(Math.floor(Math.abs(tzOffset) / 60)).padStart(2, '0');
  const tzMinutes = String(Math.abs(tzOffset) % 60).padStart(2, '0');

  return `${year}-${month}-${day}T${hStr}:${mStr}:00${tzSign}${tzHours}:${tzMinutes}`;
}

/**
 * Circular Clock Face Component - Simple clickable numbers in a circle
 */
function ClockFace({ mode, value, onChange }) {
  const numbers =
    mode === 'hours'
      ? [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
      : [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55];

  return (
    <div className="relative w-[180px] h-[180px] rounded-full bg-muted/50">
      {numbers.map((num, i) => {
        const numAngle = (i / 12) * 360 - 90;
        const radius = 68;
        const x = Math.cos((numAngle * Math.PI) / 180) * radius;
        const y = Math.sin((numAngle * Math.PI) / 180) * radius;

        const isSelected =
          mode === 'hours' ? value === num : value >= num && value < num + 5 && num <= 55;

        return (
          <button
            key={num}
            type="button"
            onClick={() => onChange(num)}
            className={cn(
              'absolute w-8 h-8 -translate-x-1/2 -translate-y-1/2 rounded-full',
              'flex items-center justify-center text-xs font-medium',
              'transition-colors',
              isSelected
                ? 'bg-primary text-primary-foreground font-semibold'
                : 'text-foreground hover:bg-muted'
            )}
            style={{
              left: `calc(50% + ${x}px)`,
              top: `calc(50% + ${y}px)`,
            }}
          >
            {mode === 'hours' ? num : String(num).padStart(2, '0')}
          </button>
        );
      })}
    </div>
  );
}

export default function DateTimePicker({
  value,
  onChange,
  label,
  error,
  placeholder = 'Pick date and time',
  className,
  showTime = true,
  ...props
}) {
  const [open, setOpen] = useState(false);
  const [clockMode, setClockMode] = useState('hours');
  const parsed = parseDateTime(value);
  const [selectedDate, setSelectedDate] = useState(parsed.date);
  const [selectedHours, setSelectedHours] = useState(parsed.hours);
  const [selectedMinutes, setSelectedMinutes] = useState(parsed.minutes);
  const [selectedPeriod, setSelectedPeriod] = useState(parsed.period);

  const [hourInput, setHourInput] = useState(String(parsed.hours).padStart(2, '0'));
  const [minuteInput, setMinuteInput] = useState(String(parsed.minutes).padStart(2, '0'));

  useEffect(() => {
    const parsed = parseDateTime(value);
    setSelectedDate(parsed.date);
    setSelectedHours(parsed.hours);
    setSelectedMinutes(parsed.minutes);
    setSelectedPeriod(parsed.period);
    setHourInput(String(parsed.hours).padStart(2, '0'));
    setMinuteInput(String(parsed.minutes).padStart(2, '0'));
  }, [value]);

  useEffect(() => {
    if (open) {
      setClockMode('hours');
    }
  }, [open]);

  const handleDateSelect = (date) => {
    setSelectedDate(date);
    if (date && !showTime) {
      const newValue = formatDateTime(date, 12, 0, 'AM');
      onChange?.(newValue);
      setOpen(false);
    }
  };

  const handleHoursChange = (hours) => {
    setSelectedHours(hours);
    setHourInput(String(hours).padStart(2, '0'));
    globalThis.setTimeout(() => setClockMode('minutes'), 300);
  };

  const handleMinutesChange = (minutes) => {
    setSelectedMinutes(minutes);
    setMinuteInput(String(minutes).padStart(2, '0'));
  };

  const handleDone = () => {
    if (selectedDate) {
      const newValue = formatDateTime(selectedDate, selectedHours, selectedMinutes, selectedPeriod);
      onChange?.(newValue);
    }
    setOpen(false);
  };

  const displayTime = `${String(selectedHours).padStart(2, '0')}:${String(selectedMinutes).padStart(2, '0')} ${selectedPeriod}`;
  const displayValue = selectedDate
    ? showTime
      ? `${format(selectedDate, 'PPP')} at ${displayTime}`
      : format(selectedDate, 'PPP')
    : null;

  return (
    <div className={cn('space-y-2', className)}>
      {label && <Label>{label}</Label>}
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            className={cn(
              'w-full justify-start text-left font-normal',
              !displayValue && 'text-muted-foreground',
              error && 'border-destructive'
            )}
            {...props}
          >
            <CalendarIcon className="mr-2 h-4 w-4" />
            {displayValue || <span>{placeholder}</span>}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0" align="start">
          <div className="flex flex-col sm:flex-row">
            {/* Calendar Section */}
            <div>
              <Calendar
                mode="single"
                selected={selectedDate}
                onSelect={handleDateSelect}
                initialFocus
              />
            </div>

            {/* Time Section */}
            {showTime && (
              <div className="flex flex-col min-w-0 sm:min-w-[220px]">
                {/* Time header with editable hour/minute inputs */}
                <div className="flex items-center justify-center gap-1 py-2 px-3">
                  <Clock className="h-3.5 w-3.5 mr-1.5 text-muted-foreground" />
                  <input
                    type="text"
                    inputMode="numeric"
                    maxLength={2}
                    value={hourInput}
                    onFocus={() => setClockMode('hours')}
                    onChange={(e) => {
                      const raw = e.target.value.replace(/\D/g, '').slice(0, 2);
                      setHourInput(raw);
                    }}
                    onBlur={() => {
                      const val = parseInt(hourInput, 10);
                      if (!isNaN(val) && val >= 1 && val <= 12) {
                        setSelectedHours(val);
                        setHourInput(String(val).padStart(2, '0'));
                      } else {
                        setHourInput(String(selectedHours).padStart(2, '0'));
                      }
                    }}
                    className={cn(
                      'w-8 text-center text-lg font-semibold tabular-nums transition-colors rounded px-1 bg-transparent border-0 outline-none',
                      clockMode === 'hours'
                        ? 'text-primary bg-primary/10'
                        : 'text-muted-foreground hover:text-foreground'
                    )}
                  />
                  <span className="text-lg font-semibold text-muted-foreground">:</span>
                  <input
                    type="text"
                    inputMode="numeric"
                    maxLength={2}
                    value={minuteInput}
                    onFocus={() => setClockMode('minutes')}
                    onChange={(e) => {
                      const raw = e.target.value.replace(/\D/g, '').slice(0, 2);
                      setMinuteInput(raw);
                    }}
                    onBlur={() => {
                      const val = parseInt(minuteInput, 10);
                      if (!isNaN(val) && val >= 0 && val <= 59) {
                        setSelectedMinutes(val);
                        setMinuteInput(String(val).padStart(2, '0'));
                      } else {
                        setMinuteInput(String(selectedMinutes).padStart(2, '0'));
                      }
                    }}
                    className={cn(
                      'w-8 text-center text-lg font-semibold tabular-nums transition-colors rounded px-1 bg-transparent border-0 outline-none',
                      clockMode === 'minutes'
                        ? 'text-primary bg-primary/10'
                        : 'text-muted-foreground hover:text-foreground'
                    )}
                  />
                  {/* AM/PM toggle */}
                  <div className="flex flex-col ml-2 text-xs font-medium gap-0.5">
                    <button
                      type="button"
                      onClick={() => setSelectedPeriod('AM')}
                      className={cn(
                        'px-2 py-0.5 rounded transition-colors',
                        selectedPeriod === 'AM'
                          ? 'bg-primary text-primary-foreground'
                          : 'text-muted-foreground hover:bg-muted'
                      )}
                    >
                      AM
                    </button>
                    <button
                      type="button"
                      onClick={() => setSelectedPeriod('PM')}
                      className={cn(
                        'px-2 py-0.5 rounded transition-colors',
                        selectedPeriod === 'PM'
                          ? 'bg-primary text-primary-foreground'
                          : 'text-muted-foreground hover:bg-muted'
                      )}
                    >
                      PM
                    </button>
                  </div>
                </div>

                {/* Clock face */}
                <div className="flex justify-center p-3 flex-1">
                  <ClockFace
                    mode={clockMode}
                    value={clockMode === 'hours' ? selectedHours : selectedMinutes}
                    onChange={clockMode === 'hours' ? handleHoursChange : handleMinutesChange}
                  />
                </div>
              </div>
            )}
          </div>

          {/* Done button - full width across both sections */}
          {showTime && (
            <div className="p-3 pt-0 border-t">
              <Button onClick={handleDone} className="w-full" size="sm">
                Done
              </Button>
            </div>
          )}
        </PopoverContent>
      </Popover>
      {error && <p className="text-sm text-destructive">{error}</p>}
    </div>
  );
}
