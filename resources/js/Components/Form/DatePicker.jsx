/**
 * DatePicker component combining Popover and Calendar
 * Provides a modern date selection interface that integrates with Inertia forms
 * Handles dates in local timezone to avoid day-shift issues
 */
import { cn } from '@/Utils/utils';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

/**
 * Parse a date string (YYYY-MM-DD) to a local Date object
 * Avoids timezone issues by parsing in local time instead of UTC
 */
function parseLocalDate(dateString) {
  if (!dateString) {
    return null;
  }

  if (dateString instanceof Date) {
    return dateString;
  }
  const parts = dateString.split('-');
  if (parts.length === 3) {
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    return new Date(year, month, day);
  }

  return new Date(dateString);
}

/**
 * Format a Date object to YYYY-MM-DD string in local timezone
 * Avoids timezone issues by using local date methods instead of UTC
 */
function formatLocalDate(date) {
  if (!date || !(date instanceof Date) || isNaN(date.getTime())) {
    return '';
  }

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

export default function DatePicker({
  value,
  onChange,
  label,
  error,
  placeholder = 'Pick a date',
  className,
  ...props
}) {
  const [open, setOpen] = useState(false);

  const dateValue = value ? parseLocalDate(value) : undefined;

  const isValidDate = dateValue && !isNaN(dateValue.getTime());
  const displayDate = isValidDate ? dateValue : null;

  const handleSelect = (date) => {
    if (date) {
      const dateString = formatLocalDate(date);
      onChange?.(dateString);
    } else {
      onChange?.('');
    }
    setOpen(false);
  };

  return (
    <div className={cn('space-y-2', className)}>
      {label && <Label>{label}</Label>}
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            className={cn(
              'w-full justify-start text-left font-normal',
              !displayDate && 'text-muted-foreground',
              error && 'border-destructive'
            )}
            {...props}
          >
            <CalendarIcon className="mr-2 h-4 w-4" />
            {displayDate ? format(displayDate, 'PPP') : <span>{placeholder}</span>}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0" align="start">
          <Calendar mode="single" selected={displayDate} onSelect={handleSelect} initialFocus />
        </PopoverContent>
      </Popover>
      {error && <p className="text-sm text-destructive">{error}</p>}
    </div>
  );
}
