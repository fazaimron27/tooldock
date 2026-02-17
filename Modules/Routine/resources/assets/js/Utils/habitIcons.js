/**
 * Habit icon utilities
 * Provides a curated set of Lucide icons for habit selection.
 * Icons are stored as slug strings in the database and resolved to
 * Lucide React components at render time.
 */
import {
  Activity,
  Apple,
  Bath,
  BedDouble,
  Book,
  Brain,
  Briefcase,
  Code,
  Coffee,
  Dumbbell,
  Flame,
  Footprints,
  GlassWater,
  Guitar,
  Heart,
  Languages,
  Leaf,
  Moon,
  Music,
  Palette,
  Pencil,
  Pill,
  Repeat,
  Salad,
  Smartphone,
  SmilePlus,
  Sparkles,
  Sun,
  Target,
  Timer,
  TreePine,
  Trophy,
  Utensils,
  Zap,
} from 'lucide-react';

/**
 * Map of icon slugs to Lucide React components.
 * Slugs are stored in the habits.icon column.
 */
export const habitIconMap = {
  activity: Activity,
  apple: Apple,
  bath: Bath,
  bed: BedDouble,
  book: Book,
  brain: Brain,
  briefcase: Briefcase,
  code: Code,
  coffee: Coffee,
  dumbbell: Dumbbell,
  flame: Flame,
  footprints: Footprints,
  'glass-water': GlassWater,
  guitar: Guitar,
  heart: Heart,
  languages: Languages,
  leaf: Leaf,
  moon: Moon,
  music: Music,
  palette: Palette,
  pencil: Pencil,
  pill: Pill,
  repeat: Repeat,
  salad: Salad,
  smartphone: Smartphone,
  'smile-plus': SmilePlus,
  sparkles: Sparkles,
  sun: Sun,
  target: Target,
  timer: Timer,
  'tree-pine': TreePine,
  trophy: Trophy,
  utensils: Utensils,
  zap: Zap,
};

/**
 * Ordered list of icon slugs for the icon picker UI.
 */
export const habitIconSlugs = Object.keys(habitIconMap);

/**
 * Resolve an icon slug to its Lucide React component.
 * Falls back to Target if the slug is not found.
 *
 * @param {string} slug - The icon slug stored in the database
 * @returns {React.ComponentType} Lucide icon component
 */
export function getHabitIcon(slug) {
  if (!slug) return Target;
  return habitIconMap[slug] || Target;
}
