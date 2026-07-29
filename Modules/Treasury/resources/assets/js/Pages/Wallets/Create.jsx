/**
 * Create Wallet page
 * Uses shared WalletForm component with useInertiaForm
 * Wallet types are fetched from Categories system
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import WalletForm, { getWalletDefaults } from '@Treasury/Components/Forms/WalletForm';
import { createWalletResolver } from '@Treasury/Schemas/walletSchemas';
import { usePage } from '@inertiajs/react';

import PageShell from '@/Components/Layouts/PageShell';

export default function Create({ walletTypes = [] }) {
  const { currency_code: referenceCurrency = 'IDR' } = usePage().props;

  const form = useInertiaForm(getWalletDefaults(null, walletTypes, referenceCurrency), {
    resolver: createWalletResolver,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('treasury.wallets.store'));
  };

  return (
    <PageShell title="Create Wallet">
      <WalletForm
        control={form.control}
        onSubmit={handleSubmit}
        isSubmitting={form.formState.isSubmitting}
        walletTypes={walletTypes}
        watch={form.watch}
      />
    </PageShell>
  );
}
