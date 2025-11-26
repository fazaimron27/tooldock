import { cn } from '@/Utils/utils';
import { ArrowDownRight, ArrowUpRight } from 'lucide-react';

import MetricCard from './MetricCard';

/**
 * Specialized card component for displaying statistics/metrics
 * Built on top of the generic MetricCard component
 * @param {object} props
 * @param {string} props.title - Card title
 * @param {string|number} props.value - Display value
 * @param {string} props.change - Change indicator (e.g., "+20.1%")
 * @param {string} props.trend - Trend direction: 'up' or 'down'
 * @param {React.ComponentType} props.icon - Icon component from lucide-react
 * @param {string} props.className - Additional CSS classes
 */
export default function StatCard({ title, value, change, trend, icon: Icon, className }) {
  const TrendIcon = trend === 'up' ? ArrowUpRight : ArrowDownRight;

  return (
    <MetricCard title={title} icon={Icon} className={className}>
      <div className="text-2xl font-bold">{value}</div>
      {change && (
        <p
          className={cn(
            'text-xs flex items-center gap-1 mt-1',
            trend === 'up' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
          )}
        >
          <TrendIcon className="h-3 w-3" />
          {change} from last month
        </p>
      )}
    </MetricCard>
  );
}
