/**
 * Color Picker field component for React Hook Form
 * Combines color picker and text input for hex values
 */
import { cn } from '@/Utils/utils';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';

export default function ColorPickerRHF({ name, control, label = 'Color', className }) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          {label && <Label htmlFor={name}>{label}</Label>}
          <div className="flex items-center gap-2">
            <input
              type="color"
              id={name}
              value={field.value || '#000000'}
              onChange={(e) => field.onChange(e.target.value)}
              className="h-10 w-20 rounded border border-input cursor-pointer"
            />
            <input
              type="text"
              value={field.value || ''}
              onChange={(e) => field.onChange(e.target.value)}
              className={cn(
                'flex-1 flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                error && 'border-destructive'
              )}
              placeholder="#000000"
            />
          </div>
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}
