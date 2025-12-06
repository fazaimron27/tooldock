/**
 * Date pattern constants for date string detection
 * Used for consistent date parsing across the AuditLog module
 */

// ISO date format: "2025-12-06T16:04:41+07:00" or "2025-12-06T16:04:41Z" or "2025-12-06T10:32:23.000000Z"
// Handles microseconds (up to 6 digits) and timezone offset
export const ISO_DATE_REGEX =
  /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?([+-]\d{2}:\d{2}|Z)?$/;

// MySQL datetime format: "2025-12-06 16:01:54"
export const MYSQL_DATETIME_REGEX = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;

// Date only format: "2025-12-06"
export const DATE_ONLY_REGEX = /^\d{4}-\d{2}-\d{2}$/;

/**
 * Check if a string matches any date pattern
 *
 * @param {string} value - String to check
 * @returns {boolean} True if string matches a date pattern
 */
export function isDateString(value) {
  if (typeof value !== 'string') {
    return false;
  }

  return (
    ISO_DATE_REGEX.test(value) || MYSQL_DATETIME_REGEX.test(value) || DATE_ONLY_REGEX.test(value)
  );
}
