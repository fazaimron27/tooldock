/**
 * Shared Goal Form component - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Includes smart Financial Tips for Emergency & Security categories
 */
import { cn } from '@/Utils/utils';
import CurrencyInputRHF from '@Treasury/Components/FormFields/CurrencyInputRHF';
import DatePickerRHF from '@Treasury/Components/FormFields/DatePickerRHF';
import SearchableSelectRHF from '@Treasury/Components/FormFields/SearchableSelectRHF';
import SwitchRHF from '@Treasury/Components/FormFields/SwitchRHF';
import FinancialTipModal from '@Treasury/Components/Modals/FinancialTipModal';
import { Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import { Lightbulb } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

/**
 * Category slugs that support financial tips (Emergency & Security)
 */
const FINANCIAL_TIP_SLUGS = ['shield-check', 'shield-plus', 'briefcase'];

/**
 * Get default form values for goal
 * @param {Object|null} goal - Existing goal for edit mode
 * @returns {Object} Form default values
 */
export function getGoalDefaults(goal = null) {
  if (goal) {
    return {
      wallet_id: goal.wallet_id || '',
      category_id: goal.category_id || '',
      name: goal.name || '',
      target_amount: goal.target_amount != null ? String(goal.target_amount) : '',
      deadline: goal.deadline || '',
      description: goal.description || '',
      is_completed: goal.is_completed ?? false,
    };
  }

  return {
    wallet_id: '',
    category_id: '',
    name: '',
    target_amount: '',
    deadline: '',
    description: '',
  };
}

export default function GoalForm({
  control,
  onSubmit,
  isSubmitting = false,
  isEdit = false,
  cancelUrl,
  wallets = [],
  categories = [],
  currency = null,
  watch,
  setValue,
}) {
  const { currency_code } = usePage().props;

  const [tipModalOpen, setTipModalOpen] = useState(false);
  const [recommendation, setRecommendation] = useState(null);
  const [isLoadingTip, setIsLoadingTip] = useState(false);
  const walletId = watch?.('wallet_id');
  const categoryId = watch?.('category_id');

  const selectedWallet = useMemo(() => {
    return wallets.find((w) => String(w.id) === String(walletId));
  }, [wallets, walletId]);

  const goalCurrency = useMemo(() => {
    if (selectedWallet?.currency) {
      return selectedWallet.currency;
    }
    return currency || currency_code || 'IDR';
  }, [selectedWallet, currency, currency_code]);

  const currencyMismatchWarning = useMemo(() => {
    if (!isEdit || !currency || !selectedWallet?.currency) {
      return null;
    }
    if (selectedWallet.currency !== currency) {
      return `Changing wallet will update goal currency from ${currency} to ${selectedWallet.currency}. Existing amounts will be displayed in the new currency.`;
    }
    return null;
  }, [isEdit, currency, selectedWallet]);

  const walletOptions = useMemo(() => {
    return wallets.map((wallet) => ({
      value: wallet.id,
      label: `${wallet.name} (${wallet.currency})`,
      color: wallet.color || '#6B7280',
    }));
  }, [wallets]);

  const categoryOptions = useMemo(() => {
    return categories.map((category) => ({
      value: category.id,
      label: category.name,
      color: category.color || '#6B7280',
      description: category.description,
      slug: category.slug,
      parentValue: category.parent_id ? String(category.parent_id) : undefined,
    }));
  }, [categories]);

  const selectedCategory = useMemo(() => {
    return categories.find((c) => String(c.id) === String(categoryId));
  }, [categories, categoryId]);
  const supportsFinancialTip = useMemo(() => {
    if (!selectedCategory?.slug) return false;
    return FINANCIAL_TIP_SLUGS.includes(selectedCategory.slug);
  }, [selectedCategory]);

  const fetchRecommendation = useCallback(async (categorySlug, walletCurrency) => {
    if (!categorySlug) return;

    setIsLoadingTip(true);
    try {
      const response = await axios.get('/tooldock/treasury/financial-health/goal-recommendation', {
        params: {
          category_slug: categorySlug,
          wallet_currency: walletCurrency || null,
        },
      });

      if (response.data.has_data) {
        setRecommendation(response.data.data);
        setTipModalOpen(true);
      } else {
        setRecommendation(null);
      }
    } catch (error) {
      console.error('Failed to fetch financial tip:', error);
      setRecommendation(null);
    } finally {
      setIsLoadingTip(false);
    }
  }, []);

  useEffect(() => {
    if (supportsFinancialTip && selectedCategory?.slug && !isEdit) {
      fetchRecommendation(selectedCategory.slug, goalCurrency);
    }
  }, [selectedCategory?.slug, supportsFinancialTip, fetchRecommendation, isEdit, goalCurrency]);

  const handleApplyRecommendation = useCallback(
    ({ amount, targetDate }) => {
      setValue?.('target_amount', String(amount), { shouldValidate: true });
      if (targetDate) {
        setValue?.('deadline', targetDate, { shouldValidate: true });
      }
    },
    [setValue]
  );

  const handleTipButtonClick = useCallback(() => {
    if (selectedCategory?.slug) {
      fetchRecommendation(selectedCategory.slug, goalCurrency);
    }
  }, [selectedCategory?.slug, fetchRecommendation, goalCurrency]);

  return (
    <FormCard
      title={isEdit ? 'Edit Savings Goal' : 'New Savings Goal'}
      description={
        isEdit ? 'Update your financial goal' : 'Set a financial goal to track your progress'
      }
      className="max-w-2xl"
    >
      <form onSubmit={onSubmit} className="space-y-6" noValidate>
        {/* Goal Name */}
        <FormFieldRHF
          name="name"
          control={control}
          label="Goal Name"
          required
          placeholder="e.g., Emergency Fund, Vacation, New Car"
        />

        {/* Wallet & Category */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            {wallets.length === 0 ? (
              <>
                <div className="flex items-center h-6">
                  <Label>
                    Savings Wallet <span className="text-destructive">*</span>
                  </Label>
                </div>
                <div className="p-3 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 rounded-md">
                  <p className="text-sm text-amber-700 dark:text-amber-300">
                    No savings wallets available
                  </p>
                </div>
              </>
            ) : (
              <SearchableSelectRHF
                name="wallet_id"
                control={control}
                label="Savings Wallet"
                required
                options={walletOptions}
                placeholder="Select a savings wallet"
                searchPlaceholder="Search wallets..."
                showColors
              />
            )}
            <p className="text-xs text-muted-foreground">
              Goal progress will be tracked in this savings wallet
            </p>
            {currencyMismatchWarning && (
              <p className="text-sm text-amber-600 dark:text-amber-400">
                ⚠️ {currencyMismatchWarning}
              </p>
            )}
          </div>

          <div className="space-y-2">
            <div className="flex items-center justify-between h-6">
              <Label htmlFor="category_id">Category</Label>
              {supportsFinancialTip && (
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-6 w-6"
                        onClick={handleTipButtonClick}
                        disabled={isLoadingTip}
                      >
                        <Lightbulb
                          className={cn(
                            'h-4 w-4',
                            isLoadingTip ? 'text-muted-foreground animate-pulse' : 'text-amber-500'
                          )}
                        />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Get financial planning tips</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              )}
            </div>
            <SearchableSelectRHF
              name="category_id"
              control={control}
              options={[
                { value: 'none', label: 'No category', color: '#9ca3af' },
                ...categoryOptions,
              ]}
              placeholder="Select a category"
              searchPlaceholder="Search categories..."
              showColors
              hierarchical
            />
            <p className="text-xs text-muted-foreground">
              Category provides color and icon for this goal
            </p>
          </div>
        </div>

        {/* Target Amount & Deadline */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <CurrencyInputRHF
            name="target_amount"
            control={control}
            label={
              <>
                Target Amount
                <span className="text-xs font-normal text-muted-foreground ml-2">
                  ({goalCurrency})
                </span>
              </>
            }
            required
            placeholder="10.000.000"
            currencyCode={goalCurrency}
          />

          <DatePickerRHF
            name="deadline"
            control={control}
            label="Target Date"
            placeholder="Pick a deadline"
          />
        </div>

        {/* Description */}
        <FormTextareaRHF
          name="description"
          control={control}
          label="Description"
          placeholder="Describe your goal and motivation"
          rows={3}
        />

        {/* Status (Edit only) */}
        {isEdit && (
          <SwitchRHF
            name="is_completed"
            control={control}
            label="Status"
            description={(value) => (value ? 'Completed' : 'In Progress')}
          />
        )}

        {/* Actions */}
        <div className="flex items-center justify-end gap-4">
          <Link href={cancelUrl || route('treasury.goals.index')}>
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
                : 'Create Goal'}
          </Button>
        </div>
      </form>

      {/* Financial Tip Modal */}
      <FinancialTipModal
        open={tipModalOpen}
        onOpenChange={setTipModalOpen}
        data={recommendation}
        onApply={handleApplyRecommendation}
      />
    </FormCard>
  );
}
