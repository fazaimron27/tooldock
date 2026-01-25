/**
 * SignalDropdown - Notification dropdown content
 *
 * Shows recent notifications with:
 * - List of last 5 notifications
 * - Mark all read button
 * - View all link to inbox
 * - Error state handling
 */
import { Link, router } from '@inertiajs/react';
import { AlertCircle, Check, Inbox, RefreshCw } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/ui/button';
import { DropdownMenuLabel, DropdownMenuSeparator } from '@/Components/ui/dropdown-menu';

import SignalItem from './SignalItem';

export default function SignalDropdown({ notifications, onRefresh, error }) {
  const [isMarkingAll, setIsMarkingAll] = useState(false);

  const handleMarkAllRead = async () => {
    setIsMarkingAll(true);
    try {
      await router.post(
        route('notifications.read-all'),
        {},
        {
          preserveState: true,
          preserveScroll: true,
          onSuccess: () => {
            if (onRefresh) onRefresh();
          },
        }
      );
    } finally {
      setIsMarkingAll(false);
    }
  };

  const handleMarkAsRead = async (id) => {
    await router.post(
      route('notifications.read', { notification: id }),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          if (onRefresh) onRefresh();
        },
      }
    );
  };

  const hasUnread = notifications.some((n) => !n.read_at);

  return (
    <div className="w-96">
      <div className="flex items-center justify-between px-2 py-1.5">
        <DropdownMenuLabel className="px-0">Notifications</DropdownMenuLabel>
        {hasUnread && (
          <Button
            variant="ghost"
            size="sm"
            className="h-7 text-xs"
            onClick={handleMarkAllRead}
            disabled={isMarkingAll}
          >
            <Check className="mr-1 h-3 w-3" />
            {isMarkingAll ? 'Marking...' : 'Mark all read'}
          </Button>
        )}
      </div>
      <DropdownMenuSeparator />

      <div className="max-h-80 overflow-y-auto">
        {error ? (
          <div className="flex flex-col items-center justify-center py-8 text-center">
            <AlertCircle className="h-8 w-8 text-destructive/50 mb-2" />
            <p className="text-sm text-muted-foreground mb-2">{error}</p>
            <Button variant="ghost" size="sm" onClick={onRefresh}>
              <RefreshCw className="mr-1 h-3 w-3" />
              Retry
            </Button>
          </div>
        ) : notifications.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-8 text-center">
            <Inbox className="h-8 w-8 text-muted-foreground/50 mb-2" />
            <p className="text-sm text-muted-foreground">No notifications yet</p>
          </div>
        ) : (
          <div className="space-y-1 p-1">
            {notifications.map((notification) => (
              <SignalItem
                key={notification.id}
                notification={notification}
                onMarkAsRead={handleMarkAsRead}
                compact
                linkToShow={false}
              />
            ))}
          </div>
        )}
      </div>

      {notifications.length > 0 && !error && (
        <>
          <DropdownMenuSeparator />
          <div className="p-2">
            <Button variant="outline" size="sm" className="w-full" asChild>
              <Link href={route('notifications.index')}>View all notifications</Link>
            </Button>
          </div>
        </>
      )}
    </div>
  );
}
