/**
 * Hook for managing toggle state with router.post
 * Provides consistent state management for toggle actions that use router.post directly
 *
 * @returns {Object} { isToggling, toggle }
 */
import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

export function useToggleState() {
  const [isToggling, setIsToggling] = useState(false);

  const toggle = useCallback((routeName, data, options = {}) => {
    setIsToggling(true);

    const originalOnSuccess = options.onSuccess;
    const originalOnError = options.onError;
    const originalOnFinish = options.onFinish;
    const originalOnStart = options.onStart;

    /**
     * Extract callbacks to prevent them from being spread in restOptions
     * This ensures our wrapped callbacks are used instead of direct options
     */
    const { onSuccess, onError, onFinish, onStart, ...restOptions } = options;

    router.post(routeName, data, {
      preserveScroll: true,
      skipLoadingIndicator: true,
      ...restOptions,
      onStart: () => {
        originalOnStart?.();
      },
      onSuccess: (page) => {
        setIsToggling(false);
        originalOnSuccess?.(page);
      },
      onError: (errors) => {
        setIsToggling(false);
        originalOnError?.(errors);
      },
      onFinish: () => {
        setIsToggling(false);
        originalOnFinish?.();
      },
    });
  }, []);

  return { isToggling, toggle };
}
