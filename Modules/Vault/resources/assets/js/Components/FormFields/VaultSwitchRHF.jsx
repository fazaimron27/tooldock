/**
 * Vault Switch field component for React Hook Form
 * Card-style toggle switch with label and description
 */
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

export default function VaultSwitchRHF({ name, control, label, description, className }) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field }) => (
        <div
          className={`flex items-center justify-between rounded-lg border p-4 ${className || ''}`}
        >
          <div className="space-y-0.5">
            <Label htmlFor={name}>{label}</Label>
            {description && <p className="text-xs text-muted-foreground">{description}</p>}
          </div>
          <Switch id={name} checked={field.value} onCheckedChange={field.onChange} />
        </div>
      )}
    />
  );
}
