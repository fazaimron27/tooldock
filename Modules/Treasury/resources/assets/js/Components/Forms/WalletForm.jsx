/**
 * Shared Wallet Form component - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Currency can be set per-wallet, with reference currency as default
 * Wallet types are fetched from Categories system
 */
import CurrencyInputRHF from '@Treasury/Components/FormFields/CurrencyInputRHF';
import CurrencySelectRHF from '@Treasury/Components/FormFields/CurrencySelectRHF';
import SearchableSelectRHF from '@Treasury/Components/FormFields/SearchableSelectRHF';
import SwitchRHF from '@Treasury/Components/FormFields/SwitchRHF';
import { Link } from '@inertiajs/react';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import { Button } from '@/Components/ui/button';

/**
 * Get default form values for wallet
 * @param {Object|null} wallet - Existing wallet for edit mode
 * @param {Array} walletTypes - Available wallet types from Categories
 * @param {string} referenceCurrency - Default currency code
 * @returns {Object} Form default values
 */
export function getWalletDefaults(wallet = null, walletTypes = [], referenceCurrency = 'IDR') {
  const defaultType = walletTypes.length > 0 ? walletTypes[0].slug : '';

  if (wallet) {
    return {
      name: wallet.name || '',
      type: wallet.type || defaultType,
      currency: wallet.currency || referenceCurrency,
      balance: wallet.balance != null ? String(wallet.balance) : '',
      description: wallet.description || '',
      is_active: wallet.is_active ?? true,
    };
  }

  return {
    name: '',
    type: defaultType,
    currency: referenceCurrency,
    balance: '',
    description: '',
  };
}

export default function WalletForm({
  control,
  onSubmit,
  isSubmitting = false,
  isEdit = false,
  walletTypes = [],
  cancelUrl,
  watch,
}) {
  // Watch currency for CurrencyInput
  const currency = watch?.('currency');

  return (
    <FormCard
      title={isEdit ? 'Edit Wallet' : 'New Wallet'}
      description={
        isEdit ? 'Update your wallet details' : 'Add a new wallet to track your finances'
      }
      className="max-w-2xl"
    >
      <form onSubmit={onSubmit} className="space-y-6" noValidate>
        {/* Wallet Name */}
        <FormFieldRHF
          name="name"
          control={control}
          label="Wallet Name"
          required
          placeholder="e.g., Main Bank Account"
        />

        {/* Type */}
        {walletTypes.length === 0 ? (
          <div className="space-y-2">
            <p className="text-sm font-medium">
              Type <span className="text-destructive">*</span>
            </p>
            <div className="p-3 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 rounded-md">
              <p className="text-sm text-amber-700 dark:text-amber-300">
                No wallet types configured. Please contact administrator to set up wallet type
                categories.
              </p>
            </div>
          </div>
        ) : (
          <SearchableSelectRHF
            name="type"
            control={control}
            label="Type"
            required
            options={walletTypes.map((type) => ({
              value: type.slug,
              label: type.name,
              color: type.color,
              description: type.description,
            }))}
            placeholder="Select wallet type"
            searchPlaceholder="Search wallet types..."
            showColors
          />
        )}

        {/* Currency */}
        <CurrencySelectRHF
          name="currency"
          control={control}
          label="Currency"
          required
          helperText="Currency for this wallet. All amounts will be recorded in this currency."
        />

        {/* Balance Field */}
        <CurrencyInputRHF
          name="balance"
          control={control}
          label={isEdit ? 'Current Balance' : 'Initial Balance'}
          placeholder="0.00"
          currencyCode={currency}
          disabled={isEdit}
          helperText={!isEdit ? "This will be your wallet's starting balance." : undefined}
        />

        {/* Description */}
        <FormTextareaRHF
          name="description"
          control={control}
          label="Description"
          placeholder="Optional notes about this wallet"
          rows={3}
        />

        {/* Status (Edit only) */}
        {isEdit && (
          <SwitchRHF
            name="is_active"
            control={control}
            label="Status"
            description={(value) => (value ? 'Active' : 'Inactive')}
          />
        )}

        {/* Actions */}
        <div className="flex items-center justify-end gap-4">
          <Link href={cancelUrl || route('treasury.wallets.index')}>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </Link>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? isEdit
                ? 'Saving...'
                : 'Creating...'
              : isEdit
                ? 'Save Changes'
                : 'Create Wallet'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
