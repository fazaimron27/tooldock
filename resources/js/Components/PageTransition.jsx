/**
 * Page transition component using Framer Motion
 * Provides smooth transitions between Inertia.js page changes
 */
import { pageTransition } from '@/Utils/animations';
import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useMemo } from 'react';

export function PageTransition() {
  const { component: PageComponent, key } = usePage();

  /**
   * Memoize the page component to prevent unnecessary re-renders.
   * Only updates when component or key changes.
   */
  const page = useMemo(() => {
    return PageComponent ? <PageComponent key={key} /> : null;
  }, [PageComponent, key]);

  return (
    <AnimatePresence mode="wait" initial={false}>
      <motion.div
        key={key}
        initial={pageTransition.initial}
        animate={pageTransition.animate}
        exit={pageTransition.exit}
        transition={pageTransition.transition}
      >
        {page}
      </motion.div>
    </AnimatePresence>
  );
}
