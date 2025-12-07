/**
 * Centralized data formatting utilities
 * Provides functions for formatting currency, dates, numbers, and extracting name initials
 */
import { format as formatDateFns } from 'date-fns';

export function formatCurrency(value, currency = 'IDR', locale = null, currencySymbol = null) {
  const numValue = typeof value === 'string' ? parseFloat(value) : value;

  if (isNaN(numValue)) {
    return value;
  }

  // Use custom currency symbol from settings if provided
  const symbol = currencySymbol || 'Rp';

  // Default locale based on currency
  const defaultLocale = locale || (currency === 'IDR' ? 'id-ID' : 'en-US');

  try {
    // If custom symbol is provided, use custom formatting
    if (symbol && symbol !== currency) {
      return `${symbol} ${numValue.toLocaleString(defaultLocale, {
        minimumFractionDigits: currency === 'IDR' ? 0 : 2,
        maximumFractionDigits: currency === 'IDR' ? 0 : 2,
      })}`;
    }

    return new Intl.NumberFormat(defaultLocale, {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: currency === 'IDR' ? 0 : 2,
      maximumFractionDigits: currency === 'IDR' ? 0 : 2,
    }).format(numValue);
  } catch (_error) {
    // Fallback formatting
    return `${symbol} ${numValue.toLocaleString()}`;
  }
}

/**
 * Convert PHP date format to date-fns format
 */
function convertPhpToDateFnsFormat(phpFormat) {
  const formatMap = {
    d: 'dd', // Day of the month, 2 digits with leading zeros
    D: 'EEE', // A textual representation of a day, three letters
    j: 'd', // Day of the month without leading zeros
    l: 'EEEE', // A full textual representation of the day of the week
    N: 'i', // ISO-8601 numeric representation of the day of the week
    S: '', // English ordinal suffix for the day of the month
    w: 'c', // Numeric representation of the day of the week
    z: 'D', // The day of the year (starting from 0)
    W: 'w', // ISO-8601 week number of year
    F: 'MMMM', // A full textual representation of a month
    m: 'MM', // Numeric representation of a month, with leading zeros
    M: 'MMM', // A short textual representation of a month, three letters
    n: 'M', // Numeric representation of a month, without leading zeros
    t: '', // Number of days in the given month
    L: '', // Whether it's a leap year
    o: 'yyyy', // ISO-8601 week-numbering year
    Y: 'yyyy', // A full numeric representation of a year, 4 digits
    y: 'yy', // A two digit representation of a year
    a: 'a', // Lowercase Ante meridiem and Post meridiem
    A: 'a', // Uppercase Ante meridiem and Post meridiem
    g: 'h', // 12-hour format of an hour without leading zeros
    G: 'H', // 24-hour format of an hour without leading zeros
    h: 'hh', // 12-hour format of an hour with leading zeros
    H: 'HH', // 24-hour format of an hour with leading zeros
    i: 'mm', // Minutes with leading zeros
    s: 'ss', // Seconds with leading zeros
    u: 'SSS', // Microseconds
    v: 'SSS', // Milliseconds
  };

  let dateFnsFormat = '';
  let i = 0;

  while (i < phpFormat.length) {
    const char = phpFormat[i];
    if (char === '\\') {
      // Escape character - take next character literally
      if (i + 1 < phpFormat.length) {
        dateFnsFormat += phpFormat[i + 1];
        i += 2;
      } else {
        i++;
      }
    } else if (formatMap[char]) {
      dateFnsFormat += formatMap[char];
      i++;
    } else {
      dateFnsFormat += char;
      i++;
    }
  }

  return dateFnsFormat;
}

export function formatDate(date, format = 'short', locale = 'en-US', customFormat = null) {
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

  // If custom format is provided (PHP-style format string), use date-fns
  // This allows using the date_format setting for all format types
  if (
    customFormat &&
    (customFormat.includes('/') ||
      customFormat.includes('-') ||
      customFormat.includes('d') ||
      customFormat.includes('Y') ||
      customFormat.includes('m') ||
      customFormat.includes('H') ||
      customFormat.includes('i'))
  ) {
    try {
      const dateFnsFormat = convertPhpToDateFnsFormat(customFormat);
      return formatDateFns(dateObj, dateFnsFormat);
    } catch (_error) {
      // Fall back to Intl if conversion fails
    }
  }

  // Use Intl.DateTimeFormat for standard formats when no custom format is provided
  const formatOptions = {
    short: { dateStyle: 'short' },
    long: { dateStyle: 'long' },
    datetime: { dateStyle: 'short', timeStyle: 'short' },
    full: { dateStyle: 'long', timeStyle: 'short' },
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

export function formatFileSize(bytes) {
  if (bytes === 0) {
    return '0 Bytes';
  }
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}
