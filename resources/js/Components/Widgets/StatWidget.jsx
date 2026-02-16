/**
 * Stat Widget component for displaying statistics
 * Accepts widget props from DashboardWidgetRegistry
 */
import { formatCurrency, formatNumber } from '@/Utils/format';
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
  const icon = widget.icon || 'BarChart';
  const change = widget.change ?? null;
  const trend = widget.trend ?? null;

  const isValueMissing = widget.value === null || widget.value === undefined;
  let value = isValueMissing ? '--' : widget.value;

  if (!isValueMissing && widget.config?.valueType === 'currency') {
    value = formatCurrency(value, widget.config?.currency || 'IDR');
  } else if (!isValueMissing && typeof value === 'number') {
    value = formatNumber(value);
  }

  const Icon = getIcon(icon);
  const description = widget.description;

  return (
    <StatCard
      title={title}
      description={description}
      value={value}
      change={change}
      trend={trend}
      icon={Icon}
    />
  );
}
