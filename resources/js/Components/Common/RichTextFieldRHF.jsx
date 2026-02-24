/**
 * Rich Text Field for React Hook Form
 *
 * Wraps RichTextEditor with Controller for seamless react-hook-form integration.
 * Follows the same pattern as FormTextareaRHF.
 */
import { cn } from '@/Utils/utils';
import { Controller } from 'react-hook-form';

import RichTextEditor from '@/Components/Common/RichTextEditor';
import { Label } from '@/Components/ui/label';

export default function RichTextFieldRHF({
  name,
  control,
  label,
  required = false,
  placeholder,
  className,
  editorClassName,
  rules,
  id,
}) {
  const fieldId = id || name;

  return (
    <Controller
      name={name}
      control={control}
      rules={rules}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          {label && (
            <Label htmlFor={fieldId}>
              {label}
              {required && <span className="text-destructive ml-1">*</span>}
            </Label>
          )}
          <RichTextEditor
            value={field.value ?? ''}
            onChange={field.onChange}
            placeholder={placeholder}
            editorClassName={editorClassName}
            className={cn(error && 'border-destructive')}
          />
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}
