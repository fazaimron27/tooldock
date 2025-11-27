/**
 * Recent activity card component displaying a list of recent system activities
 * Renders activity items in a card layout
 */
import { cn } from '@/Utils/utils';

import ActivityListItem from '@/Components/Common/ActivityListItem';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

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
