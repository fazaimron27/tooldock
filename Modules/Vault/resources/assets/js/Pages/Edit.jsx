/**
 * Edit vault page with form for updating existing vault items
 * Includes type switcher, password generator, and conditional fields
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import VaultFormFields from '@Vault/Components/VaultFormFields';
import { updateVaultResolver } from '@Vault/Schemas/vaultSchemas';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

import FormCard from '@/Components/Common/FormCard';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Edit({ vault, categories = [], types = [] }) {
  const form = useInertiaForm(
    {
      name: vault.name || '',
      type: vault.type || 'login',
      username: vault.username || '',
      email: vault.email || '',
      issuer: vault.issuer || '',
      value: vault.value || '',
      totp_secret: vault.totp_secret || '',
      fields: vault.fields && Object.keys(vault.fields).length > 0 ? vault.fields : {},
      url: vault.url || '',
      category_id: vault.category_id || '',
      is_favorite: vault.is_favorite || false,
    },
    {
      resolver: updateVaultResolver,
      toast: {
        success: 'Vault item updated successfully!',
        error: 'Failed to update vault item. Please check the form for errors.',
      },
    }
  );

  const [isGeneratingPassword, setIsGeneratingPassword] = useState(false);
  const [qrScannerOpen, setQrScannerOpen] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('vault.update', { vault: vault.id }));
  };

  const handleGeneratePassword = useCallback(async () => {
    setIsGeneratingPassword(true);
    try {
      const response = await axios.post(route('vault.generate-password'));
      form.setValue('value', response.data.password);
      toast.success('Password generated');
    } catch (_error) {
      toast.error('Failed to generate password');
    } finally {
      setIsGeneratingPassword(false);
    }
  }, [form]);

  return (
    <DashboardLayout header="Vault">
      <PageShell title="Edit Vault Item">
        <div className="space-y-6">
          <FormCard
            title="Edit Vault Item"
            description="Update vault item information"
            className="max-w-3xl"
          >
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <VaultFormFields
                form={form}
                categories={categories}
                types={types}
                isGeneratingPassword={isGeneratingPassword}
                onGeneratePassword={handleGeneratePassword}
                qrScannerOpen={qrScannerOpen}
                onQrScannerOpenChange={setQrScannerOpen}
                isEditMode={true}
              />

              <div className="flex items-center justify-end gap-4">
                <Link href={route('vault.index')}>
                  <Button type="button" variant="outline">
                    Cancel
                  </Button>
                </Link>
                <Button type="submit" disabled={form.formState.isSubmitting}>
                  {form.formState.isSubmitting ? 'Updating...' : 'Update Vault Item'}
                </Button>
              </div>
            </form>
          </FormCard>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
