/**
 * useFolioUpdate Hook
 *
 * Reusable mutation hook for eagerly saving Folio resume content.
 * Used for immediate saves (e.g. template switch) that should not
 * wait for the debounced auto-save cycle.
 *
 * @module Hooks/useFolioUpdate
 */
import { useApiMutation } from '@/Hooks/queries/useApiMutation';

/**
 * Hook for immediate (non-debounced) Folio content saves.
 *
 * @param {string} folioId - The resume UUID
 * @param {Object} [options] - Additional mutation options
 * @returns {import('@tanstack/react-query').UseMutationResult}
 *
 * @example
 * const folioUpdate = useFolioUpdate(folio.id);
 *
 * const setThemeId = (id) => {
 *   setValue('template', id, { shouldDirty: true });
 *   folioUpdate.mutate({ content: { ...watch(), template: id } });
 * };
 */
export default function useFolioUpdate(folioId, options = {}) {
  return useApiMutation(() => route('folio.update', folioId), 'put', {
    onError: (err) => {
      console.error('[Folio] Save failed:', err);
    },
    ...options,
  });
}
