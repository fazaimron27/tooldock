/**
 * Stat Widget component for displaying statistics
 * Accepts widget props from DashboardWidgetRegistry
 */
import { getIcon } from '@/Utils/iconResolver';

import StatCard from '@/Components/DataDisplay/StatCard';

/**
 * Render a stat widget
 *
 * @param {Object} widget - The widget object from the registry
 * @param {string} widget.title - Widget title
 * @param {string|number} widget.value - Widget value
 * @param {string} widget.icon - Lucide icon name
 * @param {string|null} widget.change - Change indicator (e.g., '+20.1%')
 * @param {string|null} widget.trend - Trend direction ('up' or 'down')
 */
export default function StatWidget({ widget }) {
  if (!widget) {
    console.error('StatWidget: widget prop is null or undefined');
    return null;
  }

  const title = widget.title || 'Untitled';
  const value = widget.value ?? 0;
  const icon = widget.icon || 'BarChart';
  const change = widget.change ?? null;
  const trend = widget.trend ?? null;

  const Icon = getIcon(icon);

  return <StatCard title={title} value={value} change={change} trend={trend} icon={Icon} />;
}
