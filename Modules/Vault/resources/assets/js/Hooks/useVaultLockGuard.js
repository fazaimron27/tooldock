/**
 * Hook that guards vault pages by monitoring lock status.
 * Automatically redirects users to the lock screen when their session times out.
 *
 * Uses React Query for polling with automatic pause when tab is hidden.
 *
 * @param {boolean} enabled - Whether vault lock is enabled for the user. If false, no monitoring occurs.
 */
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef } from 'react';

import { useVaultLockStatus } from './useVaultQueries';

export function useVaultLockGuard(enabled = true) {
  const hasRedirectedRef = useRef(false);

  // React Query handles polling, pausing when tab is hidden, and cleanup
  const { data, refetch } = useVaultLockStatus({
    enabled,
    // Handle lock detection
    onSuccess: (data) => {
      if (!data.unlocked && !hasRedirectedRef.current) {
        hasRedirectedRef.current = true;
        router.visit(route('vault.lock'), {
          preserveState: false,
        });
      }
    },
  });

  // Reset redirect flag when data shows unlocked (user unlocked vault)
  useEffect(() => {
    if (data?.unlocked) {
      hasRedirectedRef.current = false;
    }
  }, [data?.unlocked]);

  // Manual check function for imperative use
  const checkLockStatus = useCallback(() => {
    refetch();
  }, [refetch]);

  return { checkLockStatus };
}
