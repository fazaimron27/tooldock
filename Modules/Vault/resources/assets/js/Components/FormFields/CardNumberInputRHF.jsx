/**
 * Card Number Input field component for React Hook Form
 * Features card type auto-detection and formatting
 */
import { cn } from '@/Utils/utils';
import CardTypeLogo from '@Vault/Components/CardTypeLogo';
import VaultFormInput from '@Vault/Components/VaultFormInput';
import { detectCardType } from '@Vault/Utils/cardTypeDetector';
import { formatCardNumber } from '@Vault/Utils/maskUtils';
import { Controller } from 'react-hook-form';

import { Label } from '@/Components/ui/label';

export default function CardNumberInputRHF({
  name,
  control,
  label = 'Card Number',
  onCardTypeChange,
  className,
}) {
  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => {
        const detectedType = detectCardType(field.value);

        return (
          <div className={cn('space-y-2', className)}>
            {label && <Label htmlFor={name}>{label}</Label>}
            <div className="relative">
              <VaultFormInput
                id={name}
                type="text"
                {...field}
                value={field.value || ''}
                onChange={(e) => {
                  const formatted = formatCardNumber(e.target.value);
                  field.onChange(formatted);

                  // Notify parent of card type change if callback provided
                  if (onCardTypeChange) {
                    const detected = detectCardType(formatted);
                    onCardTypeChange(detected);
                  }
                }}
                placeholder="1234 5678 9012 3456"
                maxLength={23}
                error={!!error}
                className={cn(detectedType && 'pr-12')}
              />
              {detectedType && (
                <div className="absolute right-3 top-1/2 -translate-y-1/2 flex items-center pointer-events-none">
                  <CardTypeLogo cardType={detectedType} size={28} />
                </div>
              )}
            </div>
            {error && <p className="text-sm text-destructive">{error.message}</p>}
          </div>
        );
      }}
    />
  );
}
