/**
 * DatePicker field component for React Hook Form
 * Wraps DatePicker with RHF Controller for form integration
 */
import { Controller } from 'react-hook-form';

import DatePicker from '@/Components/Form/DatePicker';

export default function DatePickerRHF({
  name,
  control,
  label,
  required = false,
  placeholder = 'Pick a date',
  className,
  id,
  ...pickerProps
}) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <DatePicker
          id={id || name}
          value={field.value ?? ''}
          onChange={(date) => field.onChange(date)}
          label={
            label ? (
              <>
                {label}
                {required && <span className="text-destructive ml-1">*</span>}
              </>
            ) : undefined
          }
          error={error?.message}
          placeholder={placeholder}
          className={className}
          {...pickerProps}
        />
      )}
    />
  );
}
