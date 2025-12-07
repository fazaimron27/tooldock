/**
 * Hook for managing appearance and formatting logic based on user locale
 * Provides formatting functions for currency, dates, numbers, and name initials
 */
import { formatCurrency, formatDate, formatNumber, getInitials } from '@/Utils/format';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

export function useAppearance(locale = 'en-US') {
  const { date_format, currency_symbol } = usePage().props;

  return useMemo(
    () => ({
      formatCurrency: (value, currency = 'IDR') =>
        formatCurrency(value, currency, locale, currency_symbol),
      formatDate: (date, format = 'short') => formatDate(date, format, locale, date_format),
      formatNumber: (value) => formatNumber(value, locale),
      getInitials: (name) => getInitials(name),
    }),
    [locale, date_format, currency_symbol]
  );
}
