/**
 * Application constants and configuration values
 * Contains status color mappings, pagination settings, and date format configurations
 */

export const STATUS_COLORS = {
  Active: {
    bg: 'bg-green-100 text-green-800',
    dark: 'dark:bg-green-900/30 dark:text-green-400',
  },
  Inactive: {
    bg: 'bg-gray-100 text-gray-800',
    dark: 'dark:bg-gray-900/30 dark:text-gray-400',
  },
  Pending: {
    bg: 'bg-yellow-100 text-yellow-800',
    dark: 'dark:bg-yellow-900/30 dark:text-yellow-400',
  },
  Completed: {
    bg: 'bg-blue-100 text-blue-800',
    dark: 'dark:bg-blue-900/30 dark:text-blue-400',
  },
  Cancelled: {
    bg: 'bg-red-100 text-red-800',
    dark: 'dark:bg-red-900/30 dark:text-red-400',
  },
};

export const PAGINATION_LIMITS = [10, 20, 30, 50];

export const DEFAULT_PAGE_SIZE = 10;

export const DATE_FORMATS = {
  short: {
    dateStyle: 'short',
    timeStyle: undefined,
  },
  long: {
    dateStyle: 'long',
    timeStyle: undefined,
  },
  datetime: {
    dateStyle: 'short',
    timeStyle: 'short',
  },
  relative: 'relative',
};
