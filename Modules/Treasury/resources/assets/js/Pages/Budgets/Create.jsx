/**
 * Create Budget page
 * Uses shared BudgetForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import BudgetForm, { getBudgetDefaults } from '@Treasury/Components/Forms/BudgetForm';
import { createBudgetResolver } from '@Treasury/Schemas/budgetSchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Create({ categories = [], usedCategoryIds = [] }) {
  const form = useInertiaForm(getBudgetDefaults(), {
    resolver: createBudgetResolver,
    toast: {
      success: 'Budget created successfully!',
      error: 'Failed to create budget. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('treasury.budgets.store'));
  };

  return (
    <PageShell title="Create Budget">
      <BudgetForm
        control={form.control}
        onSubmit={handleSubmit}
        isSubmitting={form.formState.isSubmitting}
        categories={categories}
        usedCategoryIds={usedCategoryIds}
      />
    </PageShell>
  );
}
