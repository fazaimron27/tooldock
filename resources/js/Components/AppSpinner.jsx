import { router } from '@inertiajs/react';
import { useEffect, useLayoutEffect, useState } from 'react';

import { Spinner } from '@/Components/ui/spinner';

/**
 * Global navigation state store
 * Enables cross-component communication for navigation spinner visibility
 */
let navigationState = {
  isNavigating: false,
  currentVisit: null,
  listeners: new Set(),
};

/**
 * Check if a visit should show the loading spinner
 * Skips spinner for partial updates (datatables, filtering, searching) and component-level actions
 *
 * Priority order:
 * 1. skipLoadingIndicator flag (explicit opt-out)
 * 2. Partial updates (only prop or preserveState on same page)
 * 3. Default: show spinner for navigation
 */
/**
 * Determines if navigation spinner should be displayed
 * Implements priority-based detection to skip spinner for partial updates
 */
function shouldShowSpinner(visit) {
  if (!visit) {
    return true;
  }

  if (visit.skipLoadingIndicator === true) {
    return false;
  }

  if (visit.only && Array.isArray(visit.only) && visit.only.length > 0) {
    return false;
  }

  if (visit.preserveState === true && visit.url && typeof visit.url === 'string') {
    const currentPath = window.location.pathname;
    const visitPath = visit.url.split('?')[0];

    if (visitPath === currentPath) {
      return false;
    }
  }

  return true;
}

/**
 * Updates global navigation state and notifies all listeners
 * Automatically skips spinner for partial updates based on visit options
 */
export function setNavigationState(isNavigating, visit = null) {
  if (visit) {
    navigationState.currentVisit = visit;
  }

  if (isNavigating) {
    const visitToCheck = visit || navigationState.currentVisit;
    if (visitToCheck && !shouldShowSpinner(visitToCheck)) {
      navigationState.isNavigating = false;
      navigationState.listeners.forEach((listener) => listener(false));
      return;
    }
  }

  navigationState.isNavigating = isNavigating;
  navigationState.listeners.forEach((listener) => listener(isNavigating));

  if (!isNavigating) {
    navigationState.currentVisit = null;
  }
}

export function subscribeToNavigation(listener) {
  navigationState.listeners.add(listener);
  return () => {
    navigationState.listeners.delete(listener);
  };
}

/**
 * Global spinner shown during initial page load
 * Hides once React has mounted and the page is fully loaded
 * Ensures spinner shows even on hard reloads
 */
export function GlobalSpinner() {
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    /**
     * Ensures spinner displays for minimum duration (500ms) for better UX
     * Handles edge cases where document is already complete on mount (hard reloads)
     * Includes fallback timeout to prevent spinner from staying forever
     */
    const minDisplayTime = 500;
    const mountTime = Date.now();
    let loadTime = null;
    let timeoutId = null;

    const hideSpinner = () => {
      if (timeoutId) {
        return;
      }

      const elapsed = Date.now() - mountTime;
      const remainingTime = Math.max(0, minDisplayTime - elapsed);

      timeoutId = window.setTimeout(() => {
        setIsLoading(false);
      }, remainingTime);
    };

    if (document.readyState === 'complete') {
      loadTime = Date.now();
      hideSpinner();
    } else {
      const handleLoad = () => {
        loadTime = Date.now();
        hideSpinner();
      };
      window.addEventListener('load', handleLoad, { once: true });

      const fallbackTimeout = window.setTimeout(() => {
        if (!loadTime) {
          loadTime = Date.now();
          hideSpinner();
        }
      }, minDisplayTime + 1000);

      return () => {
        window.removeEventListener('load', handleLoad);
        if (timeoutId) {
          window.clearTimeout(timeoutId);
        }
        window.clearTimeout(fallbackTimeout);
      };
    }

    return () => {
      if (timeoutId) {
        window.clearTimeout(timeoutId);
      }
    };
  }, []);

  if (!isLoading) {
    return null;
  }

  return (
    <div className="fixed top-0 left-0 z-[9999] flex h-full w-full items-center justify-center bg-background/85 backdrop-blur-md backdrop-saturate-[180%]">
      <div className="flex flex-col items-center gap-3">
        <Spinner className="size-12" />
        <p className="text-muted-foreground text-sm m-0 font-medium">Loading Tool Dock...</p>
      </div>
    </div>
  );
}

/**
 * Reusable content spinner with backdrop
 * Uses fixed positioning to cover the visible viewport of the content area
 * Calculates bounds to exclude sidebar, navbar, and footer
 * Works correctly even when content is scrolled
 * Calculates bounds synchronously to prevent flicker
 */
export function ContentSpinner({ visible, containerRef }) {
  /**
   * Initialize bounds synchronously to prevent flicker on first render
   * Calculates viewport-relative position excluding sidebar, navbar, and footer
   */
  const [bounds, setBounds] = useState(() => {
    if (visible && containerRef?.current) {
      const container = containerRef.current;
      const rect = container.getBoundingClientRect();
      return {
        top: rect.top,
        left: rect.left,
        right: window.innerWidth - rect.right,
        bottom: window.innerHeight - rect.bottom,
      };
    }
    return { top: 0, left: 0, right: 0, bottom: 0 };
  });

  /**
   * Updates bounds synchronously before paint to prevent visual jumps
   * Listens to scroll and resize events to maintain correct positioning
   */
  useLayoutEffect(() => {
    if (!visible) {
      return;
    }

    if (containerRef?.current) {
      const container = containerRef.current;
      const rect = container.getBoundingClientRect();
      setBounds({
        top: rect.top,
        left: rect.left,
        right: window.innerWidth - rect.right,
        bottom: window.innerHeight - rect.bottom,
      });
    }

    if (!containerRef?.current) {
      return;
    }

    const container = containerRef.current;
    let rafId = null;

    const updateBounds = () => {
      if (!container) {
        return;
      }

      const rect = container.getBoundingClientRect();
      setBounds({
        top: rect.top,
        left: rect.left,
        right: window.innerWidth - rect.right,
        bottom: window.innerHeight - rect.bottom,
      });
    };

    const throttledUpdate = () => {
      if (rafId) {
        return;
      }
      rafId = window.requestAnimationFrame(() => {
        updateBounds();
        rafId = null;
      });
    };

    window.addEventListener('resize', throttledUpdate, { passive: true });
    window.addEventListener('scroll', throttledUpdate, { passive: true, capture: true });

    return () => {
      if (rafId) {
        window.cancelAnimationFrame(rafId);
      }
      window.removeEventListener('resize', throttledUpdate);
      window.removeEventListener('scroll', throttledUpdate, { capture: true });
    };
  }, [visible, containerRef]);

  if (!visible) {
    return null;
  }

  return (
    <div
      className="fixed z-40 flex items-center justify-center bg-background/70 backdrop-blur backdrop-saturate-[150%] pointer-events-none"
      style={{
        top: `${bounds.top}px`,
        left: `${bounds.left}px`,
        right: `${bounds.right}px`,
        bottom: `${bounds.bottom}px`,
      }}
    >
      <Spinner className="size-10" />
    </div>
  );
}

/**
 * Navigation spinner that listens to Inertia router events
 * and the global navigation state
 * Accepts a containerRef to calculate the content area bounds
 * Automatically skips spinner for partial updates (datatables, filtering, searching)
 */
export function NavigationSpinner({ containerRef }) {
  const [isNavigating, setIsNavigating] = useState(false);

  useEffect(() => {
    setIsNavigating(navigationState.isNavigating);
    const unsubscribe = subscribeToNavigation(setIsNavigating);

    /**
     * Listen to Inertia router events to capture visit options
     * Visit options are used to determine if spinner should be shown
     * (e.g., skipLoadingIndicator, only prop, preserveState)
     */
    const handleStart = (event) => {
      const visit = event?.detail?.visit;
      setNavigationState(true, visit);
    };

    const handleFinish = () => {
      setNavigationState(false);
    };

    const handleError = () => {
      setNavigationState(false);
    };

    if (router && typeof router.on === 'function') {
      const removeStartListener = router.on('start', handleStart);
      const removeFinishListener = router.on('finish', handleFinish);
      const removeErrorListener = router.on('error', handleError);

      return () => {
        unsubscribe();
        removeStartListener();
        removeFinishListener();
        removeErrorListener();
      };
    }

    return unsubscribe;
  }, []);

  return <ContentSpinner visible={isNavigating} containerRef={containerRef} />;
}
