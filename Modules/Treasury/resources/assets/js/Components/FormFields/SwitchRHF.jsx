/**
 * Switch field component for React Hook Form
 * Wraps Switch with RHF Controller for form integration
 */
import { cn } from '@/Utils/utils';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

export default function SwitchRHF({
  name,
  control,
  label,
  description,
  className,
  id,
  ...switchProps
}) {
  const inputId = id || name;

  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          {label && <Label htmlFor={inputId}>{label}</Label>}
          <div className="flex items-center gap-3">
            <Switch
              id={inputId}
              checked={field.value ?? false}
              onCheckedChange={field.onChange}
              {...switchProps}
            />
            {description && (
              <span className="text-sm text-muted-foreground">
                {typeof description === 'function' ? description(field.value) : description}
              </span>
            )}
          </div>
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}
