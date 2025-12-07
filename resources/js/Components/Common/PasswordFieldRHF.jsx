/**
 * Password field component for React Hook Form with reveal password functionality
 * Provides consistent styling, error handling, and password visibility toggle
 */
import { cn } from '@/Utils/utils';
import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import { Controller } from 'react-hook-form';

import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function PasswordFieldRHF({
  name,
  control,
  label,
  required = false,
  autoComplete,
  placeholder,
  className,
  inputClassName,
  rules,
  id,
  ...inputProps
}) {
  const [showPassword, setShowPassword] = useState(false);
  const inputId = id || name;

  return (
    <Controller
      name={name}
      control={control}
      rules={rules}
      render={({ field, fieldState: { error } }) => (
        <div className={cn('space-y-2', className)}>
          <Label htmlFor={inputId}>
            {label}
            {required && <span className="text-destructive ml-1">*</span>}
          </Label>
          <div className="relative">
            <Input
              id={inputId}
              type={showPassword ? 'text' : 'password'}
              {...field}
              required={required}
              autoComplete={autoComplete}
              placeholder={placeholder}
              className={cn(error && 'border-destructive', 'pr-10', inputClassName)}
              {...inputProps}
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
              aria-label={showPassword ? 'Hide password' : 'Show password'}
            >
              {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}
