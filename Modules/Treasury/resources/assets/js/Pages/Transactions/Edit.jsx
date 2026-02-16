/**
 * Edit Transaction page
 * Uses shared TransactionForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import TransactionForm, {
  getTransactionDefaults,
} from '@Treasury/Components/Forms/TransactionForm';
import { updateTransactionResolver } from '@Treasury/Schemas/transactionSchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Edit({
  transaction,
  wallets = [],
  categories = [],
  goals = [],
  types = [],
  exchangeRates = {},
  referenceCurrency = 'IDR',
}) {
  const form = useInertiaForm(getTransactionDefaults(transaction), {
    resolver: updateTransactionResolver,
    toast: {
      success: 'Transaction updated successfully!',
      error: 'Failed to update transaction. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('treasury.transactions.update', transaction.id));
  };

  return (
    <PageShell title="Edit Transaction">
      <TransactionForm
        control={form.control}
        onSubmit={handleSubmit}
        isSubmitting={form.formState.isSubmitting}
        isEdit
        transaction={transaction}
        wallets={wallets}
        categories={categories}
        goals={goals}
        types={types}
        existingAttachments={transaction.attachments || []}
        exchangeRates={exchangeRates}
        referenceCurrency={referenceCurrency}
        watch={form.watch}
        setValue={form.setValue}
      />
    </PageShell>
  );
}
