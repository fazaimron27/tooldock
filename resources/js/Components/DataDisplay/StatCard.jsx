/**
 * Stat card component for displaying statistics with trend indicators
 * Shows value, change percentage, and trend direction (up/down)
 */
import { cn } from '@/Utils/utils';
import { ArrowDownRight, ArrowUpRight } from 'lucide-react';

import MetricCard from './MetricCard';

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
