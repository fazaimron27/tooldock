/**
 * Wallet icon utilities
 * Provides consistent wallet type icons across the app
 *
 * Note: Wallet types are managed via Categories module (type: 'wallet_type').
 * Colors are primarily fetched from Categories. This utility serves as a fallback.
 * Slugs match those defined in TreasuryCategoryRegistrar.php
 */
import { Building2, CreditCard, PiggyBank, Smartphone, TrendingUp, Wallet } from 'lucide-react';

/**
 * Mapping of wallet type slugs to Lucide icons
 * Keys match the slugs in Categories (type: 'wallet_type')
 * From TreasuryCategoryRegistrar.php:
 * - cash: Physical cash wallet
 * - bank: Bank account
 * - ewallet: Digital wallet (e.g., PayPal, GoPay)
 * - investment: Investment account
 * - savings: Savings account
 * - credit-card: Credit card account
 */
export const walletTypeIcons = {
  cash: Wallet,
  bank: Building2,
  ewallet: Smartphone,
  investment: TrendingUp,
  savings: PiggyBank,
  'credit-card': CreditCard,
};

/**
 * Get the icon component for a wallet type
 * @param {string} slug - Wallet type slug (cash, bank, ewallet, etc.)
 * @returns {React.ComponentType} Lucide icon component
 */
export function getWalletIcon(slug) {
  if (!slug) return Wallet;
  return walletTypeIcons[slug] || Wallet;
}

/**
 * Default colors for wallet types (fallback when category color is not available)
 * Keys match the slugs in Categories (type: 'wallet_type')
 * Colors match those defined in TreasuryCategoryRegistrar.php
 */
export const walletTypeColors = {
  cash: '#22c55e', // green - Physical cash wallet
  bank: '#3b82f6', // blue - Bank account
  ewallet: '#8b5cf6', // purple - Digital wallet
  investment: '#f59e0b', // amber - Investment account
  savings: '#06b6d4', // cyan - Savings account
  'credit-card': '#ef4444', // red - Credit card account
};

/**
 * Get the color for a wallet type (fallback when category color is not available)
 * @param {string} slug - Wallet type slug
 * @returns {string} Hex color code
 */
export function getWalletColor(slug) {
  if (!slug) return '#6b7280';
  return walletTypeColors[slug] || '#6b7280';
}
