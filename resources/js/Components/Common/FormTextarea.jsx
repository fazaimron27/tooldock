/**
 * Form textarea component combining label, textarea, and error display
 * Provides consistent styling and error handling for textarea inputs
 */
import { cn } from '@/Utils/utils';

import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

export default function FormTextarea({
  name,
  label,
  value,
  onChange,
  error,
  required = false,
  placeholder,
  rows = 10,
  className,
  textareaClassName,
  textareaRef,
  ...textareaProps
}) {
  return (
    <div className={cn('space-y-2', className)}>
      <Label htmlFor={name}>{label}</Label>
      <Textarea
        id={name}
        ref={textareaRef}
        value={value}
        onChange={onChange}
        required={required}
        placeholder={placeholder}
        rows={rows}
        className={cn(error && 'border-destructive', textareaClassName)}
        {...textareaProps}
      />
      {error && <p className="text-sm text-destructive">{error}</p>}
    </div>
  );
}
