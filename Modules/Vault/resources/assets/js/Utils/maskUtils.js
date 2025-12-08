/**
 * Utility functions for masking sensitive data
 */

/**
 * Mask credit card number (shows only last 4 digits)
 * @param {string} cardNumber - Full card number
 * @returns {string} Masked card number (e.g., "**** **** **** 1234")
 */
export function maskCardNumber(cardNumber) {
  if (!cardNumber) {
    return '';
  }

  const digits = cardNumber.replace(/\D/g, '');

  if (digits.length <= 4) {
    return '*'.repeat(digits.length);
  }

  const last4 = digits.slice(-4);
  const masked = '*'.repeat(digits.length - 4);
  const formatted = (masked + last4).replace(/(.{4})/g, '$1 ').trim();

  return formatted;
}

/**
 * Format credit card number as user types (adds spaces every 4 digits)
 * @param {string} value - Card number input
 * @returns {string} Formatted card number
 */
export function formatCardNumber(value) {
  if (!value) {
    return '';
  }

  const digits = value.replace(/\D/g, '');
  const limited = digits.slice(0, 19);

  return limited.replace(/(.{4})/g, '$1 ').trim();
}

/**
 * Mask CVV (shows only asterisks)
 * @param {string} cvv - CVV code
 * @returns {string} Masked CVV (e.g., "***")
 */
export function maskCVV(cvv) {
  if (!cvv) {
    return '';
  }

  return '*'.repeat(cvv.length);
}

/**
 * Format expiration date (MM/YY)
 * @param {string} value - Expiration input
 * @returns {string} Formatted expiration (e.g., "12/25")
 */
export function formatExpirationDate(value) {
  if (!value) {
    return '';
  }

  const digits = value.replace(/\D/g, '');
  const limited = digits.slice(0, 4);

  if (limited.length >= 2) {
    return `${limited.slice(0, 2)}/${limited.slice(2)}`;
  }

  return limited;
}
