import { cn } from '@/Utils/utils';

import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

/**
 * Reusable form field component with label, input, and error handling
 * @param {object} props
 * @param {string} props.name - Field name (used for id and htmlFor)
 * @param {string} props.label - Label text
 * @param {string} props.type - Input type (default: 'text')
 * @param {string|number} props.value - Input value
 * @param {function} props.onChange - Change handler
 * @param {string} props.error - Error message to display
 * @param {boolean} props.required - Whether field is required
 * @param {string} props.autoComplete - Autocomplete attribute
 * @param {string} props.placeholder - Placeholder text
 * @param {string} props.className - Additional CSS classes for wrapper
 * @param {string} props.inputClassName - Additional CSS classes for input
 * @param {React.Ref} props.inputRef - Ref for the input element
 * @param {object} props.inputProps - Additional props to pass to Input component
 */
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
