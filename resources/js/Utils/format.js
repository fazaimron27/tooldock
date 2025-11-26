/**
 * Centralized data formatting utilities
 */

/**
 * Format a number as currency
 * @param {number|string} value - The value to format
 * @param {string} currency - Currency code (default: 'IDR')
 * @param {string} locale - Locale string (default: 'id-ID' for IDR, 'en-US' otherwise)
 * @returns {string} Formatted currency string
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

/**
 * Format a date according to the specified format
 * @param {Date|string|number} date - The date to format
 * @param {string} format - Format type: 'short', 'long', 'datetime', or 'relative'
 * @param {string} locale - Locale string (default: 'en-US')
 * @returns {string} Formatted date string
 */
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

/**
 * Format a date as relative time (e.g., "2 hours ago", "in 3 days")
 * @param {Date} date - The date to format
 * @returns {string} Relative time string
 */
function formatRelativeDate(date) {
  const now = new Date();
  const diffMs = date.getTime() - now.getTime();
  const diffSec = Math.floor(diffMs / 1000);
  const diffMin = Math.floor(diffSec / 60);
  const diffHour = Math.floor(diffMin / 60);
  const diffDay = Math.floor(diffHour / 24);

  const isPast = diffMs < 0;
  const absDiff = Math.abs;

  if (absDiff(diffDay) > 7) {
    return formatDate(date, 'short');
  }

  if (absDiff(diffDay) > 0) {
    return `${isPast ? '' : 'in '}${absDiff(diffDay)} ${absDiff(diffDay) === 1 ? 'day' : 'days'}${isPast ? ' ago' : ''}`;
  }

  if (absDiff(diffHour) > 0) {
    return `${isPast ? '' : 'in '}${absDiff(diffHour)} ${absDiff(diffHour) === 1 ? 'hour' : 'hours'}${isPast ? ' ago' : ''}`;
  }

  if (absDiff(diffMin) > 0) {
    return `${isPast ? '' : 'in '}${absDiff(diffMin)} ${absDiff(diffMin) === 1 ? 'minute' : 'minutes'}${isPast ? ' ago' : ''}`;
  }

  return 'just now';
}

/**
 * Format a number with locale-specific formatting
 * @param {number|string} value - The number to format
 * @param {string} locale - Locale string (default: 'en-US')
 * @returns {string} Formatted number string
 */
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

/**
 * Extract initials from a name
 * @param {string} name - Full name (e.g., "John Doe")
 * @returns {string} Initials (e.g., "JD")
 */
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
