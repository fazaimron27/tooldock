/**
 * Utility function for merging Tailwind CSS classes
 * Combines clsx and tailwind-merge for conditional class names with conflict resolution
 */
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}
