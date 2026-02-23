/**
 * useAutoSave Hook
 *
 * Subscribes to tldraw store changes and auto-saves the document state
 * to the backend via a debounced mutation using React Query.
 * Exposes save status for UI via the mutation's built-in state.
 */
import { useMutation } from '@tanstack/react-query';
import { useCallback, useEffect, useRef, useState } from 'react';
import { getSnapshot } from 'tldraw';

/**
 * Hook that auto-saves tldraw editor state to the backend.
 *
 * @param {import('tldraw').Editor|null} editor - The tldraw editor instance
 * @param {string} quickdrawId - The canvas ID for the sync endpoint
 * @param {number} [debounceMs=1500] - Debounce delay in milliseconds
 * @returns {{ saveStatus: string }} The current save status
 */
export function useAutoSave(editor, quickdrawId, debounceMs = 1500) {
  const timeoutRef = useRef(null);
  const [isDirty, setIsDirty] = useState(false);

  const mutation = useMutation({
    mutationFn: async (documentState) => {
      const response = await window.axios.post(route('quickdraw.sync', quickdrawId), {
        document_state: documentState,
      });
      return response.data;
    },
    onError: (err) => {
      console.error('[QuickDraw] Auto-save failed:', err);
    },
  });

  const save = useCallback(() => {
    if (!editor) return;

    const { document } = getSnapshot(editor.store);
    setIsDirty(false);
    mutation.mutate({ document });
  }, [editor, mutation, quickdrawId]); // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => {
    if (!editor) return;

    const unlisten = editor.store.listen(
      () => {
        setIsDirty(true);
        if (timeoutRef.current) {
          window.clearTimeout(timeoutRef.current);
        }
        timeoutRef.current = window.setTimeout(save, debounceMs);
      },
      { source: 'user', scope: 'document' }
    );

    return () => {
      unlisten();
      if (timeoutRef.current) {
        window.clearTimeout(timeoutRef.current);
      }
    };
  }, [editor, save, debounceMs]);

  useEffect(() => {
    if (!editor) return;

    const handleBeforeUnload = () => {
      const snapshot = getSnapshot(editor.store);
      const url = route('quickdraw.sync', quickdrawId);
      const token = window.document.querySelector('meta[name="csrf-token"]')?.content;
      const body = JSON.stringify({
        _token: token,
        document_state: { document: snapshot.document },
      });

      window.navigator.sendBeacon(url, new window.Blob([body], { type: 'application/json' }));
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [editor, quickdrawId]);

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
