/**
 * Searchable Select field component for React Hook Form
 * Wraps SearchableSelect with RHF Controller for form integration
 */
import { cn } from '@/Utils/utils';
import SearchableSelect from '@Treasury/Components/SearchableSelect';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';

export default function SearchableSelectRHF({
  name,
  control,
  label,
  required = false,
  options = [],
  placeholder,
  searchPlaceholder,
  showColors = false,
  emptyMessage,
  className,
  id,
  ...selectProps
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
          <SearchableSelect
            value={field.value ?? ''}
            onValueChange={(value) => field.onChange(value)}
            options={options}
            placeholder={placeholder}
            searchPlaceholder={searchPlaceholder}
            showColors={showColors}
            emptyMessage={emptyMessage}
            error={error?.message}
            {...selectProps}
          />
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}
