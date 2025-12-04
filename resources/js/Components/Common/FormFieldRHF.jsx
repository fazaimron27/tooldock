/**
 * Form field component for React Hook Form
 * Provides consistent styling and error handling for react-hook-form inputs
 */
import { cn } from '@/Utils/utils';
import { Controller } from 'react-hook-form';

import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function FormFieldRHF({
  name,
  control,
  label,
  type = 'text',
  required = false,
  autoComplete,
  placeholder,
  className,
  inputClassName,
  rules,
  id,
  ...inputProps
}) {
  /**
   * Use provided id or fall back to name for accessibility.
   * Ensures proper label-input association.
   */
  const inputId = id || name;

  return (
    <Controller
      name={name}
      control={control}
      rules={rules}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          <Label htmlFor={inputId}>
            {label}
            {required && <span className="text-destructive ml-1">*</span>}
          </Label>
          <Input
            id={inputId}
            type={type}
            {...field}
            required={required}
            autoComplete={autoComplete}
            placeholder={placeholder}
            className={cn(error && 'border-destructive', inputClassName)}
            {...inputProps}
          />
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}
