/**
 * Form textarea component for React Hook Form
 * Provides consistent styling and error handling for react-hook-form textareas
 */
import { cn } from '@/Utils/utils';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

export default function FormTextareaRHF({
  name,
  control,
  label,
  required = false,
  placeholder,
  rows = 10,
  className,
  textareaClassName,
  rules,
  id,
  ...textareaProps
}) {
  /**
   * Use provided id or fall back to name for accessibility.
   * Ensures proper label-textarea association.
   */
  const textareaId = id || name;

  return (
    <Controller
      name={name}
      control={control}
      rules={rules}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          <Label htmlFor={textareaId}>
            {label}
            {required && <span className="text-destructive ml-1">*</span>}
          </Label>
          <Textarea
            id={textareaId}
            {...field}
            value={field.value ?? ''}
            required={required}
            placeholder={placeholder}
            rows={rows}
            className={cn(error && 'border-destructive', textareaClassName)}
            {...textareaProps}
          />
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}
