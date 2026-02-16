/**
 * Create Transaction page
 * Uses shared TransactionForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import TransactionForm, {
  getTransactionDefaults,
} from '@Treasury/Components/Forms/TransactionForm';
import { createTransactionResolver } from '@Treasury/Schemas/transactionSchemas';
import { Link } from '@inertiajs/react';
import { AlertTriangle, Wallet } from 'lucide-react';

import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';

export default function Create({
  wallets = [],
  categories = [],
  goals = [],
  types = [],
  exchangeRates = {},
  referenceCurrency = 'IDR',
}) {
  const form = useInertiaForm(getTransactionDefaults(), {
    resolver: createTransactionResolver,
    toast: {
      success: 'Transaction created successfully!',
      error: 'Failed to create transaction. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('treasury.transactions.store'));
  };

  // Show empty state if no wallets exist
  if (wallets.length === 0) {
    return (
      <PageShell title="Create Transaction">
        <Card className="max-w-2xl border-0 shadow-xl overflow-hidden">
          <CardContent className="p-8">
            <div className="flex flex-col items-center justify-center text-center py-8">
              <div className="w-16 h-16 rounded-2xl bg-amber-500/10 flex items-center justify-center mb-4">
                <AlertTriangle className="w-8 h-8 text-amber-500" />
              </div>
              <h3 className="text-xl font-semibold mb-2">No Wallets Available</h3>
              <p className="text-muted-foreground mb-6 max-w-md">
                You need to create a wallet before you can add transactions. Wallets help you
                organize and track your money across different accounts.
              </p>
              <div className="flex gap-3">
                <Link href={route('treasury.transactions.index')}>
                  <Button variant="outline">Go Back</Button>
                </Link>
                <Link href={route('treasury.wallets.create')}>
                  <Button>
                    <Wallet className="w-4 h-4 mr-2" />
                    Create Your First Wallet
                  </Button>
                </Link>
              </div>
            </div>
          </CardContent>
        </Card>
      </PageShell>
    );
  }

  return (
    <PageShell title="Create Transaction">
      <TransactionForm
        control={form.control}
        onSubmit={handleSubmit}
        isSubmitting={form.formState.isSubmitting}
        wallets={wallets}
        categories={categories}
        goals={goals}
        types={types}
        exchangeRates={exchangeRates}
        referenceCurrency={referenceCurrency}
        watch={form.watch}
        setValue={form.setValue}
      />
    </PageShell>
  );
}
