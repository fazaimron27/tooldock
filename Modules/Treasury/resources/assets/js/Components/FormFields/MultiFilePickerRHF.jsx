/**
 * MultiFilePicker field component for React Hook Form
 * Wraps MultiFilePicker with RHF Controller for form integration
 */
import { Controller } from 'react-hook-form';

import MultiFilePicker from '@/Components/Form/MultiFilePicker';

export default function MultiFilePickerRHF({
  name,
  control,
  existingFiles = [],
  onRemoveExisting,
  accept,
  directory,
  maxFiles = 5,
  className,
  id,
  ...pickerProps
}) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <>
          <MultiFilePicker
            id={id || name}
            value={field.value ?? []}
            onChange={(ids) => field.onChange(ids)}
            existingFiles={existingFiles}
            onRemoveExisting={onRemoveExisting}
            accept={accept}
            directory={directory}
            maxFiles={maxFiles}
            className={className}
            {...pickerProps}
          />
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </>
      )}
    />
  );
}
