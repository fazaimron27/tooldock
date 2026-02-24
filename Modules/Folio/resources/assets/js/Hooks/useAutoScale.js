import { useEffect, useState } from 'react';

/**
 * Observes a container ref and returns a scale factor so that `targetWidth`
 * fits within the container's available width. Scale is capped at 1.
 */
export default function useAutoScale(containerRef, targetWidth) {
  const [scale, setScale] = useState(1);

  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;

    const computeScale = () => {
      const availableWidth = container.clientWidth;
      const newScale = Math.min(1, availableWidth / targetWidth);
      setScale(newScale);
    };

    computeScale();

    const observer = new ResizeObserver(() => computeScale());
    observer.observe(container);
    return () => observer.disconnect();
  }, [containerRef, targetWidth]);

  return scale;
}
