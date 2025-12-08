/**
 * Card type logo component
 * Displays credit card brand logos (Visa, Mastercard, etc.)
 */
import { PaymentIcon } from 'react-svg-credit-card-payment-icons';

/**
 * Map internal card type to PaymentIcon type format
 */
const CARD_TYPE_MAP = {
  visa: 'visa',
  mastercard: 'mastercard',
  'american-express': 'american-express',
  discover: 'discover',
  jcb: 'jcb',
  'diners-club': 'diners-club',
  unionpay: 'unionpay',
  maestro: 'maestro',
  elo: 'elo',
  hipercard: 'hipercard',
  mir: 'mir',
  other: null,
};

/**
 * CardTypeLogo component
 * @param {Object} props
 * @param {string} props.cardType - The card type slug (e.g., 'visa', 'mastercard')
 * @param {string} props.className - Additional CSS classes
 * @param {number} props.size - Icon size in pixels (default: 24)
 */
export default function CardTypeLogo({ cardType, className = '', size = 24 }) {
  if (!cardType || cardType === 'other') {
    return null;
  }

  const iconType = CARD_TYPE_MAP[cardType];

  if (!iconType) {
    return null;
  }

  return <PaymentIcon type={iconType} width={size} height={size} className={className} />;
}
