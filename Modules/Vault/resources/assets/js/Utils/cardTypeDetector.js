/**
 * Credit card type detection utility using credit-card-type library
 */
import creditCardType from 'credit-card-type';

/**
 * Map credit-card-type names to our internal card type values
 */
const CARD_TYPE_MAP = {
  visa: 'visa',
  'master-card': 'mastercard',
  'american-express': 'amex',
  discover: 'discover',
  jcb: 'jcb',
  'diners-club': 'diners',
};

/**
 * Detect card type from card number
 * @param {string} cardNumber - Card number (can include spaces)
 * @returns {string|null} Detected card type or null if not detected
 */
export function detectCardType(cardNumber) {
  if (!cardNumber) {
    return null;
  }

  const digits = cardNumber.replace(/\D/g, '');

  if (digits.length < 4) {
    return null;
  }

  try {
    const types = creditCardType(digits);

    if (types.length === 0) {
      return null;
    }

    const detectedType = types[0].type;
    return CARD_TYPE_MAP[detectedType] || 'other';
  } catch {
    return null;
  }
}
