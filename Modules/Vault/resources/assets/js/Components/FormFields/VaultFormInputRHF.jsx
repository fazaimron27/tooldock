/**
 * Vault Form Input field component for React Hook Form
 * Wraps VaultFormInput with RHF Controller for form integration
 */
import { cn } from '@/Utils/utils';
import VaultFormInput from '@Vault/Components/VaultFormInput';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';

export default function VaultFormInputRHF({
  name,
  control,
  label,
  required = false,
  type = 'text',
  placeholder,
  maxLength,
  min,
  max,
  helperText,
  className,
  inputClassName,
  children,
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
          <div className={cn('flex gap-2', children && 'items-center')}>
            <VaultFormInput
              id={name}
              type={type}
              {...field}
              value={field.value || ''}
              error={!!error}
              placeholder={placeholder}
              maxLength={maxLength}
              min={min}
              max={max}
              required={required}
              className={cn(children && 'flex-1', inputClassName)}
            />
            {children}
          </div>
          {error && <p className="text-sm text-destructive">{error.message}</p>}
          {helperText && !error && <p className="text-xs text-muted-foreground">{helperText}</p>}
        </div>
      )}
    />
  );
}
