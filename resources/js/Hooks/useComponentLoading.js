/**
 * Hook for managing component-level loading states
 *
 * Provides a centralized way to handle loading states for component-level actions
 * that should show a local spinner instead of the global navigation spinner.
 * Automatically handles skipLoadingIndicator for form submissions.
 *
 * @param {Object} options - Configuration options
 * @param {boolean} options.skipNavigationSpinner - Whether to skip navigation spinner (default: true)
 * @returns {Object} Loading state and helpers
 *
 * @example
 * const { isProcessing, showOverlay, formOptions } = useComponentLoading();
 *
 * // Use formOptions in form submissions
 * form.post(url, { ...formOptions, onSuccess: ... });
 *
 * // Use showOverlay to conditionally render overlay
 * {showOverlay && <LoadingOverlay />}
 */
import { useMemo, useState } from 'react';

export function useComponentLoading(options = {}) {
  const { skipNavigationSpinner = true } = options;

  const [processingStates, setProcessingStates] = useState({});

  /**
   * Set processing state for a specific action
   */
  const setProcessing = (key, value) => {
    setProcessingStates((prev) => ({
      ...prev,
      [key]: value,
    }));
  };

  /**
   * Check if any action is processing
   */
  const isProcessing = useMemo(() => {
    return Object.values(processingStates).some((state) => state === true);
  }, [processingStates]);

  /**
   * Form options that skip navigation spinner
   */
  const formOptions = useMemo(() => {
    if (skipNavigationSpinner) {
      return {
        skipLoadingIndicator: true,
      };
    }
    return {};
  }, [skipNavigationSpinner]);

  /**
   * Helper to create form submission options with loading state management
   */
  const createFormOptions = (actionKey, options = {}) => {
    return {
      ...formOptions,
      ...options,
      onStart: () => {
        setProcessing(actionKey, true);
        options.onStart?.();
      },
      onFinish: () => {
        setProcessing(actionKey, false);
        options.onFinish?.();
      },
      onError: (errors) => {
        setProcessing(actionKey, false);
        options.onError?.(errors);
      },
    };
  };

  return {
    isProcessing,
    processingStates,
    setProcessing,
    formOptions,
    createFormOptions,
  };
}
