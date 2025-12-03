/**
 * Activity Widget component for displaying recent activities
 * Accepts widget props from DashboardWidgetRegistry
 */
import { getIcon } from '@/Utils/iconResolver';

import ActivityListItem from '@/Components/Common/ActivityListItem';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Render an activity widget
 *
 * @param {Object} widget - The widget object from the registry
 * @param {string} widget.title - Widget title (defaults to "Recent Activity")
 * @param {string|null} widget.description - Optional description
 * @param {array} widget.data - Array of activity objects with title, timestamp, icon, iconColor
 */
export default function ActivityWidget({ widget }) {
  if (!widget) {
    console.error('ActivityWidget: widget prop is null or undefined');
    return null;
  }

  const title = widget.title || 'Recent Activity';
  const description = widget.description ?? 'Latest system events';
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
          {data.map((activity, index) => {
            if (!activity || typeof activity !== 'object') {
              return null;
            }

            const IconComponent = activity.icon ? getIcon(activity.icon) : null;
            const iconElement = IconComponent ? <IconComponent className="h-4 w-4" /> : null;

            return (
              <ActivityListItem
                key={activity.id || index}
                title={activity.title || 'Untitled Activity'}
                timestamp={activity.timestamp || ''}
                icon={iconElement}
                iconColor={activity.iconColor}
              />
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
}
