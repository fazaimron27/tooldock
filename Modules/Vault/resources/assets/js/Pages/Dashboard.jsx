/**
 * Vault module dashboard page
 * Displays widgets for vault statistics and metrics
 */
import { useVaultLockGuard } from '@Vault/Hooks/useVaultLockGuard';
import { usePage } from '@inertiajs/react';

import ModuleDashboardPage from '@/Components/Dashboard/ModuleDashboardPage';

export default function Dashboard({ widgets = [], moduleMetadata = {} }) {
  const { vault_lock_settings } = usePage().props;

  useVaultLockGuard(vault_lock_settings?.enabled);

  return (
    <ModuleDashboardPage moduleName="Vault" widgets={widgets} moduleMetadata={moduleMetadata} />
  );
}
