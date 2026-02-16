/**
 * Vault Select field component for React Hook Form
 * Wraps shadcn Select with RHF Controller for form integration
 */
import { cn } from '@/Utils/utils';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

export default function VaultSelectRHF({
  name,
  control,
  label,
  required = false,
  options = [],
  placeholder = 'Select...',
  disabled = false,
  className,
}) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          {label && (
            <Label htmlFor={name}>
              {label} {required && <span className="text-destructive">*</span>}
            </Label>
          )}
          <Select
            value={field.value || undefined}
            onValueChange={(value) => field.onChange(value || '')}
            disabled={disabled}
          >
            <SelectTrigger id={name} className={cn(error && 'border-destructive')}>
              <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
              {options.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}
