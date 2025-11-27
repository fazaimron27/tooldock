/**
 * Icon resolver utility for converting icon name strings to Lucide React components
 * Handles name normalization and provides fallback icon if not found
 */
import * as LucideIcons from 'lucide-react';

export function getIcon(iconName) {
  if (!iconName) {
    return LucideIcons.Circle;
  }

  // Convert to PascalCase if needed
  const normalizedName = iconName
    .split(/[-_\s]/)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join('');

  // Try to find the icon
  const IconComponent = LucideIcons[normalizedName] || LucideIcons[iconName];

  // Return found icon or fallback
  return IconComponent || LucideIcons.Circle;
}
