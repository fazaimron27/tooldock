/**
 * Shared input component for Vault forms
 * Provides consistent styling for text inputs used throughout vault forms
 */
import { cn } from '@/Utils/utils';
import { forwardRef } from 'react';

const VaultFormInput = forwardRef(({ className, error, ...props }, ref) => {
  return (
    <input
      ref={ref}
      className={cn(
        'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
        error && 'border-destructive',
        className
      )}
      {...props}
    />
  );
});

VaultFormInput.displayName = 'VaultFormInput';

export default VaultFormInput;
