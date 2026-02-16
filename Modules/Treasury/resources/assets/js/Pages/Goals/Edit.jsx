/**
 * Edit Goal page
 * Uses shared GoalForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import GoalForm, { getGoalDefaults } from '@Treasury/Components/Forms/GoalForm';
import { updateGoalResolver } from '@Treasury/Schemas/goalSchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Edit({ goal, wallets = [], categories = [] }) {
  const form = useInertiaForm(getGoalDefaults(goal), {
    resolver: updateGoalResolver,
    toast: {
      success: 'Goal updated successfully!',
      error: 'Failed to update goal. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('treasury.goals.update', goal.id));
  };

  return (
    <PageShell title="Edit Goal">
      <GoalForm
        control={form.control}
        onSubmit={handleSubmit}
        isSubmitting={form.formState.isSubmitting}
        isEdit
        wallets={wallets}
        categories={categories}
        currency={goal.currency}
        watch={form.watch}
        setValue={form.setValue}
      />
    </PageShell>
  );
}
