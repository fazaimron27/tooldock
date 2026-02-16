/**
 * Currency Input field component for React Hook Form
 * Wraps CurrencyInput with RHF Controller for form integration
 */
import { cn } from '@/Utils/utils';
import CurrencyInput from '@Treasury/Components/CurrencyInput';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';

export default function CurrencyInputRHF({
  name,
  control,
  label,
  required = false,
  placeholder = '0.00',
  currencyCode,
  disabled = false,
  className,
  inputClassName,
  helperText,
  id,
  ...inputProps
}) {
  const inputId = id || name;

  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          {label && (
            <Label htmlFor={inputId}>
              {label}
              {required && <span className="text-destructive ml-1">*</span>}
            </Label>
          )}
          <CurrencyInput
            id={inputId}
            value={field.value ?? ''}
            onChange={(value) => field.onChange(value)}
            onBlur={field.onBlur}
            placeholder={placeholder}
            currencyCode={currencyCode}
            disabled={disabled}
            className={cn(
              error && 'border-destructive',
              disabled && 'bg-muted cursor-not-allowed opacity-70',
              inputClassName
            )}
            {...inputProps}
          />
          {error && <p className="text-sm text-destructive">{error.message}</p>}
          {helperText && !error && <p className="text-xs text-muted-foreground">{helperText}</p>}
        </div>
      )}
    />
  );
}
