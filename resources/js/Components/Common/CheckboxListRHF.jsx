/**
 * Checkbox List field component for React Hook Form
 * Renders a list of checkboxes for multi-select scenarios (e.g., roles, permissions)
 */
import { Controller } from 'react-hook-form';

import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';

export default function CheckboxListRHF({
  name,
  control,
  label,
  options = [],
  emptyMessage = 'No options available',
  className,
  getOptionId = (option) => option.id,
  getOptionLabel = (option) => option.name,
}) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => {
        const selectedValues = field.value || [];

        const handleToggle = (optionId) => {
          if (selectedValues.includes(optionId)) {
            field.onChange(selectedValues.filter((id) => id !== optionId));
          } else {
            field.onChange([...selectedValues, optionId]);
          }
        };

        return (
          <div className={`space-y-4 ${className || ''}`}>
            {label && <Label>{label}</Label>}
            <div className="space-y-3 rounded-md border p-4">
              {options.length === 0 ? (
                <p className="text-sm text-muted-foreground">{emptyMessage}</p>
              ) : (
                options.map((option) => {
                  const optionId = getOptionId(option);
                  const optionLabel = getOptionLabel(option);

                  return (
                    <div key={optionId} className="flex items-center space-x-2">
                      <Checkbox
                        id={`${name}-${optionId}`}
                        checked={selectedValues.includes(optionId)}
                        onCheckedChange={() => handleToggle(optionId)}
                      />
                      <Label
                        htmlFor={`${name}-${optionId}`}
                        className="text-sm font-normal cursor-pointer"
                      >
                        {optionLabel}
                      </Label>
                    </div>
                  );
                })
              )}
            </div>
            {error && <p className="text-sm text-destructive">{error.message}</p>}
          </div>
        );
      }}
    />
  );
}
