import { cn } from '@/Utils/utils';

/**
 * Reusable activity list item component
 * @param {object} props
 * @param {string} props.title - Activity title
 * @param {string} props.timestamp - Timestamp text (e.g., "2 minutes ago")
 * @param {React.ReactNode} props.icon - Optional custom icon component
 * @param {string} props.iconColor - Icon color class (default: "bg-primary")
 * @param {string} props.className - Additional CSS classes
 */
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
