/**
 * SignalItem - Individual notification row component
 *
 * Displays a single notification with:
 * - Type-based icon and color styling
 * - Title, message, and timestamp
 * - Read/unread status indicator
 * - Click to navigate to notification details page
 */
import { cn } from '@/Utils/utils';
import { Link, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, Info, ShieldAlert } from 'lucide-react';

const typeConfig = {
  info: {
    icon: Info,
    className: 'text-blue-500 bg-blue-500/10',
    badgeClassName: 'bg-blue-500',
  },
  success: {
    icon: CheckCircle,
    className: 'text-green-500 bg-green-500/10',
    badgeClassName: 'bg-green-500',
  },
  warning: {
    icon: AlertTriangle,
    className: 'text-yellow-500 bg-yellow-500/10',
    badgeClassName: 'bg-yellow-500',
  },
  error: {
    icon: ShieldAlert,
    className: 'text-red-500 bg-red-500/10',
    badgeClassName: 'bg-red-500',
  },
};

export default function SignalItem({
  notification,
  onMarkAsRead,
  compact = false,
  linkToShow = true,
}) {
  const config = typeConfig[notification.type] || typeConfig.info;
  const Icon = config.icon;
  const isUnread = !notification.read_at;

  const handleClick = (e) => {
    if (isUnread && onMarkAsRead) {
      onMarkAsRead(notification.id);
    }

    if (!linkToShow && notification.action_url) {
      e.preventDefault();
      router.visit(notification.action_url);
    }
  };

  const content = (
    <div
      className={cn(
        'flex items-start gap-3 p-3 rounded-lg transition-colors',
        'cursor-pointer hover:bg-muted/50',
        isUnread && 'bg-muted/30',
        compact && 'py-2'
      )}
    >
      {/* Icon */}
      <div className={cn('flex-shrink-0 p-2 rounded-full', config.className)}>
        <Icon className="h-4 w-4" />
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0">
        <div className="flex items-center justify-between gap-2">
          <div className="flex items-center gap-2 min-w-0">
            <p className={cn('text-sm font-medium truncate', isUnread && 'font-semibold')}>
              {notification.title}
            </p>
            {isUnread && (
              <span className={cn('h-2 w-2 rounded-full flex-shrink-0', config.badgeClassName)} />
            )}
          </div>
          <span className="text-xs text-muted-foreground whitespace-nowrap flex-shrink-0">
            {notification.created_at_human}
          </span>
        </div>
        <p className={cn('text-sm text-muted-foreground mt-0.5', compact && 'line-clamp-1')}>
          {notification.message}
        </p>
        {notification.module_source && !compact && (
          <p className="text-xs text-muted-foreground/70 mt-1">via {notification.module_source}</p>
        )}
      </div>
    </div>
  );

  if (linkToShow) {
    return (
      <Link
        href={route('notifications.show', { notification: notification.id })}
        onClick={handleClick}
        className="block"
      >
        {content}
      </Link>
    );
  }

  return <div onClick={handleClick}>{content}</div>;
}
