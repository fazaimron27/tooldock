/**
 * Dynamically renders dashboard widgets based on their type.
 * Routes to appropriate widget components (stat, chart, activity, system, table).
 */
import { AlertTriangle } from 'lucide-react';

import ActivityWidget from '@/Components/Widgets/ActivityWidget';
import ChartWidget from '@/Components/Widgets/ChartWidget';
import StatWidget from '@/Components/Widgets/StatWidget';
import SystemWidget from '@/Components/Widgets/SystemWidget';
import TableWidget from '@/Components/Widgets/TableWidget';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

const VALID_TYPES = ['stat', 'chart', 'activity', 'system', 'table'];

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
 * Renders a widget based on its type from the dashboard widget registry.
 *
 * @param {Object} widget - Widget configuration from registry
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

    case 'table':
      return <TableWidget widget={widget} />;

    default:
      return <ErrorWidget widget={widget} />;
  }
}
