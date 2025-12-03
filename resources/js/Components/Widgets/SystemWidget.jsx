/**
 * System Widget component for displaying system status metrics
 * Accepts widget props from DashboardWidgetRegistry
 */
import ProgressBar from '@/Components/Common/ProgressBar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Render a system status widget
 *
 * @param {Object} widget - The widget object from the registry
 * @param {string} widget.title - Widget title (defaults to "System Status")
 * @param {string|null} widget.description - Optional description
 * @param {array} widget.data - Array of metric objects with label, value, percentage, color
 */
export default function SystemWidget({ widget }) {
  if (!widget) {
    console.error('SystemWidget: widget prop is null or undefined');
    return null;
  }

  const title = widget.title || 'System Status';
  const description = widget.description ?? 'Current system health';
  const data = Array.isArray(widget.data) ? widget.data : [];

  if (!data || data.length === 0) {
    return null;
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {data.map((metric, index) => {
            if (!metric || typeof metric !== 'object') {
              return null;
            }

            return (
              <ProgressBar
                key={metric.label || index}
                label={metric.label || 'Unknown Metric'}
                value={metric.value ?? 0}
                percentage={metric.percentage ?? 0}
                color={metric.color || 'primary'}
              />
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
}
