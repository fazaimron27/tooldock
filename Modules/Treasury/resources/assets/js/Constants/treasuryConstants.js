/**
 * Treasury module shared constants
 * Centralized configuration for transaction types, etc.
 *
 * Note: Currency is managed app-wide via Settings module
 * Note: Wallet types are managed via Categories module (type: 'wallet_type')
 * Note: Goal categories are managed via Categories module (type: 'goal')
 * Note: Wallet icons are in Utils/walletIcons.js
 * Note: Goal icons are in Utils/goalIcons.js
 */

export const TRANSACTION_TYPES = [
  { value: 'income', label: 'Income' },
  { value: 'expense', label: 'Expense' },
  { value: 'transfer', label: 'Transfer' },
];

export const DEFAULT_TRANSACTION_TYPE = 'expense';
