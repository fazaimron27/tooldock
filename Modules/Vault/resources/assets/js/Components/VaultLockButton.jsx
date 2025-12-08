/**
 * Button to manually lock the vault
 * Only shows when vault lock is enabled
 */
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Lock } from 'lucide-react';

import { Button } from '@/Components/ui/button';

export default function VaultLockButton() {
  const { props } = usePage();
  const vaultLockSettings = props.vault_lock_settings || {};

  if (!vaultLockSettings.enabled) {
    return null;
  }

  const handleLock = () => {
    router.post(route('vault.lock.store'));
  };

  return (
    <Button variant="outline" size="sm" onClick={handleLock}>
      <Lock className="mr-2 h-4 w-4" />
      Lock Vault
    </Button>
  );
}
