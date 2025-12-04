/**
 * Hook to sync Inertia.js navigation with Zustand navigation store
 * Tracks current path, previous path, and navigation state
 */
import { useAppStore } from '@/Stores/useAppStore';
import { useNavigationStore } from '@/Stores/useNavigationStore';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export function useNavigationTracking() {
  const { setCurrentPath, setNavigating } = useNavigationStore();
  const { addToHistory } = useAppStore();

  useEffect(() => {
    /**
     * Track navigation start
     */
    const handleStart = () => {
      setNavigating(true);
    };

    /**
     * Track navigation finish
     */
    const handleFinish = () => {
      const currentUrl = window.location.pathname;
      setCurrentPath(currentUrl);
      addToHistory(currentUrl);
      setNavigating(false);
    };

    /**
     * Track navigation errors
     */
    const handleError = () => {
      setNavigating(false);
    };

    /**
     * Initialize current path on mount
     */
    const initialPath = window.location.pathname;
    setCurrentPath(initialPath);
    addToHistory(initialPath);

    /**
     * Subscribe to Inertia router events
     * router.on() returns an unsubscribe function
     */
    const removeStartListener = router.on('start', handleStart);
    const removeFinishListener = router.on('finish', handleFinish);
    const removeErrorListener = router.on('error', handleError);

    /**
     * Cleanup event listeners on unmount
     */
    return () => {
      removeStartListener();
      removeFinishListener();
      removeErrorListener();
    };
  }, [setCurrentPath, setNavigating, addToHistory]);
}
