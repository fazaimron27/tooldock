/**
 * Centralized data formatting utilities
 * Provides functions for formatting currency, dates, numbers, and extracting name initials
 */

export function formatCurrency(value, currency = 'IDR', locale = null) {
  const numValue = typeof value === 'string' ? parseFloat(value) : value;

  if (isNaN(numValue)) {
    return value;
  }

  // Default locale based on currency
  const defaultLocale = locale || (currency === 'IDR' ? 'id-ID' : 'en-US');

  try {
    return new Intl.NumberFormat(defaultLocale, {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: currency === 'IDR' ? 0 : 2,
      maximumFractionDigits: currency === 'IDR' ? 0 : 2,
    }).format(numValue);
  } catch (_error) {
    // Fallback formatting
    return `${currency} ${numValue.toLocaleString()}`;
  }
}

export function formatDate(date, format = 'short', locale = 'en-US') {
  if (!date) {
    return '';
  }

  const dateObj = date instanceof Date ? date : new Date(date);

  if (isNaN(dateObj.getTime())) {
    return date;
  }

  if (format === 'relative') {
    return formatRelativeDate(dateObj);
  }

  const formatOptions = {
    short: { dateStyle: 'short' },
    long: { dateStyle: 'long' },
    datetime: { dateStyle: 'short', timeStyle: 'short' },
  };

  const options = formatOptions[format] || formatOptions.short;

  try {
    return new Intl.DateTimeFormat(locale, options).format(dateObj);
  } catch (_error) {
    return dateObj.toLocaleDateString();
  }
}

function formatRelativeDate(date) {
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffSec = Math.floor(Math.abs(diffMs) / 1000);
  const diffMin = Math.floor(diffSec / 60);
  const diffHour = Math.floor(diffMin / 60);
  const diffDay = Math.floor(diffHour / 24);

  const isPast = diffMs > 0;

  if (diffDay > 7) {
    return formatDate(date, 'short');
  }

  if (diffDay > 0) {
    return `${isPast ? '' : 'in '}${diffDay} ${diffDay === 1 ? 'day' : 'days'}${isPast ? ' ago' : ''}`;
  }

  if (diffHour > 0) {
    return `${isPast ? '' : 'in '}${diffHour} ${diffHour === 1 ? 'hour' : 'hours'}${isPast ? ' ago' : ''}`;
  }

  if (diffMin > 0) {
    return `${isPast ? '' : 'in '}${diffMin} ${diffMin === 1 ? 'minute' : 'minutes'}${isPast ? ' ago' : ''}`;
  }

  return 'just now';
}

export function formatNumber(value, locale = 'en-US') {
  const numValue = typeof value === 'string' ? parseFloat(value) : value;

  if (isNaN(numValue)) {
    return value;
  }

  try {
    return new Intl.NumberFormat(locale).format(numValue);
  } catch (_error) {
    return numValue.toLocaleString();
  }
}

export function getInitials(name) {
  if (!name || typeof name !== 'string') {
    return '';
  }

  const parts = name.trim().split(/\s+/);

  if (parts.length === 0) {
    return '';
  }

  if (parts.length === 1) {
    return parts[0].charAt(0).toUpperCase();
  }

  // Return first letter of first and last name
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}
