/**
 * Transaction category icon utilities
 * Provides consistent transaction category icons across the app
 *
 * Note: Transaction categories are managed via Categories module (type: 'transaction_category').
 * Colors are primarily fetched from Categories. This utility serves as a fallback.
 * Slugs match those defined in TreasuryCategoryRegistrar.php
 */
import {
  Award,
  Banknote,
  BookOpen,
  Briefcase,
  Building2,
  Car,
  Coffee,
  Coins,
  CreditCard,
  DollarSign,
  Film,
  Fuel,
  Gift,
  Heart,
  Home,
  Hospital,
  Laptop,
  Lightbulb,
  MonitorSmartphone,
  Package,
  PiggyBank,
  Plane,
  RefreshCw,
  School,
  ShoppingBag,
  ShoppingCart,
  Smartphone,
  Sparkles,
  Stethoscope,
  Target,
  Ticket,
  TrendingDown,
  TrendingUp,
  Truck,
  Tv,
  Utensils,
  Wallet,
  Wifi,
  Zap,
} from 'lucide-react';

/**
 * Mapping of transaction category slugs to Lucide icons
 * Keys match the slugs in Categories (type: 'transaction_category')
 * Organized by: Income, Expense, and System categories
 */
export const categoryIcons = {
  // ========================================
  // INCOME CATEGORIES
  // ========================================

  // Salary & Wages
  'salary-wages': Banknote,
  'regular-salary': Banknote,
  overtime: Banknote,
  bonus: Award,
  commission: DollarSign,

  // Business Income
  'business-income': Briefcase,
  freelance: Laptop,
  'side-business': Lightbulb,
  consulting: Briefcase,

  // Investment Returns
  'investment-returns': TrendingUp,
  dividends: Coins,
  'interest-income': TrendingUp,
  'capital-gains': TrendingUp,
  'rental-income': Building2,

  // Other Income
  'other-income': Wallet,
  'gifts-received': Gift,
  refunds: RefreshCw,
  'tax-refunds': DollarSign,
  'lottery-winnings': Sparkles,
  inheritance: Gift,

  // ========================================
  // EXPENSE CATEGORIES
  // ========================================

  // Food & Dining
  'food-dining': Utensils,
  groceries: ShoppingCart,
  restaurants: Utensils,
  'coffee-snacks': Coffee,
  'food-delivery': Truck,

  // Transportation
  transportation: Car,
  fuel: Fuel,
  'public-transport': Car,
  parking: Car,
  tolls: Car,
  'ride-sharing': Car,
  'vehicle-maintenance': Car,

  // Housing
  housing: Home,
  rent: Home,
  mortgage: Home,
  'property-tax': Building2,
  'home-insurance': Home,
  'home-maintenance': Home,

  // Bills & Utilities
  'bills-utilities': Zap,
  electricity: Zap,
  water: Zap,
  gas: Zap,
  internet: Wifi,
  phone: Smartphone,
  'tv-streaming': Tv,

  // Shopping
  shopping: ShoppingBag,
  clothing: ShoppingBag,
  electronics: MonitorSmartphone,
  'home-garden': Home,
  'personal-care': Sparkles,

  // Entertainment
  entertainment: Film,
  'movies-shows': Film,
  games: MonitorSmartphone,
  hobbies: Sparkles,
  'sports-fitness': Heart,
  'events-concerts': Ticket,

  // Healthcare
  healthcare: Heart,
  'doctor-hospital': Hospital,
  pharmacy: Stethoscope,
  'health-insurance': Heart,
  dental: Stethoscope,
  'eye-care': Stethoscope,

  // Education
  education: BookOpen,
  tuition: School,
  'books-supplies': BookOpen,
  'courses-training': BookOpen,

  // Travel
  travel: Plane,
  flights: Plane,
  'hotels-accommodation': Building2,
  'travel-activities': Plane,
  'travel-insurance': Plane,

  // Financial
  financial: DollarSign,
  'bank-fees': Building2,
  'loan-interest': TrendingDown,
  'insurance-premium': CreditCard,
  taxes: DollarSign,

  // Personal
  personal: Heart,
  'gifts-given': Gift,
  donations: Heart,
  subscriptions: CreditCard,
  pets: Heart,

  // Other Expense
  'other-expense': Package,
  miscellaneous: Package,

  // ========================================
  // SYSTEM CATEGORIES
  // ========================================
  'initial-balance': PiggyBank,
  adjustment: RefreshCw,
  'goal-allocation': Target,
  transfer: RefreshCw,

  // Legacy slugs (for backward compatibility)
  salary: Banknote,
  investment: TrendingUp,
  home: Home,

  // Default fallback
  default: CreditCard,
};

/**
 * Get the icon component for a transaction category
 * @param {string} slug - Transaction category slug
 * @returns {React.ComponentType} Lucide icon component
 */
export function getCategoryIcon(slug) {
  if (!slug) return categoryIcons.default;
  return categoryIcons[slug] || categoryIcons.default;
}

/**
 * Default colors for transaction categories (fallback when category color is not available)
 * Keys match the slugs in Categories (type: 'transaction_category')
 */
export const categoryColors = {
  // Income categories
  'salary-wages': '#10b981',
  'regular-salary': '#10b981',
  overtime: '#10b981',
  bonus: '#10b981',
  commission: '#10b981',
  'business-income': '#22c55e',
  freelance: '#22c55e',
  'side-business': '#22c55e',
  consulting: '#22c55e',
  'investment-returns': '#6366f1',
  dividends: '#6366f1',
  'interest-income': '#6366f1',
  'capital-gains': '#6366f1',
  'rental-income': '#6366f1',
  'other-income': '#84cc16',
  'gifts-received': '#84cc16',
  refunds: '#84cc16',
  'tax-refunds': '#84cc16',
  'lottery-winnings': '#84cc16',
  inheritance: '#84cc16',

  // Expense categories
  'food-dining': '#ef4444',
  groceries: '#ef4444',
  restaurants: '#ef4444',
  'coffee-snacks': '#ef4444',
  'food-delivery': '#ef4444',
  transportation: '#f59e0b',
  fuel: '#f59e0b',
  'public-transport': '#f59e0b',
  parking: '#f59e0b',
  tolls: '#f59e0b',
  'ride-sharing': '#f59e0b',
  'vehicle-maintenance': '#f59e0b',
  housing: '#a855f7',
  rent: '#a855f7',
  mortgage: '#a855f7',
  'property-tax': '#a855f7',
  'home-insurance': '#a855f7',
  'home-maintenance': '#a855f7',
  'bills-utilities': '#3b82f6',
  electricity: '#3b82f6',
  water: '#3b82f6',
  gas: '#3b82f6',
  internet: '#3b82f6',
  phone: '#3b82f6',
  'tv-streaming': '#3b82f6',
  shopping: '#ec4899',
  clothing: '#ec4899',
  electronics: '#ec4899',
  'home-garden': '#ec4899',
  'personal-care': '#ec4899',
  entertainment: '#8b5cf6',
  'movies-shows': '#8b5cf6',
  games: '#8b5cf6',
  hobbies: '#8b5cf6',
  'sports-fitness': '#8b5cf6',
  'events-concerts': '#8b5cf6',
  healthcare: '#14b8a6',
  'doctor-hospital': '#14b8a6',
  pharmacy: '#14b8a6',
  'health-insurance': '#14b8a6',
  dental: '#14b8a6',
  'eye-care': '#14b8a6',
  education: '#06b6d4',
  tuition: '#06b6d4',
  'books-supplies': '#06b6d4',
  'courses-training': '#06b6d4',
  travel: '#0ea5e9',
  flights: '#0ea5e9',
  'hotels-accommodation': '#0ea5e9',
  'travel-activities': '#0ea5e9',
  'travel-insurance': '#0ea5e9',
  financial: '#64748b',
  'bank-fees': '#64748b',
  'loan-interest': '#64748b',
  'insurance-premium': '#64748b',
  taxes: '#64748b',
  personal: '#f472b6',
  'gifts-given': '#f472b6',
  donations: '#f472b6',
  subscriptions: '#f472b6',
  pets: '#f472b6',
  'other-expense': '#78716c',
  miscellaneous: '#78716c',

  // System categories
  'initial-balance': '#8b5cf6',
  adjustment: '#64748b',
  'goal-allocation': '#14b8a6',
  transfer: '#0284c7',

  // Legacy slugs
  salary: '#10b981',
  investment: '#6366f1',
  home: '#a855f7',

  // Default
  default: '#6b7280',
};

/**
 * Get the color for a transaction category (fallback when category color is not available)
 * @param {string} slug - Transaction category slug
 * @returns {string} Hex color code
 */
export function getCategoryColor(slug) {
  if (!slug) return categoryColors.default;
  return categoryColors[slug] || categoryColors.default;
}
