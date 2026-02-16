/**
 * Edit Budget page
 * Supports editing both template and period-specific amounts
 * Uses shared BudgetForm component with useInertiaForm
 *
 * Note: Budget 'name' removed - category name serves as the identifier
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import BudgetForm, { getBudgetDefaults } from '@Treasury/Components/Forms/BudgetForm';
import { getBudgetResolver } from '@Treasury/Schemas/budgetSchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Edit({
  budget,
  budgetPeriod = null,
  categories = [],
  currentPeriod = null,
  isEditingPeriod = false,
}) {
  const isPeriodEdit =
    isEditingPeriod ||
    (!!currentPeriod?.month && !!currentPeriod?.year && currentPeriod?.isExplicit);

  const categoryName = budget.category?.name || 'Budget';

  const form = useInertiaForm(
    {
      ...getBudgetDefaults(budget, budgetPeriod),
      update_type: isPeriodEdit ? 'period' : 'template',
      period: currentPeriod?.period || null,
    },
    {
      resolver: getBudgetResolver(isPeriodEdit),
      toast: {
        success: isPeriodEdit
          ? `Budget for ${currentPeriod?.label || 'this month'} updated!`
          : 'Budget updated successfully!',
        error: 'Failed to update budget. Please check the form for errors.',
      },
    }
  );

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('treasury.budgets.update', budget.id));
  };

  const pageTitle = isPeriodEdit
    ? `Edit "${categoryName}" for ${currentPeriod?.label || 'This Month'}`
    : `Edit Budget: ${categoryName}`;

  return (
    <PageShell title={pageTitle}>
      {/* Show budget info header for period edit */}
      {isPeriodEdit && (
        <div className="mb-6 p-4 bg-muted/50 rounded-lg border">
          <div className="flex items-center gap-3">
            <div
              className="w-4 h-4 rounded-full ring-2 ring-offset-2 ring-offset-background"
              style={{
                backgroundColor: budget.category?.color || '#6b7280',
              }}
            />
            <div>
              <p className="font-semibold">{categoryName}</p>
              <p className="text-sm text-muted-foreground">{currentPeriod?.label}</p>
            </div>
          </div>
        </div>
      )}

      <BudgetForm
        control={form.control}
        onSubmit={handleSubmit}
        isSubmitting={form.formState.isSubmitting}
        isEdit
        isPeriodEdit={isPeriodEdit}
        categories={categories}
        currentPeriod={currentPeriod}
        currency={budget.currency}
        categoryName={categoryName}
      />
    </PageShell>
  );
}
