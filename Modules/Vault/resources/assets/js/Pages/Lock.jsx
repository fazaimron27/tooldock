/**
 * Vault lock screen - requires PIN to unlock vault
 * Shows PIN setup form if no lock exists, or unlock form if lock exists
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { Lock as LockIcon } from 'lucide-react';
import { useEffect, useRef } from 'react';

import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Lock({ hasLock = false }) {
  const pinInputRef = useRef(null);

  const setupForm = useInertiaForm(
    {
      pin: '',
      pin_confirmation: '',
    },
    {
      toast: {
        success: 'Vault PIN set successfully!',
        error: 'Failed to set PIN. Please check the form for errors.',
      },
    }
  );

  const unlockForm = useInertiaForm(
    {
      pin: '',
    },
    {
      toast: {
        error: 'Incorrect PIN. Please try again.',
      },
    }
  );

  useEffect(() => {
    pinInputRef.current?.focus();
  }, []);

  const handleSetupSubmit = (e) => {
    e.preventDefault();
    setupForm.post(route('vault.pin.set'));
  };

  const handleUnlockSubmit = (e) => {
    e.preventDefault();
    unlockForm.post(route('vault.unlock'), {
      preserveScroll: true,
    });
  };

  if (!hasLock) {
    return (
      <DashboardLayout header="Vault">
        <PageShell title="Set Vault PIN">
          <div className="mx-auto max-w-md">
            <div className="rounded-lg border bg-card p-8 text-card-foreground shadow-sm">
              <div className="mb-6 flex justify-center">
                <div className="rounded-full bg-muted p-4">
                  <LockIcon className="h-8 w-8 text-muted-foreground" />
                </div>
              </div>
              <h2 className="mb-2 text-center text-2xl font-semibold">Set Vault PIN</h2>
              <p className="mb-6 text-center text-sm text-muted-foreground">
                Create a PIN to secure your vault. You'll need this PIN to access your vault items.
              </p>
              <form onSubmit={handleSetupSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="pin">PIN (4-20 characters)</Label>
                  <Input
                    ref={pinInputRef}
                    id="pin"
                    type="password"
                    maxLength={20}
                    {...setupForm.register('pin')}
                    placeholder="Enter PIN"
                    autoFocus
                    required
                  />
                  {setupForm.errors.pin && (
                    <p className="text-sm text-destructive">{setupForm.errors.pin}</p>
                  )}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="pin_confirmation">Confirm PIN</Label>
                  <Input
                    id="pin_confirmation"
                    type="password"
                    maxLength={20}
                    {...setupForm.register('pin_confirmation')}
                    placeholder="Confirm PIN"
                    required
                  />
                  {setupForm.errors.pin_confirmation && (
                    <p className="text-sm text-destructive">{setupForm.errors.pin_confirmation}</p>
                  )}
                </div>
                <Button
                  type="submit"
                  className="w-full"
                  disabled={setupForm.formState.isSubmitting}
                >
                  {setupForm.formState.isSubmitting ? 'Setting PIN...' : 'Set PIN'}
                </Button>
              </form>
            </div>
          </div>
        </PageShell>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout header="Vault">
      <PageShell title="Vault Locked">
        <div className="mx-auto max-w-md">
          <div className="rounded-lg border bg-card p-8 text-card-foreground shadow-sm">
            <div className="mb-6 flex justify-center">
              <div className="rounded-full bg-muted p-4">
                <LockIcon className="h-8 w-8 text-muted-foreground" />
              </div>
            </div>
            <h2 className="mb-2 text-center text-2xl font-semibold">Vault Locked</h2>
            <p className="mb-6 text-center text-sm text-muted-foreground">
              Enter your PIN to unlock the vault
            </p>
            <form onSubmit={handleUnlockSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="pin">PIN</Label>
                <Input
                  ref={pinInputRef}
                  id="pin"
                  type="password"
                  maxLength={20}
                  {...unlockForm.register('pin')}
                  placeholder="Enter PIN"
                  autoFocus
                  required
                />
                {unlockForm.errors.pin && (
                  <p className="text-sm text-destructive">{unlockForm.errors.pin}</p>
                )}
              </div>
              <Button type="submit" className="w-full" disabled={unlockForm.formState.isSubmitting}>
                {unlockForm.formState.isSubmitting ? 'Unlocking...' : 'Unlock Vault'}
              </Button>
            </form>
          </div>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
