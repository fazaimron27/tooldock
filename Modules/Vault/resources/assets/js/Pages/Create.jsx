/**
 * Create vault page with form for creating new vault items
 * Includes type switcher, password generator, and conditional fields
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import VaultFormFields from '@Vault/Components/VaultFormFields';
import { createVaultResolver } from '@Vault/Schemas/vaultSchemas';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

import FormCard from '@/Components/Common/FormCard';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Create({ categories = [], types = [] }) {
  const form = useInertiaForm(
    {
      name: '',
      type: 'login',
      username: '',
      email: '',
      issuer: '',
      value: '',
      totp_secret: '',
      fields: {},
      url: '',
      category_id: '',
      is_favorite: false,
    },
    {
      resolver: createVaultResolver,
      toast: {
        success: 'Vault item created successfully!',
        error: 'Failed to create vault item. Please check the form for errors.',
      },
    }
  );

  const [isGeneratingPassword, setIsGeneratingPassword] = useState(false);
  const [qrScannerOpen, setQrScannerOpen] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('vault.store'));
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
      <PageShell title="Create Vault Item">
        <div className="space-y-6">
          <FormCard
            title="New Vault Item"
            description="Create a new vault item"
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
              />

              <div className="flex items-center justify-end gap-4">
                <Link href={route('vault.index')}>
                  <Button type="button" variant="outline">
                    Cancel
                  </Button>
                </Link>
                <Button type="submit" disabled={form.formState.isSubmitting}>
                  {form.formState.isSubmitting ? 'Creating...' : 'Create Vault Item'}
                </Button>
              </div>
            </form>
          </FormCard>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
