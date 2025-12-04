/**
 * Reusable animation variants for Framer Motion
 * Provides common animation presets for consistent UI animations
 */

export const fadeIn = {
  initial: { opacity: 0 },
  animate: { opacity: 1 },
  exit: { opacity: 0 },
};

export const slideUp = {
  initial: { opacity: 0, y: 20 },
  animate: { opacity: 1, y: 0 },
  exit: { opacity: 0, y: -20 },
};

export const slideDown = {
  initial: { opacity: 0, y: -20 },
  animate: { opacity: 1, y: 0 },
  exit: { opacity: 0, y: 20 },
};

export const slideLeft = {
  initial: { opacity: 0, x: 20 },
  animate: { opacity: 1, x: 0 },
  exit: { opacity: 0, x: -20 },
};

export const slideRight = {
  initial: { opacity: 0, x: -20 },
  animate: { opacity: 1, x: 0 },
  exit: { opacity: 0, x: 20 },
};

export const scale = {
  initial: { opacity: 0, scale: 0.95 },
  animate: { opacity: 1, scale: 1 },
  exit: { opacity: 0, scale: 0.95 },
};

export const scaleUp = {
  initial: { opacity: 0, scale: 0.8 },
  animate: { opacity: 1, scale: 1 },
  exit: { opacity: 0, scale: 0.8 },
};

/**
 * Common transition presets for consistent animation timing.
 */
export const defaultTransition = {
  duration: 0.2,
  ease: [0.4, 0, 0.2, 1],
};

export const smoothTransition = {
  duration: 0.3,
  ease: [0.4, 0, 0.2, 1],
};

export const fastTransition = {
  duration: 0.15,
  ease: [0.4, 0, 0.2, 1],
};

/**
 * Page transition variants for Inertia.js page changes.
 */
export const pageTransition = {
  initial: { opacity: 0, y: 10 },
  animate: { opacity: 1, y: 0 },
  exit: { opacity: 0, y: -10 },
  transition: defaultTransition,
};

/**
 * Dialog/Modal animation variants.
 */
export const dialogVariants = {
  overlay: {
    initial: { opacity: 0 },
    animate: { opacity: 1 },
    exit: { opacity: 0 },
    transition: defaultTransition,
  },
  content: {
    /**
     * Only animate opacity and scale to preserve CSS centering.
     * Avoids translate-x/y which can interfere with CSS positioning.
     */
    initial: { opacity: 0, scale: 0.95 },
    animate: { opacity: 1, scale: 1 },
    exit: { opacity: 0, scale: 0.95 },
    transition: smoothTransition,
  },
};

/**
 * Sheet/Drawer animation variants for slide-in panels.
 */
export const sheetVariants = {
  overlay: {
    initial: { opacity: 0 },
    animate: { opacity: 1 },
    exit: { opacity: 0 },
    transition: defaultTransition,
  },
  content: {
    initial: { x: '100%' },
    animate: { x: 0 },
    exit: { x: '100%' },
    transition: smoothTransition,
  },
};
