/**
 * Centralized data formatting utilities
 * Uses native Intl.NumberFormat for currency formatting (supports all ISO 4217 currencies)
 */
import { router } from '@inertiajs/react';
import { format as formatDateFns } from 'date-fns';

/**
 * Get today's date as YYYY-MM-DD string in local timezone
 * Avoids UTC conversion issues with toISOString().split('T')[0]
 *
 * @param {Date} date - Optional date object, defaults to new Date()
 * @returns {string} Date string in YYYY-MM-DD format
 */
export function getLocalDateString(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

/**
 * Get current datetime as ISO string with timezone offset
 * Returns format: YYYY-MM-DDTHH:mm:ss+HH:mm (e.g., 2026-02-01T08:30:00+07:00)
 *
 * @param {Date} date - Optional date object, defaults to new Date()
 * @returns {string} DateTime string in ISO format with timezone
 */
export function getLocalDateTimeString(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');

  // Get timezone offset in ±HH:mm format
  const tzOffset = -date.getTimezoneOffset();
  const tzSign = tzOffset >= 0 ? '+' : '-';
  const tzHours = String(Math.floor(Math.abs(tzOffset) / 60)).padStart(2, '0');
  const tzMinutes = String(Math.abs(tzOffset) % 60).padStart(2, '0');

  return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}${tzSign}${tzHours}:${tzMinutes}`;
}

/**
 * Format a value as currency using native Intl.NumberFormat
 * Automatically handles all ISO 4217 currencies with correct symbols and decimal places
 *
 * @param {number|string} value - The value to format
 * @param {string} currency - ISO 4217 currency code (e.g., 'USD', 'EUR', 'IDR')
 * @param {string|null} locale - Optional locale override (auto-detected if not provided)
 * @param {string|null} currencySymbol - Optional custom symbol override (legacy support)
 * @returns {string} Formatted currency string
 */
export function formatCurrency(value, currency = 'IDR', locale = null, currencySymbol = null) {
  const numValue = typeof value === 'string' ? parseFloat(value) : value;

  if (isNaN(numValue)) {
    return value;
  }

  // Use locale based on currency for best formatting, or fallback to en-US
  const effectiveLocale = locale || getLocaleForCurrency(currency);

  try {
    // Use native Intl.NumberFormat - it handles all ISO 4217 currencies automatically
    const formatted = new Intl.NumberFormat(effectiveLocale, {
      style: 'currency',
      currency: currency,
      currencyDisplay: 'narrowSymbol', // Use narrow symbol when available (e.g., $ instead of US$)
    }).format(numValue);

    // If custom symbol is provided (legacy support for IDR), replace the default symbol
    if (currencySymbol && currency === 'IDR') {
      return formatted.replace(/^[^\d\s]+\s?/, `${currencySymbol} `);
    }

    return formatted;
  } catch (_error) {
    // Fallback for invalid currency codes
    return `${currency} ${numValue.toLocaleString('en-US')}`;
  }
}

/**
 * Get the most appropriate locale for a currency code
 * This ensures proper number formatting (thousand/decimal separators)
 *
 * @param {string} currency - ISO 4217 currency code
 * @returns {string} Locale string
 */
function getLocaleForCurrency(currency) {
  // 1. Try to get from router props (standard Inertia way)
  const props = router.page?.props || {};
  const currencyMap = props.currency_map || {};

  if (currencyMap[currency]) {
    return currencyMap[currency].locale.replace('_', '-');
  }

  // 2. Try to get from DOM directly (fallback if router is not yet initialized)
  try {
    const el = document.getElementById('app');
    if (el?.dataset?.page) {
      const pageData = JSON.parse(el.dataset.page);
      const map = pageData?.props?.currency_map || {};
      if (map[currency]) {
        return map[currency].locale.replace('_', '-');
      }
    }
  } catch (_e) {
    // Ignore parsing errors
  }

  // 3. Common currency-to-locale fallbacks
  const fallbacks = {
    IDR: 'id-ID',
    USD: 'en-US',
    EUR: 'de-DE',
    JPY: 'ja-JP',
    GBP: 'en-GB',
    SGD: 'en-SG',
    MYR: 'ms-MY',
  };

  if (fallbacks[currency]) {
    return fallbacks[currency];
  }

  // 4. Default to document lang or en-US
  return document.documentElement.lang || 'en-US';
}

/**
 * Get currency info using Intl.NumberFormat
 * Extracts symbol and decimal info from the browser's built-in currency data
 *
 * @param {string} currencyCode - ISO 4217 currency code
 * @returns {{ symbol: string, decimals: number, thousandSep: string, decimalSep: string }}
 */
export function getCurrencyInfo(currencyCode) {
  const locale = getLocaleForCurrency(currencyCode);

  try {
    // Format a test number to extract formatting info
    // Use a large enough number to ensure a thousand separator is present
    const formatter = new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currencyCode,
      currencyDisplay: 'narrowSymbol',
    });

    const parts = formatter.formatToParts(1234567.89);

    let symbol = currencyCode;
    let decimals = 2;
    let thousandSep = locale.startsWith('id') ? '.' : ',';
    let decimalSep = locale.startsWith('id') ? ',' : '.';

    for (const part of parts) {
      switch (part.type) {
        case 'currency':
          symbol = part.value.trim();
          break;
        case 'group':
          thousandSep = part.value;
          break;
        case 'decimal':
          decimalSep = part.value;
          break;
        case 'fraction':
          decimals = part.value.length;
          break;
      }
    }

    // Check if this is a zero-decimal currency by formatting 1.5
    // Some locales/currencies might have decimals in data but not in common usage
    const testFormat = formatter.format(1.5);
    if (!testFormat.includes(decimalSep)) {
      decimals = 0;
    }

    return { symbol, decimals, thousandSep, decimalSep, locale };
  } catch (_error) {
    return {
      symbol: currencyCode,
      decimals: 2,
      thousandSep: currencyCode === 'IDR' ? '.' : ',',
      decimalSep: currencyCode === 'IDR' ? ',' : '.',
      locale: 'en-US',
    };
  }
}

/**
 * Convert PHP date format to date-fns format
 */
function convertPhpToDateFnsFormat(phpFormat) {
  const formatMap = {
    d: 'dd',
    j: 'd',
    m: 'MM',
    n: 'M',
    Y: 'yyyy',
    y: 'yy',
    H: 'HH',
    h: 'hh',
    i: 'mm',
    s: 'ss',
    F: 'MMMM',
    M: 'MMM',
    l: 'EEEE',
    D: 'EEE',
  };

  let dateFnsFormat = '';
  let i = 0;

  while (i < phpFormat.length) {
    const char = phpFormat[i];
    if (char === '\\') {
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

export function formatDate(
  date,
  format = 'short',
  locale = document.documentElement.lang || 'en-US',
  customFormat = null
) {
  if (!date) return '';

  // Detect if this is a date-only string (YYYY-MM-DD format without time component)
  // Date-only strings should be parsed in local timezone, not UTC
  const isDateOnly = typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date.trim());

  let dateObj;
  if (date instanceof Date) {
    dateObj = date;
  } else if (isDateOnly) {
    // Append T00:00:00 to parse in local timezone instead of UTC
    // Without timezone suffix, JavaScript uses the browser's local timezone
    dateObj = new Date(date + 'T00:00:00');
  } else {
    dateObj = new Date(date);
  }

  if (isNaN(dateObj.getTime())) return date;

  if (format === 'relative') return formatRelativeDate(dateObj);

  if (customFormat) {
    try {
      const dateFnsFormat = convertPhpToDateFnsFormat(customFormat);
      return formatDateFns(dateObj, dateFnsFormat);
    } catch (_error) {
      // Fallback
    }
  }

  const formatOptions = {
    short: { dateStyle: 'short' },
    long: { dateStyle: 'long' },
    datetime: { dateStyle: 'short', timeStyle: 'short' },
    full: { dateStyle: 'long', timeStyle: 'short' },
  };

  let options = formatOptions[format] || formatOptions.short;

  // For date-only values, don't show time even if 'full' or 'datetime' format is requested
  // There's no meaningful time data for date-only fields
  if (isDateOnly && options.timeStyle) {
    options = { dateStyle: options.dateStyle };
  }

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

  if (diffDay > 7) return formatDate(date, 'short');
  if (diffHour > 0)
    return `${isPast ? '' : 'in '}${diffHour} hour${diffHour === 1 ? '' : 's'}${isPast ? ' ago' : ''}`;
  if (diffMin > 0)
    return `${isPast ? '' : 'in '}${diffMin} minute${diffMin === 1 ? '' : 's'}${isPast ? ' ago' : ''}`;

  return 'just now';
}

export function formatNumber(value, locale = document.documentElement.lang || 'en-US') {
  const numValue = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(numValue)) return value;

  try {
    return new Intl.NumberFormat(locale).format(numValue);
  } catch (_error) {
    return numValue.toLocaleString();
  }
}

export function getInitials(name) {
  if (!name || typeof name !== 'string') return '';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 0) return '';
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

export function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}
