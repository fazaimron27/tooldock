import { cn } from '@/Utils/utils';

import StatCard from './StatCard';

/**
 * Grid layout component for multiple StatCard components
 * Can also be used with MetricCard or any other card component
 * @param {object} props
 * @param {array} props.stats - Array of stat objects with title, value, change, trend, icon
 * @param {number} props.columns - Number of columns on large screens (default: 4)
 * @param {string} props.className - Additional CSS classes
 * @param {React.ComponentType} props.cardComponent - Custom card component to use (default: StatCard)
 */
export default function StatGrid({
  stats = [],
  columns = 4,
  className,
  cardComponent: CardComponent = StatCard,
}) {
  if (!stats || stats.length === 0) {
    return null;
  }

  const gridCols = {
    1: 'grid-cols-1',
    2: 'md:grid-cols-2',
    3: 'md:grid-cols-2 lg:grid-cols-3',
    4: 'md:grid-cols-2 lg:grid-cols-4',
  };

  return (
    <div className={cn('grid gap-4', gridCols[columns] || gridCols[4], className)}>
      {stats.map((stat, index) => (
        <CardComponent key={stat.id || stat.title || index} {...stat} />
      ))}
    </div>
  );
}
