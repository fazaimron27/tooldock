import { Controller } from 'react-hook-form';

import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

/**
 * Reusable number input field with unit label, wired to react-hook-form.
 */
export default function NumberField({ label, name, control, min, max, step = 1, unit }) {
  return (
    <div>
      <Label className="text-xs">{label}</Label>
      <div className="relative mt-1">
        <Controller
          name={name}
          control={control}
          render={({ field }) => (
            <Input
              type="number"
              step={step}
              min={min}
              max={max}
              className="text-sm pr-8"
              {...field}
              onChange={(e) => field.onChange(parseFloat(e.target.value))}
            />
          )}
        />
        <span className="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-muted-foreground pointer-events-none">
          {unit}
        </span>
      </div>
    </div>
  );
}
