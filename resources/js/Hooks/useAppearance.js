/**
 * Hook for managing appearance and formatting logic based on user locale
 * Provides formatting functions for currency, dates, numbers, and name initials
 */
import { formatCurrency, formatDate, formatNumber, getInitials } from '@/Utils/format';
import { useMemo } from 'react';

export function useAppearance(locale = 'en-US') {
  return useMemo(
    () => ({
      formatCurrency: (value, currency = 'IDR') => formatCurrency(value, currency, locale),
      formatDate: (date, format = 'short') => formatDate(date, format, locale),
      formatNumber: (value) => formatNumber(value, locale),
      getInitials: (name) => getInitials(name),
    }),
    [locale]
  );
}
