/* global CustomEvent, sessionStorage */
/**
 * Hook for managing navigation loading state
 * Provides loading state and methods to manually trigger loading indicators
 * Listens to Inertia router events and custom events for reliable loading state management
 * Uses sessionStorage to persist loading state across full page reloads
 */
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const LOADING_STORAGE_KEY = 'inertia:is-loading';

/**
 * Safely set item in sessionStorage with error handling
 * @param {string} key - Storage key
 * @param {string} value - Storage value
 * @returns {boolean} - True if successful, false otherwise
 */
function safeSetStorage(key, value) {
  if (typeof window === 'undefined') {
    return false;
  }
  try {
    sessionStorage.setItem(key, value);
    return true;
  } catch (e) {
    // Only log warnings in development
    if (import.meta.env.DEV) {
      // Handle quota exceeded or private browsing mode
      if (e.name === 'QuotaExceededError' || e.name === 'NS_ERROR_DOM_QUOTA_REACHED') {
        console.warn('sessionStorage quota exceeded, loading state will not persist');
      } else if (e.name === 'SecurityError') {
        console.warn(
          'sessionStorage not available (private browsing), loading state will not persist'
        );
      } else {
        console.warn('sessionStorage error:', e);
      }
    }
    return false;
  }
}

/**
 * Safely remove item from sessionStorage with error handling
 * @param {string} key - Storage key
 * @returns {boolean} - True if successful, false otherwise
 */
function safeRemoveStorage(key) {
  if (typeof window === 'undefined') {
    return false;
  }
  try {
    sessionStorage.removeItem(key);
    return true;
  } catch (e) {
    if (import.meta.env.DEV) {
      console.warn('sessionStorage remove error:', e);
    }
    return false;
  }
}

/**
 * Safely get item from sessionStorage with error handling
 * @param {string} key - Storage key
 * @returns {string|null} - Storage value or null
 */
function safeGetStorage(key) {
  if (typeof window === 'undefined') {
    return null;
  }
  try {
    return sessionStorage.getItem(key);
  } catch (e) {
    if (import.meta.env.DEV) {
      console.warn('sessionStorage get error:', e);
    }
    return null;
  }
}

export function useNavigationLoading() {
  // Initialize from sessionStorage to persist across page reloads
  const [isLoading, setIsLoading] = useState(() => {
    return safeGetStorage(LOADING_STORAGE_KEY) === 'true';
  });

  // Use refs for cleanup to avoid stale closures
  const clearOnLoadTimeoutRef = useRef(null);
  const clearOnRouterFinishRef = useRef(null);
  const isLoadingShownRef = useRef(false);

  /**
   * Determines if a visit should skip the loading indicator
   * @param {object} visit - The visit object from Inertia router event
   * @returns {boolean} - True if loading should be skipped
   */
  const shouldSkipLoading = (visit) => {
    if (!visit) {
      return false;
    }

    // Explicit flag takes precedence
    if (visit.skipLoadingIndicator === true) {
      return true;
    }

    // Only skip loading for GET requests with both preserveState and preserveScroll
    // Form submissions (POST/PUT/PATCH/DELETE) should always show loading
    const method = visit.method?.toLowerCase() || 'get';
    return method === 'get' && visit.preserveState === true && visit.preserveScroll === true;
  };

  useEffect(() => {
    const removeStartListener = router.on('start', (event) => {
      const visit = event?.detail?.visit;

      if (shouldSkipLoading(visit)) {
        return;
      }

      isLoadingShownRef.current = false;
      setIsLoading(true);
      safeSetStorage(LOADING_STORAGE_KEY, 'true');
      isLoadingShownRef.current = true;
    });

    const removeFinishListener = router.on('finish', (event) => {
      const visit = event?.detail?.visit;

      if (shouldSkipLoading(visit)) {
        return;
      }

      if (isLoadingShownRef.current || safeGetStorage(LOADING_STORAGE_KEY) === 'true') {
        setIsLoading(false);
        safeRemoveStorage(LOADING_STORAGE_KEY);
        isLoadingShownRef.current = false;
      }

      if (clearOnLoadTimeoutRef.current) {
        window.clearTimeout(clearOnLoadTimeoutRef.current);
        clearOnLoadTimeoutRef.current = null;
      }
    });

    const handleForceLoading = (event) => {
      const show = event.detail.show ?? true;
      setIsLoading(show);
      if (show) {
        safeSetStorage(LOADING_STORAGE_KEY, 'true');
      } else {
        safeRemoveStorage(LOADING_STORAGE_KEY);
      }
    };

    window.addEventListener('inertia:force-loading', handleForceLoading);

    // Handle loading state persistence across full page reloads
    // Router events don't fire on full reloads, so we use a fallback timeout
    if (safeGetStorage(LOADING_STORAGE_KEY) === 'true') {
      clearOnRouterFinishRef.current = router.on('finish', () => {
        setIsLoading(false);
        safeRemoveStorage(LOADING_STORAGE_KEY);
        if (clearOnLoadTimeoutRef.current) {
          window.clearTimeout(clearOnLoadTimeoutRef.current);
          clearOnLoadTimeoutRef.current = null;
        }
      });

      const clearOnLoad = () => {
        clearOnLoadTimeoutRef.current = window.setTimeout(() => {
          setIsLoading(false);
          safeRemoveStorage(LOADING_STORAGE_KEY);
          clearOnLoadTimeoutRef.current = null;
        }, 500);
      };

      if (document.readyState === 'complete') {
        clearOnLoad();
      } else {
        window.addEventListener('load', clearOnLoad, { once: true });
      }
    }

    return () => {
      removeStartListener();
      removeFinishListener();
      window.removeEventListener('inertia:force-loading', handleForceLoading);

      if (clearOnRouterFinishRef.current) {
        clearOnRouterFinishRef.current();
        clearOnRouterFinishRef.current = null;
      }

      if (clearOnLoadTimeoutRef.current) {
        window.clearTimeout(clearOnLoadTimeoutRef.current);
        clearOnLoadTimeoutRef.current = null;
      }

      if (safeGetStorage(LOADING_STORAGE_KEY) === 'true') {
        safeRemoveStorage(LOADING_STORAGE_KEY);
      }
    };
  }, []);

  /**
   * Manually trigger loading state
   * @param {boolean} show - Whether to show or hide the loading state
   */
  const setLoading = (show) => {
    setIsLoading(show);
    if (show) {
      safeSetStorage(LOADING_STORAGE_KEY, 'true');
    } else {
      safeRemoveStorage(LOADING_STORAGE_KEY);
    }
    window.dispatchEvent(
      new CustomEvent('inertia:force-loading', {
        detail: { show },
      })
    );
  };

  /**
   * Show loading state
   */
  const showLoading = () => {
    setLoading(true);
  };

  /**
   * Hide loading state
   */
  const hideLoading = () => {
    setLoading(false);
  };

  return {
    isLoading,
    setLoading,
    showLoading,
    hideLoading,
  };
}
