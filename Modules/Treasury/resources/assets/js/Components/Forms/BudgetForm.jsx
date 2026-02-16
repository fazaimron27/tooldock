/**
 * Shared Budget Form component - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Supports Template + Instance model for monthly budgets
 *
 * Note: Budget 'name' field removed - category name serves as the identifier
 */
import CurrencyInputRHF from '@Treasury/Components/FormFields/CurrencyInputRHF';
import SearchableSelectRHF from '@Treasury/Components/FormFields/SearchableSelectRHF';
import SwitchCardRHF from '@Treasury/Components/FormFields/SwitchCardRHF';
import { Link, usePage } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { useMemo } from 'react';

import FormCard from '@/Components/Common/FormCard';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import { Button } from '@/Components/ui/button';

/**
 * Get default form values for budget template
 * @param {Object|null} budget - Existing budget for edit mode
 * @param {Object|null} budgetPeriod - Existing period for period edit mode
 * @returns {Object} Form default values
 */
export function getBudgetDefaults(budget = null, budgetPeriod = null) {
  if (budget) {
    const amount = budgetPeriod?.amount ?? budget.amount;
    return {
      category_id: String(budget.category_id) || '',
      amount: amount != null ? String(amount) : '',
      is_recurring: budget.is_recurring ?? true,
      rollover_enabled: budget.rollover_enabled ?? false,
      description: budgetPeriod?.description || '',
    };
  }

  return {
    category_id: '',
    amount: '',
    is_recurring: true,
    rollover_enabled: false,
    description: '',
  };
}

export default function BudgetForm({
  control,
  onSubmit,
  isSubmitting = false,
  isEdit = false,
  isPeriodEdit = false,
  categories = [],
  usedCategoryIds = [],
  cancelUrl,
  currentPeriod = null,
  currency = null,
  categoryName = null, // For displaying read-only category in edit mode
}) {
  const { currency_code } = usePage().props;
  const budgetCurrency = currency || currency_code || 'IDR';

  // Convert to Set for O(1) lookup
  const usedCategorySet = useMemo(() => new Set(usedCategoryIds.map(String)), [usedCategoryIds]);

  // Convert categories to options for SearchableSelect
  // Disable categories that already have budgets (one budget per category)
  const categoryOptions = useMemo(() => {
    return categories.map((category) => {
      const isUsed = usedCategorySet.has(String(category.id));
      return {
        value: String(category.id),
        label: category.name,
        color: category.color || '#6B7280',
        description: isUsed ? 'Already has a budget' : category.description,
        parentValue: category.parent_id ? String(category.parent_id) : undefined,
        disabled: isUsed,
      };
    });
  }, [categories, usedCategorySet]);

  return (
    <FormCard
      title={
        isPeriodEdit
          ? `Edit Budget for ${currentPeriod?.label || 'This Month'}`
          : isEdit
            ? `Edit Budget: ${categoryName || 'Budget'}`
            : 'New Budget'
      }
      description={
        isPeriodEdit
          ? 'Adjust the budget limit for this specific month only'
          : isEdit
            ? 'Update your recurring spending limit'
            : 'Set a monthly spending limit for a category'
      }
      className="max-w-2xl"
    >
      <form onSubmit={onSubmit} className="space-y-6" noValidate>
        {/* Category - Only show for new budgets (read-only info shown in title for edit) */}
        {!isEdit && (
          <SearchableSelectRHF
            name="category_id"
            control={control}
            label="Category"
            required
            options={categoryOptions}
            placeholder="Select a category"
            searchPlaceholder="Search categories..."
            showColors
            hierarchical
          />
        )}

        {/* Budget Amount */}
        <div className="space-y-2">
          <CurrencyInputRHF
            name="amount"
            control={control}
            label={
              <>
                {isPeriodEdit ? 'Budget Amount for This Month' : 'Monthly Budget Amount'}
                <span className="text-xs font-normal text-muted-foreground ml-2">
                  ({budgetCurrency})
                </span>
              </>
            }
            required
            placeholder="Enter budget amount"
            currencyCode={budgetCurrency}
          />
          {isPeriodEdit && (
            <p className="text-xs text-muted-foreground flex items-center gap-1">
              <Info className="w-3 h-3" />
              This only affects {currentPeriod?.label}. Future months will use the template amount.
            </p>
          )}
        </div>

        {/* Template Options - Only show for template edit or create */}
        {!isPeriodEdit && (
          <>
            <SwitchCardRHF
              name="is_recurring"
              control={control}
              label="Recurring Budget"
              description="Automatically create this budget each month"
            />

            <SwitchCardRHF
              name="rollover_enabled"
              control={control}
              label="Enable Rollover"
              description="Carry unused budget to the next month"
            />
          </>
        )}

        {/* Description - Only for period edit */}
        {isPeriodEdit && (
          <FormTextareaRHF
            name="description"
            control={control}
            label="Description (Optional)"
            placeholder="e.g., Holiday month - extra spending"
            rows={2}
          />
        )}

        {/* Actions */}
        <div className="flex items-center justify-end gap-4">
          <Link href={cancelUrl || route('treasury.budgets.index')}>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </Link>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? isEdit
                ? 'Saving...'
                : 'Creating...'
              : isPeriodEdit
                ? 'Save for This Month'
                : isEdit
                  ? 'Save Budget'
                  : 'Create Budget'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
