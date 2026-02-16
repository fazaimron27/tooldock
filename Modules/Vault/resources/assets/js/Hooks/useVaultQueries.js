/**
 * Vault Query Hooks using TanStack Query
 *
 * React Query hooks for vault-related data fetching.
 * Provides caching, automatic refetching, and cleaner polling logic.
 *
 * @module Vault/Hooks/useVaultQueries
 */
import { useApiQuery } from '@/Hooks/queries/useApiQuery';

/**
 * Query key factory for vault
 */
export const vaultKeys = {
  all: ['vault'],
  lockStatus: () => [...vaultKeys.all, 'lock-status'],
  totp: (vaultId) => [...vaultKeys.all, 'totp', vaultId],
};

/**
 * Check vault lock status with polling
 *
 * Used to detect when vault session times out and redirect to lock screen.
 * Automatically pauses polling when tab is hidden (saves battery/bandwidth).
 *
 * @param {Object} options - Additional query options
 * @param {boolean} options.enabled - Whether to enable polling (default: true)
 * @returns {Object} Query result with lock status
 *
 * @example
 * const { data } = useVaultLockStatus({
 *   enabled: vaultLockEnabled,
 *   onSuccess: (data) => {
 *     if (!data.unlocked) router.visit(route('vault.lock'));
 *   }
 * });
 */
export function useVaultLockStatus(options = {}) {
  return useApiQuery(vaultKeys.lockStatus(), route('vault.lock.status'), {
    refetchInterval: 10 * 1000, // 10 seconds
    refetchIntervalInBackground: false, // Pause when tab hidden
    refetchOnWindowFocus: true,
    staleTime: 5 * 1000, // 5 seconds
    retry: false, // Don't retry on failure (network issues shouldn't lock user out)
    ...options,
  });
}

/**
 * Fetch TOTP code for a vault item
 *
 * TOTP codes are generated server-side to protect the secret.
 * Automatically refreshes every 5 seconds to stay current.
 *
 * @param {string|number} vaultId - The vault item ID
 * @param {Object} options - Additional query options
 * @returns {Object} Query result with TOTP code
 *
 * @example
 * const { data } = useTOTPCode(vault.id, {
 *   enabled: !!vault.totp_secret,
 * });
 * // data.code = "123456"
 */
export function useTOTPCode(vaultId, options = {}) {
  return useApiQuery(
    vaultKeys.totp(vaultId),
    () => route('vault.generate-totp', { vault: vaultId }),
    {
      refetchInterval: 5 * 1000, // 5 seconds (TOTP period is 30s, fetch 6x per period)
      refetchIntervalInBackground: false,
      staleTime: 4 * 1000, // 4 seconds
      enabled: !!vaultId,
      retry: 1,
      ...options,
    }
  );
}
