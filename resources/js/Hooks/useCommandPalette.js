/**
 * Custom hook for managing Command Palette state and keyboard shortcuts
 *
 * Registers a global keyboard listener for Cmd+K (Mac) / Ctrl+K (Windows/Linux)
 * to toggle the command palette open/closed state.
 *
 * @returns {{ open: boolean, setOpen: (open: boolean) => void }}
 */
import { useCallback, useEffect, useState } from 'react';

export function useCommandPalette() {
  const [open, setOpen] = useState(false);

  const handleKeyDown = useCallback((e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      setOpen((prev) => !prev);
    }
  }, []);

  useEffect(() => {
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [handleKeyDown]);

  return { open, setOpen };
}
