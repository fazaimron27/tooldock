/**
 * Create Goal page
 * Uses shared GoalForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import GoalForm, { getGoalDefaults } from '@Treasury/Components/Forms/GoalForm';
import { createGoalResolver } from '@Treasury/Schemas/goalSchemas';
import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';

import PageShell from '@/Components/Layouts/PageShell';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';

export default function Create({ wallets = [], categories = [], hasSavingsWallet = true }) {
  const form = useInertiaForm(getGoalDefaults(), {
    resolver: createGoalResolver,
    toast: {
      success: 'Goal created successfully!',
      error: 'Failed to create goal. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('treasury.goals.store'));
  };

  return (
    <PageShell title="Create Goal">
      {!hasSavingsWallet ? (
        <Alert variant="destructive" className="max-w-2xl">
          <AlertTriangle className="h-4 w-4" />
          <AlertTitle>Savings Wallet Required</AlertTitle>
          <AlertDescription className="mt-2">
            <p className="mb-4">
              You need to create a savings wallet before you can set up goals. Goals are linked to
              savings wallets to track your progress.
            </p>
            <Link href={route('treasury.wallets.create')}>
              <Button variant="outline" size="sm">
                Create Savings Wallet <ArrowRight className="ml-2 h-4 w-4" />
              </Button>
            </Link>
          </AlertDescription>
        </Alert>
      ) : (
        <GoalForm
          control={form.control}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          wallets={wallets}
          categories={categories}
          watch={form.watch}
          setValue={form.setValue}
        />
      )}
    </PageShell>
  );
}
