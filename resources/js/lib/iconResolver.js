import * as LucideIcons from "lucide-react";

/**
 * Resolve icon name string to Lucide React component
 * @param {string} iconName - Name of the icon (e.g., "Home", "FileText")
 * @returns {React.ComponentType} - The icon component or a fallback
 */
export function getIcon(iconName) {
  if (!iconName) {
    return LucideIcons.Circle;
  }

  // Convert to PascalCase if needed
  const normalizedName = iconName
    .split(/[-_\s]/)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join("");

  // Try to find the icon
  const IconComponent = LucideIcons[normalizedName] || LucideIcons[iconName];

  // Return found icon or fallback
  return IconComponent || LucideIcons.Circle;
}
