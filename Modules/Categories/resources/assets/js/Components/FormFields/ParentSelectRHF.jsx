/**
 * Parent Category Select field component for React Hook Form
 * Hierarchical select for parent categories filtered by type
 */
import { cn } from '@/Utils/utils';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';

export default function ParentSelectRHF({
  name,
  control,
  label = 'Parent Category',
  options = [],
  disabled = false,
  noOptionsMessage,
  className,
}) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          {label && <Label htmlFor={name}>{label}</Label>}
          <select
            id={name}
            value={field.value}
            onChange={(e) => field.onChange(e.target.value || '')}
            disabled={disabled}
            className={cn(
              'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
              error && 'border-destructive'
            )}
          >
            <option value="">None (Top-level category)</option>
            {options.map((parent) => (
              <option key={parent.id} value={parent.id}>
                {parent.name}
              </option>
            ))}
          </select>
          {error && <p className="text-sm text-destructive">{error.message}</p>}
          {noOptionsMessage && !error && (
            <p className="text-sm text-muted-foreground">{noOptionsMessage}</p>
          )}
        </div>
      )}
    />
  );
}
