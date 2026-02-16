/**
 * Edit Wallet page
 * Uses shared WalletForm component with useInertiaForm
 * Wallet types are fetched from Categories system
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import WalletForm, { getWalletDefaults } from '@Treasury/Components/Forms/WalletForm';
import { updateWalletResolver } from '@Treasury/Schemas/walletSchemas';
import { usePage } from '@inertiajs/react';

import PageShell from '@/Components/Layouts/PageShell';

export default function Edit({ wallet, walletTypes = [] }) {
  const { currency_code: referenceCurrency = 'IDR' } = usePage().props;

  const form = useInertiaForm(getWalletDefaults(wallet, walletTypes, referenceCurrency), {
    resolver: updateWalletResolver,
    toast: {
      success: 'Wallet updated successfully!',
      error: 'Failed to update wallet. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('treasury.wallets.update', wallet.id));
  };

  return (
    <PageShell title="Edit Wallet">
      <WalletForm
        control={form.control}
        onSubmit={handleSubmit}
        isSubmitting={form.formState.isSubmitting}
        isEdit
        walletTypes={walletTypes}
        watch={form.watch}
      />
    </PageShell>
  );
}
