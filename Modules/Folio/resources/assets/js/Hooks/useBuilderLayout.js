import { useEffect, useRef, useState } from 'react';

/**
 * Manages the Builder page layout:
 * - Locks the outer dashboard scroll container
 * - Dynamically computes available height for the three-pane layout
 * - Restores scroll on unmount
 *
 * @returns {{ builderRef: React.RefObject, paneHeight: number }}
 */
export default function useBuilderLayout() {
  const builderRef = useRef(null);
  const [paneHeight, setPaneHeight] = useState(0);

  useEffect(() => {
    const scrollContainer = builderRef.current?.closest('[class*="overflow-y-auto"]');
    if (scrollContainer) {
      scrollContainer.style.overflow = 'hidden';
    }

    const compute = () => {
      const el = builderRef.current;
      if (!el) return;
      const footer = document.querySelector('footer');
      const footerH = footer?.getBoundingClientRect().height || 0;
      const bottomPad = 28;
      const available = window.innerHeight - el.getBoundingClientRect().top - footerH - bottomPad;
      setPaneHeight(Math.max(300, available));
    };

    compute();
    window.addEventListener('resize', compute);
    return () => {
      window.removeEventListener('resize', compute);
      if (scrollContainer) {
        scrollContainer.style.overflow = '';
      }
    };
  }, []);

  return { builderRef, paneHeight };
}
