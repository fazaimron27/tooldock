import { cn } from '@/Utils/utils';

import ProgressBar from '@/Components/Common/ProgressBar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * System status card component for dashboard
 * @param {object} props
 * @param {array} props.metrics - Array of metric objects with { label, value, percentage, color? }
 * @param {string} props.className - Additional CSS classes
 */
export default function SystemStatusCard({ metrics = [], className }) {
  if (!metrics || metrics.length === 0) {
    return null;
  }

  return (
    <Card className={cn(className)}>
      <CardHeader>
        <CardTitle>System Status</CardTitle>
        <CardDescription>Current system health</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {metrics.map((metric, index) => (
            <ProgressBar
              key={metric.label || index}
              label={metric.label}
              value={metric.value}
              percentage={metric.percentage}
              color={metric.color || 'primary'}
            />
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
