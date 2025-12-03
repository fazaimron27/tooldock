/**
 * Widget Renderer component for dynamically rendering dashboard widgets
 * Supports different widget types (stat, chart, activity, system, etc.)
 * Uses dedicated widget components from Components/Widgets
 */
import { AlertTriangle } from 'lucide-react';

import ActivityWidget from '@/Components/Widgets/ActivityWidget';
import ChartWidget from '@/Components/Widgets/ChartWidget';
import StatWidget from '@/Components/Widgets/StatWidget';
import SystemWidget from '@/Components/Widgets/SystemWidget';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Valid widget types
 */
const VALID_TYPES = ['stat', 'chart', 'activity', 'system'];

/**
 * Error widget component for displaying invalid widget types
 */
function ErrorWidget({ widget, error }) {
  return (
    <Card className="border-destructive">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-destructive">
          <AlertTriangle className="h-5 w-5" />
          Invalid Widget
        </CardTitle>
        <CardDescription>
          {error || `Widget type "${widget?.type || 'unknown'}" is not supported.`}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <p className="text-sm text-muted-foreground">
          Valid widget types are: {VALID_TYPES.join(', ')}
        </p>
        {widget?.title && (
          <p className="text-sm text-muted-foreground mt-2">
            Widget: <strong>{widget.title}</strong>
          </p>
        )}
        {widget?.module && (
          <p className="text-sm text-muted-foreground">
            Module: <strong>{widget.module}</strong>
          </p>
        )}
      </CardContent>
    </Card>
  );
}

/**
 * Render a widget based on its type
 *
 * @param {Object} widget - The widget object from the registry
 * @param {string} widget.type - Widget type (e.g., 'stat', 'chart', 'activity', 'system')
 * @param {string} widget.title - Widget title
 * @param {string|number} widget.value - Widget value
 * @param {string} widget.icon - Lucide icon name
 * @param {string|null} widget.change - Change indicator (e.g., '+20.1%')
 * @param {string|null} widget.trend - Trend direction ('up' or 'down')
 * @param {array|null} widget.data - Additional data for complex widgets
 * @param {string|null} widget.description - Optional description
 * @param {string|null} widget.chartType - Chart type for chart widgets ('bar', 'area', 'line')
 */
export default function WidgetRenderer({ widget }) {
  if (!widget) {
    console.error('WidgetRenderer: widget prop is null or undefined');
    return (
      <ErrorWidget
        widget={{ type: 'unknown', title: 'Missing Widget' }}
        error="Widget data is missing"
      />
    );
  }

  if (!widget.type) {
    console.error('WidgetRenderer: widget.type is missing', { widget });
    return <ErrorWidget widget={widget} error="Widget type is required" />;
  }

  if (!VALID_TYPES.includes(widget.type)) {
    console.error('WidgetRenderer: Invalid widget type', {
      type: widget.type,
      widget,
    });
    return <ErrorWidget widget={widget} />;
  }

  switch (widget.type) {
    case 'stat':
      return <StatWidget widget={widget} />;

    case 'chart':
      return <ChartWidget widget={widget} />;

    case 'activity':
      return <ActivityWidget widget={widget} />;

    case 'system':
      return <SystemWidget widget={widget} />;

    default:
      // This should never happen due to validation above, but kept as safety net
      return <ErrorWidget widget={widget} />;
  }
}
