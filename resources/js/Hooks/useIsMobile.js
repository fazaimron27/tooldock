/**
 * Hook for detecting mobile screen size
 * Returns true when viewport width is below the mobile breakpoint (768px)
 *
 * Uses SSR-safe initial value based on window width to prevent hydration flicker
 */
import * as React from 'react';

const MOBILE_BREAKPOINT = 768;

/**
 * Get initial mobile state synchronously to prevent flash
 */
function getInitialIsMobile() {
  if (typeof window === 'undefined') {
    return false;
  }
  return window.innerWidth < MOBILE_BREAKPOINT;
}

export function useIsMobile() {
  const [isMobile, setIsMobile] = React.useState(getInitialIsMobile);

  React.useEffect(() => {
    const mql = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);
    const onChange = () => {
      setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
    };
    mql.addEventListener('change', onChange);
    setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
    return () => mql.removeEventListener('change', onChange);
  }, []);

  return isMobile;
}
