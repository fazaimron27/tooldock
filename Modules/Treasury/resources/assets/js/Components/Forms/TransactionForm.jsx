/**
 * Enhanced Transaction Form component - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Features: Visual type selector, category color indicators, modern styling
 * Supports multi-currency wallets with per-wallet currency display
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { getLocalDateTimeString } from '@/Utils/format';
import { cn } from '@/Utils/utils';
import CurrencyInputRHF from '@Treasury/Components/FormFields/CurrencyInputRHF';
import DateTimePickerRHF from '@Treasury/Components/FormFields/DateTimePickerRHF';
import MultiFilePickerRHF from '@Treasury/Components/FormFields/MultiFilePickerRHF';
import SearchableSelectRHF from '@Treasury/Components/FormFields/SearchableSelectRHF';
import { DEFAULT_TRANSACTION_TYPE, TRANSACTION_TYPES } from '@Treasury/Constants/treasuryConstants';
import { Link } from '@inertiajs/react';
import {
  AlertTriangle,
  ArrowDownCircle,
  ArrowRight,
  ArrowRightCircle,
  ArrowUpCircle,
  Banknote,
  Calendar,
  FileText,
  Paperclip,
  Receipt,
  Tag,
  Wallet,
} from 'lucide-react';
import { useMemo } from 'react';
import { Controller } from 'react-hook-form';

import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';

const typeConfig = {
  expense: {
    icon: ArrowDownCircle,
    label: 'Expense',
    color: 'rose',
    description: 'Money going out',
    bgClass: 'bg-rose-500/10 border-rose-500/30',
    activeClass: 'bg-rose-500 text-white border-rose-500',
    iconClass: 'text-rose-500',
  },
  income: {
    icon: ArrowUpCircle,
    label: 'Income',
    color: 'emerald',
    description: 'Money coming in',
    bgClass: 'bg-emerald-500/10 border-emerald-500/30',
    activeClass: 'bg-emerald-500 text-white border-emerald-500',
    iconClass: 'text-emerald-500',
  },
  transfer: {
    icon: ArrowRightCircle,
    label: 'Transfer',
    color: 'blue',
    description: 'Between wallets',
    bgClass: 'bg-blue-500/10 border-blue-500/30',
    activeClass: 'bg-blue-500 text-white border-blue-500',
    iconClass: 'text-blue-500',
  },
};

/**
 * Get default form values for transaction
 */
export function getTransactionDefaults(transaction = null) {
  if (transaction) {
    return {
      wallet_id: String(transaction.wallet_id) || '',
      destination_wallet_id: transaction.destination_wallet_id
        ? String(transaction.destination_wallet_id)
        : '',
      category_id: transaction.category_id ? String(transaction.category_id) : '',
      type: transaction.type || DEFAULT_TRANSACTION_TYPE,
      name: transaction.name || '',
      amount: transaction.amount != null ? String(transaction.amount) : '',
      fee: transaction.fee != null ? String(transaction.fee) : '0',
      description: transaction.description || '',
      date: transaction.date || getLocalDateTimeString(),
      attachment_ids: [],
      remove_attachment_ids: [],
    };
  }

  return {
    wallet_id: '',
    destination_wallet_id: '',
    category_id: '',
    type: DEFAULT_TRANSACTION_TYPE,
    name: '',
    amount: '',
    fee: '0',
    description: '',
    date: getLocalDateTimeString(),
    attachment_ids: [],
    remove_attachment_ids: [],
  };
}

function TypeSelectorRHF({ control, types }) {
  const availableTypes = types.length > 0 ? types : Object.keys(typeConfig);

  return (
    <Controller
      name="type"
      control={control}
      render={({ field, fieldState: { error } }) => (
        <div className="space-y-3">
          <Label className="text-sm font-medium">Transaction Type</Label>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {availableTypes.map((type) => {
              const config = typeConfig[type];
              if (!config) return null;

              const Icon = config.icon;
              const isActive = field.value === type;

              return (
                <button
                  key={type}
                  type="button"
                  onClick={() => field.onChange(type)}
                  className={cn(
                    'relative flex sm:flex-col items-center gap-3 sm:gap-2 p-3 sm:p-4 rounded-xl border-2 transition-all duration-200',
                    'hover:scale-[1.02] active:scale-[0.98]',
                    isActive
                      ? config.activeClass
                      : 'border-border bg-card hover:border-muted-foreground/30'
                  )}
                >
                  <Icon
                    className={cn('w-6 h-6 shrink-0', isActive ? 'text-white' : config.iconClass)}
                  />
                  <div className="text-left sm:text-center min-w-0 flex-1">
                    <p
                      className={cn(
                        'font-bold sm:font-medium text-sm',
                        !isActive && 'text-foreground'
                      )}
                    >
                      {config.label}
                    </p>
                    <p
                      className={cn(
                        'text-[10px] truncate',
                        isActive ? 'text-white/80' : 'text-muted-foreground'
                      )}
                    >
                      {config.description}
                    </p>
                  </div>
                  {isActive && (
                    <div className="absolute top-2 right-2 w-2 h-2 rounded-full bg-white animate-pulse" />
                  )}
                </button>
              );
            })}
          </div>
          {error && <p className="text-sm text-destructive">{error.message}</p>}
        </div>
      )}
    />
  );
}

export default function TransactionForm({
  control,
  onSubmit,
  isSubmitting = false,
  isEdit = false,
  transaction = null,
  wallets = [],
  categories = [],
  types = [],
  existingAttachments = [],
  cancelUrl,
  exchangeRates = {},
  referenceCurrency = 'IDR',
  watch,
  setValue,
}) {
  const { formatCurrency } = useAppearance();

  const formType = watch?.('type') || 'expense';
  const walletId = watch?.('wallet_id') || '';
  const destWalletId = watch?.('destination_wallet_id') || '';
  const amount = watch?.('amount') || '';
  const fee = watch?.('fee') || '0';

  const currentTypeConfig = typeConfig[formType] || typeConfig.expense;

  const sourceWallet = useMemo(() => {
    return wallets.find((w) => String(w.id) === String(walletId));
  }, [wallets, walletId]);

  const sourceWalletCurrency = sourceWallet?.currency || referenceCurrency;

  const availableSourceWallets = useMemo(() => {
    if (formType === 'expense') {
      return wallets.filter((w) => w.type !== 'savings');
    }
    return wallets;
  }, [wallets, formType]);

  const destinationWallet = useMemo(() => {
    return wallets.find((w) => String(w.id) === String(destWalletId));
  }, [wallets, destWalletId]);

  const destinationWalletCurrency = destinationWallet?.currency || referenceCurrency;

  const isCrossCurrencyTransfer = useMemo(() => {
    return (
      formType === 'transfer' &&
      sourceWallet &&
      destinationWallet &&
      sourceWalletCurrency !== destinationWalletCurrency
    );
  }, [formType, sourceWallet, destinationWallet, sourceWalletCurrency, destinationWalletCurrency]);

  const exchangeRateInfo = useMemo(() => {
    if (!isCrossCurrencyTransfer) return null;

    const sourceRate = exchangeRates[sourceWalletCurrency];
    const destRate = exchangeRates[destinationWalletCurrency];

    if (!sourceRate || !destRate) {
      return { available: false, rate: null };
    }

    const rate = destRate / sourceRate;
    const amountNum = parseFloat(amount) || 0;
    const convertedAmount = amountNum * rate;

    return {
      available: true,
      rate,
      convertedAmount,
      sourceCurrency: sourceWalletCurrency,
      destCurrency: destinationWalletCurrency,
    };
  }, [
    isCrossCurrencyTransfer,
    exchangeRates,
    sourceWalletCurrency,
    destinationWalletCurrency,
    amount,
  ]);

  const balanceInfo = useMemo(() => {
    if (formType === 'income') return null;
    if (!sourceWallet?.balance) return null;

    const amountNum = parseFloat(amount || 0);
    const feeNum = parseFloat(fee || 0);
    const totalDeduction = amountNum + feeNum;
    let availableBalance = parseFloat(sourceWallet.balance);

    if (isEdit && transaction) {
      const originalAmount = parseFloat(transaction.amount || 0);
      const originalFee = parseFloat(transaction.fee || 0);
      const originalTotal = originalAmount + originalFee;
      const originalType = transaction.type;
      const originalWalletId = String(transaction.wallet_id);
      const currentWalletId = String(walletId);

      if (originalWalletId === currentWalletId) {
        if (originalType === 'expense' || originalType === 'transfer') {
          availableBalance += originalTotal;
        } else if (originalType === 'income') {
          availableBalance -= originalTotal;
        }
      }
    }

    const isOverBalance = totalDeduction > availableBalance;
    const remainingBalance = availableBalance - totalDeduction;

    return {
      availableBalance,
      totalDeduction,
      isOverBalance,
      remainingBalance,
      currency: sourceWalletCurrency,
    };
  }, [
    formType,
    amount,
    fee,
    walletId,
    sourceWallet?.balance,
    sourceWalletCurrency,
    isEdit,
    transaction,
  ]);

  const sourceWalletOptions = useMemo(() => {
    return availableSourceWallets.map((wallet) => ({
      value: String(wallet.id),
      label: `${wallet.name}${wallet.currency ? ` (${wallet.currency})` : ''}`,
      color: wallet.color,
      disabled: String(wallet.id) === destWalletId,
    }));
  }, [availableSourceWallets, destWalletId]);

  const destinationWalletOptions = useMemo(() => {
    return wallets.map((wallet) => ({
      value: String(wallet.id),
      label: `${wallet.name}${wallet.currency ? ` (${wallet.currency})` : ''}`,
      color: wallet.color,
      disabled: String(wallet.id) === walletId,
    }));
  }, [wallets, walletId]);

  const categoryOptions = useMemo(() => {
    return categories.map((category) => ({
      value: String(category.id),
      label: category.name,
      color: category.color || '#6b7280',
      description: category.description,
      parentValue: category.parent_id ? String(category.parent_id) : undefined,
    }));
  }, [categories]);

  return (
    <Card className="max-w-2xl border-0 shadow-xl overflow-hidden">
      {/* Header with gradient based on type */}
      <div
        className={cn(
          'px-6 py-5 transition-colors duration-300',
          formType === 'income' && 'bg-gradient-to-r from-emerald-500/10 to-transparent',
          formType === 'expense' && 'bg-gradient-to-r from-rose-500/10 to-transparent',
          formType === 'transfer' && 'bg-gradient-to-r from-blue-500/10 to-transparent'
        )}
      >
        <div className="flex items-center gap-3">
          <div
            className={cn(
              'w-10 h-10 rounded-xl flex items-center justify-center transition-colors',
              currentTypeConfig.bgClass
            )}
          >
            {(() => {
              const Icon = currentTypeConfig.icon;
              return <Icon className={cn('w-5 h-5', currentTypeConfig.iconClass)} />;
            })()}
          </div>
          <div>
            <h2 className="text-lg font-semibold">
              {isEdit ? 'Edit Transaction' : 'New Transaction'}
            </h2>
            <p className="text-sm text-muted-foreground">
              {isEdit ? 'Update transaction details' : 'Record a new income or expense'}
            </p>
          </div>
        </div>
      </div>

      <CardContent className="p-6 space-y-6">
        <form onSubmit={onSubmit} className="space-y-6" noValidate>
          {/* Transaction Type Selector */}
          <TypeSelectorRHF control={control} types={types} />

          {/* Wallet Selection */}
          {formType === 'transfer' ? (
            <div className="space-y-3">
              <Label className="text-sm font-medium flex items-center gap-2">
                <ArrowRight className="w-4 h-4 text-blue-500" />
                Transfer Between Wallets <span className="text-destructive">*</span>
              </Label>
              <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                <div className="flex-1">
                  <SearchableSelectRHF
                    name="wallet_id"
                    control={control}
                    options={sourceWalletOptions}
                    placeholder="From wallet"
                    searchPlaceholder="Search wallets..."
                    showColors
                  />
                </div>

                <div className="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-blue-500/10 flex items-center justify-center self-center rotate-90 sm:rotate-0">
                  <ArrowRight className="w-4 h-4 sm:w-5 sm:h-5 text-blue-500" />
                </div>

                <div className="flex-1">
                  <SearchableSelectRHF
                    name="destination_wallet_id"
                    control={control}
                    options={destinationWalletOptions}
                    placeholder="To wallet"
                    searchPlaceholder="Search wallets..."
                    showColors
                  />
                </div>
              </div>

              {isCrossCurrencyTransfer && (
                <Alert className="mt-3 border-blue-500/30 bg-blue-500/10">
                  <AlertTriangle className="h-4 w-4 text-blue-500" />
                  <AlertDescription className="text-sm">
                    {exchangeRateInfo?.available ? (
                      <>
                        <strong>Cross-currency transfer:</strong>{' '}
                        {parseFloat(amount || 0).toLocaleString()} {sourceWalletCurrency} ≈{' '}
                        {exchangeRateInfo.convertedAmount.toLocaleString(undefined, {
                          maximumFractionDigits: 2,
                        })}{' '}
                        {destinationWalletCurrency}
                        <span className="text-xs text-muted-foreground ml-2">
                          (Rate: 1 {sourceWalletCurrency} = {exchangeRateInfo.rate.toFixed(4)}{' '}
                          {destinationWalletCurrency})
                        </span>
                      </>
                    ) : (
                      <>
                        <strong>Cross-currency transfer:</strong> Exchange rate not available for{' '}
                        {sourceWalletCurrency} → {destinationWalletCurrency}. Please refresh
                        exchange rates.
                      </>
                    )}
                  </AlertDescription>
                </Alert>
              )}
            </div>
          ) : (
            <div className="space-y-2">
              <Label className="flex items-center gap-2">
                <Wallet className="w-4 h-4 text-muted-foreground" />
                Wallet <span className="text-destructive">*</span>
              </Label>
              <SearchableSelectRHF
                name="wallet_id"
                control={control}
                options={sourceWalletOptions}
                placeholder="Select wallet"
                searchPlaceholder="Search wallets..."
                showColors
              />
            </div>
          )}

          {/* Amount */}
          <div className="space-y-3">
            <CurrencyInputRHF
              name="amount"
              control={control}
              label={
                <span className="flex items-center gap-2">
                  <Banknote className="w-4 h-4 text-muted-foreground" />
                  Amount
                </span>
              }
              required
              placeholder="0"
              currencyCode={sourceWalletCurrency}
              inputClassName={cn(
                'h-14 text-2xl font-bold',
                formType === 'income' && 'focus-visible:ring-emerald-500',
                formType === 'expense' && 'focus-visible:ring-rose-500',
                formType === 'transfer' && 'focus-visible:ring-blue-500'
              )}
            />
          </div>

          {/* Fee */}
          <CurrencyInputRHF
            name="fee"
            control={control}
            label={
              <span className="flex items-center gap-2">
                <Receipt className="w-4 h-4 text-muted-foreground" />
                Fee
              </span>
            }
            placeholder="0"
            currencyCode={sourceWalletCurrency}
            helperText={
              formType === 'income'
                ? 'Fee will be subtracted from income (e.g., tax, service charge)'
                : formType === 'transfer'
                  ? 'Fee will be added to amount deducted from source wallet'
                  : 'Fee will be added to expense (e.g., processing fee)'
            }
          />

          {/* Balance Warning */}
          {balanceInfo && (
            <Alert
              className={cn(
                'border',
                balanceInfo.isOverBalance
                  ? 'border-amber-500/30 bg-amber-500/10'
                  : 'border-muted bg-muted/30'
              )}
            >
              {balanceInfo.isOverBalance ? (
                <AlertTriangle className="h-4 w-4 text-amber-500" />
              ) : (
                <Wallet className="h-4 w-4 text-muted-foreground" />
              )}
              <AlertDescription className="text-sm">
                {balanceInfo.isOverBalance ? (
                  <>
                    <strong className="text-amber-600 dark:text-amber-400">
                      Insufficient balance:
                    </strong>{' '}
                    Total deduction{' '}
                    <strong>
                      {formatCurrency(balanceInfo.totalDeduction, balanceInfo.currency)}
                    </strong>{' '}
                    exceeds available balance of{' '}
                    <strong>
                      {formatCurrency(balanceInfo.availableBalance, balanceInfo.currency)}
                    </strong>
                  </>
                ) : (
                  <>
                    <span className="text-muted-foreground">Available balance:</span>{' '}
                    <strong>
                      {formatCurrency(balanceInfo.availableBalance, balanceInfo.currency)}
                    </strong>
                    {balanceInfo.totalDeduction > 0 && (
                      <>
                        {' → '}
                        <span className="text-muted-foreground">After transaction:</span>{' '}
                        <strong
                          className={cn(
                            balanceInfo.remainingBalance < 0 ? 'text-rose-500' : 'text-emerald-500'
                          )}
                        >
                          {formatCurrency(balanceInfo.remainingBalance, balanceInfo.currency)}
                        </strong>
                      </>
                    )}
                  </>
                )}
              </AlertDescription>
            </Alert>
          )}

          {/* Transaction Name */}
          <FormFieldRHF
            name="name"
            control={control}
            label={
              <span className="flex items-center gap-2">
                <Tag className="w-4 h-4 text-muted-foreground" />
                Transaction Name
              </span>
            }
            placeholder={
              formType === 'income'
                ? 'e.g., Salary, Freelance Payment'
                : 'e.g., Grocery Shopping, Electric Bill'
            }
            inputClassName="h-11"
          />

          {/* Date */}
          <div className="space-y-2">
            <Label className="flex items-center gap-1.5 text-sm font-medium">
              <Calendar className="w-4 h-4 text-muted-foreground" />
              Date & Time <span className="text-destructive">*</span>
            </Label>
            <div className="[&_button]:h-11">
              <DateTimePickerRHF name="date" control={control} placeholder="Pick date and time" />
            </div>
          </div>

          {/* Category */}
          <SearchableSelectRHF
            name="category_id"
            control={control}
            label="Category"
            options={categoryOptions}
            placeholder="Select category (optional)"
            searchPlaceholder="Search categories..."
            showColors
            hierarchical
          />

          {/* Description */}
          <FormTextareaRHF
            name="description"
            control={control}
            label={
              <span className="flex items-center gap-2">
                <FileText className="w-4 h-4 text-muted-foreground" />
                Description
              </span>
            }
            placeholder="What was this transaction for?"
            rows={3}
          />

          {/* File Attachments */}
          <div className="space-y-2">
            <Label className="flex items-center gap-2">
              <Paperclip className="w-4 h-4 text-muted-foreground" />
              Attachments
            </Label>
            <MultiFilePickerRHF
              name="attachment_ids"
              control={control}
              existingFiles={existingAttachments}
              onRemoveExisting={(id) => {
                const current = watch?.('remove_attachment_ids') || [];
                setValue?.('remove_attachment_ids', [...current, id]);
              }}
              accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
              directory="transactions"
              maxFiles={5}
            />
            <p className="text-xs text-muted-foreground">
              Attach receipts, invoices, or related documents (max 5 files)
            </p>
          </div>

          {/* Actions */}
          <div className="flex flex-col-reverse sm:flex-row items-stretch sm:items-center sm:justify-end gap-3 pt-4 border-t">
            <Link
              href={cancelUrl || route('treasury.transactions.index')}
              className="w-full sm:w-auto"
            >
              <Button type="button" variant="ghost" size="lg" className="w-full">
                Cancel
              </Button>
            </Link>
            <Button
              type="submit"
              disabled={isSubmitting || balanceInfo?.isOverBalance}
              size="lg"
              className={cn(
                'w-full sm:min-w-[140px] shadow-lg transition-shadow',
                formType === 'income' &&
                  'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-500/25',
                formType === 'expense' && 'bg-rose-500 hover:bg-rose-600 shadow-rose-500/25',
                formType === 'transfer' && 'bg-blue-500 hover:bg-blue-600 shadow-blue-500/25'
              )}
            >
              {isSubmitting
                ? isEdit
                  ? 'Saving...'
                  : 'Creating...'
                : isEdit
                  ? 'Save Changes'
                  : 'Create Transaction'}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
