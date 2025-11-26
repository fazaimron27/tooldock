import { cn } from '@/Utils/utils';

import ActivityListItem from '@/Components/Common/ActivityListItem';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Recent activity card component for dashboard
 * @param {object} props
 * @param {array} props.activities - Array of activity objects with { title, timestamp, icon?, iconColor? }
 * @param {string} props.className - Additional CSS classes
 */
export default function RecentActivityCard({ activities = [], className }) {
  if (!activities || activities.length === 0) {
    return null;
  }

  return (
    <Card className={cn(className)}>
      <CardHeader>
        <CardTitle>Recent Activity</CardTitle>
        <CardDescription>Latest system events</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {activities.map((activity, index) => (
            <ActivityListItem
              key={activity.id || index}
              title={activity.title}
              timestamp={activity.timestamp}
              icon={activity.icon}
              iconColor={activity.iconColor}
            />
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
