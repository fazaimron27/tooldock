/**
 * Type Input field component for React Hook Form
 * Combines text input with datalist for type suggestions
 */
import { cn } from '@/Utils/utils';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';

export default function TypeInputRHF({
  name,
  control,
  label = 'Type',
  required = false,
  types = [],
  placeholder = 'Enter category type',
  helperText,
  className,
}) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          {label && (
            <Label htmlFor={name}>
              {label} {required && <span className="text-destructive">*</span>}
            </Label>
          )}
          <div className="relative">
            <input
              id={name}
              type="text"
              list={`${name}-options`}
              value={field.value}
              onChange={(e) => {
                const value = e.target.value.toLowerCase().replace(/[^a-z0-9_-]/g, '');
                field.onChange(value);
              }}
              className={cn(
                'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                error && 'border-destructive'
              )}
              placeholder={placeholder}
              required={required}
              autoComplete="off"
            />
            <datalist id={`${name}-options`}>
              {types.map((type) => (
                <option key={type} value={type}>
                  {type.charAt(0).toUpperCase() + type.slice(1)}
                </option>
              ))}
            </datalist>
          </div>
          {error && <p className="text-sm text-destructive">{error.message}</p>}
          {helperText && !error && <p className="text-xs text-muted-foreground">{helperText}</p>}
        </div>
      )}
    />
  );
}
