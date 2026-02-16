/**
 * Switch Card field component for React Hook Form
 * A bordered card with switch toggle, label, and description
 */
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

export default function SwitchCardRHF({ name, control, label, description, id, ...switchProps }) {
  const inputId = id || name;

  return (
    <Controller
      name={name}
      control={control}
      render={({ field }) => (
        <div className="flex items-center justify-between rounded-lg border p-4">
          <div className="space-y-0.5">
            <Label htmlFor={inputId} className="text-base cursor-pointer">
              {label}
            </Label>
            {description && <p className="text-sm text-muted-foreground">{description}</p>}
          </div>
          <Switch
            id={inputId}
            checked={field.value ?? false}
            onCheckedChange={field.onChange}
            {...switchProps}
          />
        </div>
      )}
    />
  );
}
