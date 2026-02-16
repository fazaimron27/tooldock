/**
 * DateTimePicker field component for React Hook Form
 * Wraps DateTimePicker with RHF Controller for form integration
 */
import { Controller } from 'react-hook-form';

import DateTimePicker from '@/Components/Form/DateTimePicker';

export default function DateTimePickerRHF({
  name,
  control,
  placeholder = 'Pick date and time',
  className,
  id,
  ...pickerProps
}) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <DateTimePicker
          id={id || name}
          value={field.value ?? ''}
          onChange={(date) => field.onChange(date)}
          error={error?.message}
          placeholder={placeholder}
          className={className}
          {...pickerProps}
        />
      )}
    />
  );
}
