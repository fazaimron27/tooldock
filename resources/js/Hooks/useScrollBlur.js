/**
 * Hook for detecting scroll position and managing blur state
 *
 * Monitors a scroll container's scroll position and returns a boolean indicating
 * whether the container has been scrolled past a specified threshold. This is
 * useful for applying visual effects (like backdrop blur) to elements when
 * content is scrolled.
 *
 * @param {React.RefObject} scrollContainerRef - Ref to the scrollable container element
 * @param {number} threshold - Scroll position threshold in pixels (default: 10)
 * @returns {boolean} True when scroll position exceeds the threshold
 */
import { useEffect, useState } from 'react';

export function useScrollBlur(scrollContainerRef, threshold = 10) {
  const [isScrolled, setIsScrolled] = useState(false);

  useEffect(() => {
    const setupScrollListener = () => {
      const scrollContainer = scrollContainerRef?.current;
      if (!scrollContainer) {
        return null;
      }

      const handleScroll = () => {
        const scrollTop = scrollContainer.scrollTop;
        setIsScrolled(scrollTop > threshold);
      };

      handleScroll();

      scrollContainer.addEventListener('scroll', handleScroll, { passive: true });

      return () => {
        scrollContainer.removeEventListener('scroll', handleScroll);
      };
    };

    let cleanup = setupScrollListener();

    if (!cleanup) {
      const timeoutId = window.setTimeout(() => {
        cleanup = setupScrollListener();
      }, 100);

      return () => {
        window.clearTimeout(timeoutId);
        if (cleanup) {
          cleanup();
        }
      };
    }

    return cleanup;
  }, [scrollContainerRef, threshold]);

  return isScrolled;
}
