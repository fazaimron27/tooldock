/**
 * Form field component combining label, input, and error display
 * Provides consistent styling and error handling for form inputs
 */
import { cn } from '@/Utils/utils';

import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function FormField({
  name,
  label,
  type = 'text',
  value,
  onChange,
  error,
  required = false,
  autoComplete,
  placeholder,
  className,
  inputClassName,
  inputRef,
  ...inputProps
}) {
  return (
    <div className={cn('space-y-2', className)}>
      <Label htmlFor={name}>{label}</Label>
      <Input
        id={name}
        ref={inputRef}
        type={type}
        value={value}
        onChange={onChange}
        required={required}
        autoComplete={autoComplete}
        placeholder={placeholder}
        className={cn(error && 'border-destructive', inputClassName)}
        {...inputProps}
      />
      {error && <p className="text-sm text-destructive">{error}</p>}
    </div>
  );
}
