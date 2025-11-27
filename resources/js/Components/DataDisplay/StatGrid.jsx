/**
 * Grid layout component for displaying multiple stat cards
 * Supports responsive column layouts and custom card components
 */
import { cn } from '@/Utils/utils';

import StatCard from './StatCard';

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
