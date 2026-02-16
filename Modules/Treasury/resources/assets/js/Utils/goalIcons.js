/**
 * Goal icon utilities
 * Provides consistent goal category icons across the app
 *
 * Note: Goal categories are managed via Categories module (type: 'goal').
 * Colors are primarily fetched from Categories. This utility serves as a fallback.
 * Slugs match those defined in TreasuryCategoryRegistrar.php
 */
import {
  Armchair,
  Award,
  Baby,
  Bike,
  Box,
  Briefcase,
  Building,
  Cake,
  Car,
  CarFront,
  Coins,
  Dumbbell,
  Gamepad2,
  Gem,
  GraduationCap,
  Hammer,
  Heart,
  HeartPulse,
  Home,
  Hotel,
  Key,
  Languages,
  Laptop,
  Lightbulb,
  MapPin,
  Mountain,
  Phone,
  PiggyBank,
  Plane,
  Presentation,
  Rocket,
  Router,
  School,
  Shield,
  ShieldCheck,
  ShieldPlus,
  Smartphone,
  Sparkles,
  Stethoscope,
  Sunset,
  Target,
  TreePalm,
  TrendingUp,
} from 'lucide-react';

/**
 * Mapping of goal category slugs to Lucide icons
 * Keys match the slugs in Categories (type: 'goal')
 * Organized by parent categories from TreasuryCategoryRegistrar.php
 */
export const goalCategoryIcons = {
  shield: Shield,
  'shield-check': ShieldCheck,
  'shield-plus': ShieldPlus,
  briefcase: Briefcase,

  home: Home,
  key: Key,
  hammer: Hammer,
  armchair: Armchair,
  building: Building,

  car: Car,
  'car-front': CarFront,
  bike: Bike,
  bicycle: Bike,

  plane: Plane,
  'palm-tree': TreePalm,
  mountain: Mountain,
  hotel: Hotel,
  'map-pin': MapPin,

  'graduation-cap': GraduationCap,
  school: School,
  award: Award,
  languages: Languages,
  presentation: Presentation,

  heart: Heart,
  rings: Gem,
  baby: Baby,
  sunset: Sunset,
  cake: Cake,

  smartphone: Smartphone,
  phone: Phone,
  laptop: Laptop,
  'gamepad-2': Gamepad2,
  router: Router,

  'heart-pulse': HeartPulse,
  stethoscope: Stethoscope,
  dumbbell: Dumbbell,
  sparkles: Sparkles,

  'trending-up': TrendingUp,
  rocket: Rocket,
  coins: Coins,
  lightbulb: Lightbulb,

  target: Target,
  'piggy-bank': PiggyBank,
  box: Box,
};

/**
 * Get the icon component for a goal category
 * @param {string} slug - Goal category slug
 * @returns {React.ComponentType} Lucide icon component
 */
export function getGoalIcon(slug) {
  if (!slug) return Target;
  return goalCategoryIcons[slug] || Target;
}

/**
 * Default colors for goal categories (fallback when category color is not available)
 * Keys match the slugs in Categories (type: 'goal')
 * Colors match those defined in TreasuryCategoryRegistrar.php
 */
export const goalCategoryColors = {
  // Emergency & Security
  shield: '#ef4444',
  'shield-check': '#ef4444',
  'shield-plus': '#ef4444',
  briefcase: '#ef4444',

  // Home & Property
  home: '#f59e0b',
  key: '#f59e0b',
  hammer: '#f59e0b',
  armchair: '#f59e0b',
  building: '#f59e0b',

  // Transportation
  car: '#3b82f6',
  'car-front': '#3b82f6',
  bike: '#3b82f6',
  bicycle: '#3b82f6',

  // Travel & Leisure
  plane: '#06b6d4',
  'palm-tree': '#06b6d4',
  mountain: '#06b6d4',
  hotel: '#06b6d4',
  'map-pin': '#06b6d4',

  // Education & Growth
  'graduation-cap': '#8b5cf6',
  school: '#8b5cf6',
  award: '#8b5cf6',
  languages: '#8b5cf6',
  presentation: '#8b5cf6',

  // Life Events
  heart: '#ec4899',
  rings: '#ec4899',
  baby: '#ec4899',
  sunset: '#ec4899',
  cake: '#ec4899',

  // Technology
  smartphone: '#6366f1',
  phone: '#6366f1',
  laptop: '#6366f1',
  'gamepad-2': '#6366f1',
  router: '#6366f1',

  // Health & Medical
  'heart-pulse': '#10b981',
  stethoscope: '#10b981',
  dumbbell: '#10b981',
  sparkles: '#10b981',

  // Business & Investment
  'trending-up': '#f97316',
  rocket: '#f97316',
  coins: '#f97316',
  lightbulb: '#f97316',

  // Other
  target: '#6b7280',
  'piggy-bank': '#6b7280',
  box: '#6b7280',
};

/**
 * Get the color for a goal category (fallback when category color is not available)
 * @param {string} slug - Goal category slug
 * @returns {string} Hex color code
 */
export function getGoalColor(slug) {
  if (!slug) return '#6b7280';
  return goalCategoryColors[slug] || '#6b7280';
}
