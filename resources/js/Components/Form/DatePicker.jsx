/**
 * DatePicker component combining Popover and Calendar
 * Provides a modern date selection interface that integrates with Inertia forms
 */
import { cn } from '@/Utils/utils';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

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

  // Convert value to Date object if it's a string
  const dateValue = value ? (typeof value === 'string' ? new Date(value) : value) : undefined;

  // Validate date
  const isValidDate = dateValue && !isNaN(dateValue.getTime());
  const displayDate = isValidDate ? dateValue : null;

  const handleSelect = (date) => {
    onChange?.(date);
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
