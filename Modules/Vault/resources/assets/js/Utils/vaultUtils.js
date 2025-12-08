/**
 * Utility functions for Vault module
 */

/**
 * Capitalize the first letter of a string (e.g., 'login' -> 'Login')
 *
 * @param {string} str - String to capitalize
 * @returns {string} Capitalized string
 */
export function capitalizeType(str) {
  if (!str || typeof str !== 'string') {
    return str;
  }
  return str.charAt(0).toUpperCase() + str.slice(1);
}
