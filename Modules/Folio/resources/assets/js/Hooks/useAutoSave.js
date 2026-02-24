/**
 * useAutoSave Hook
 *
 * Subscribes to react-hook-form watch changes and auto-saves the resume
 * content to the backend via a debounced mutation using React Query.
 * Exposes save status for UI via the mutation's built-in state.
 *
 * Follows the same pattern as QuickDraw's useAutoSave hook.
 *
 * NOTE: Unlike QuickDraw which uses tldraw's editor.store.listen()
 * (only fires on user actions), react-hook-form's watch(callback)
 * fires immediately on subscription. Therefore all non-stable values
 * are accessed via refs to keep the subscription effect stable.
 */
import { useMutation } from '@tanstack/react-query';
import { useCallback, useEffect, useRef, useState } from 'react';

const DEBOUNCE_MS = 1500;

/**
 * Hook that auto-saves Folio resume content to the backend.
 *
 * @param {string} folioId - The resume UUID
 * @param {Function} watchFn - react-hook-form watch function
 * @param {boolean} formIsDirty - whether the form has unsaved changes
 * @returns {{ saveStatus: 'idle' | 'unsaved' | 'saving' | 'saved' | 'error' }}
 */
export default function useAutoSave(folioId, watchFn, formIsDirty) {
  const timerRef = useRef(null);
  const [isDirty, setIsDirty] = useState(false);

  const formIsDirtyRef = useRef(formIsDirty);
  formIsDirtyRef.current = formIsDirty;

  const watchFnRef = useRef(watchFn);
  watchFnRef.current = watchFn;

  const mutation = useMutation({
    mutationFn: async (content) => {
      const response = await window.axios.put(route('folio.update', folioId), {
        content,
      });
      return response.data;
    },
    onError: (err) => {
      console.error('[Folio] Auto-save failed:', err);
    },
  });

  const mutateRef = useRef(mutation.mutate);
  mutateRef.current = mutation.mutate;

  const save = useCallback(() => {
    const content = watchFnRef.current();
    setIsDirty(false);
    mutateRef.current(content);
  }, []);

  useEffect(() => {
    const subscription = watchFnRef.current(() => {
      if (!formIsDirtyRef.current) return;

      setIsDirty(true);

      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }

      timerRef.current = setTimeout(save, DEBOUNCE_MS);
    });

    return () => {
      subscription.unsubscribe();
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, [save]);

  useEffect(() => {
    const handleBeforeUnload = () => {
      if (!isDirty && !mutation.isPending) return;

      const url = route('folio.update', folioId);
      const token = document.querySelector('meta[name="csrf-token"]')?.content;
      const body = JSON.stringify({
        _token: token,
        _method: 'PUT',
        content: watchFnRef.current(),
      });

      navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [folioId, isDirty, mutation.isPending]);

  let saveStatus = 'idle';
  if (mutation.isPending) {
    saveStatus = 'saving';
  } else if (isDirty) {
    saveStatus = 'unsaved';
  } else if (mutation.isError) {
    saveStatus = 'error';
  } else if (mutation.isSuccess) {
    saveStatus = 'saved';
  }

  return { saveStatus };
}
