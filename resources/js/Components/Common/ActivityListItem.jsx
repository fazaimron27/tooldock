/**
 * Activity list item component for displaying activity entries
 * Shows title, timestamp, and optional icon with consistent styling
 */
import { cn } from '@/Utils/utils';

export default function ActivityListItem({
  title,
  timestamp,
  icon,
  iconColor = 'bg-primary',
  className,
}) {
  return (
    <div className={cn('flex items-center gap-4', className)}>
      {icon ? icon : <div className={cn('h-2 w-2 rounded-full', iconColor)} />}
      <div className="flex-1">
        <p className="text-sm font-medium">{title}</p>
        {timestamp && <p className="text-xs text-muted-foreground">{timestamp}</p>}
      </div>
    </div>
  );
}
