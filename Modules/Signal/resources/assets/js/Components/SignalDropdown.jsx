/**
 * SignalDropdown - Notification dropdown content
 *
 * Shows recent notifications with:
 * - List of last 5 notifications
 * - Mark all read button (uses React Query mutation)
 * - Sound toggle with volume control
 * - View all link to inbox
 * - Error state handling
 */
import { Link, router, usePage } from '@inertiajs/react';
import { AlertCircle, Inbox, RefreshCw, Volume, Volume1, Volume2, VolumeX } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/ui/button';
import { DropdownMenuLabel, DropdownMenuSeparator } from '@/Components/ui/dropdown-menu';
import { Slider } from '@/Components/ui/slider';

import { useMarkAllAsRead, useMarkAsRead } from '../Hooks/useNotificationQueries';
import { playNotificationSound } from '../Utils/notificationSound';
import SignalItem from './SignalItem';

export default function SignalDropdown({ notifications, onRefresh, error }) {
  const { userPreferences } = usePage().props;

  // Local state for immediate UI feedback, synced with server preferences
  const [soundEnabled, setSoundEnabled] = useState(userPreferences?.notificationSound ?? true);
  const [soundVolume, setSoundVolume] = useState(userPreferences?.notificationVolume ?? 0.5);

  // React Query mutations
  const markAllReadMutation = useMarkAllAsRead({
    onSuccess: () => {
      if (onRefresh) onRefresh();
    },
  });

  const markAsReadMutation = useMarkAsRead({
    onSuccess: () => {
      if (onRefresh) onRefresh();
    },
  });

  const handleMarkAllRead = () => {
    markAllReadMutation.mutate({});
  };

  const handleMarkAsRead = (id) => {
    markAsReadMutation.mutate(id);
  };

  const toggleSound = () => {
    const newValue = !soundEnabled;
    setSoundEnabled(newValue);

    // Sync to backend
    router.post(
      route('preferences.update'),
      {
        key: 'core_notification_sound',
        value: newValue,
      },
      {
        preserveScroll: true,
        preserveState: true,
        only: [],
      }
    );

    // Play a preview sound when enabling
    if (newValue) {
      playNotificationSound('info', soundVolume);
    }
  };

  const handleVolumeChange = (value) => {
    const newVolume = value[0];
    setSoundVolume(newVolume);
  };

  // Play sound and save to backend when slider is released (onValueCommit)
  const handleVolumeCommit = (value) => {
    const newVolume = value[0];
    playNotificationSound('info', newVolume);

    // Sync to backend (convert to percentage 0-100)
    router.post(
      route('preferences.update'),
      {
        key: 'core_notification_volume',
        value: Math.round(newVolume * 100),
      },
      {
        preserveScroll: true,
        preserveState: true,
        only: [],
      }
    );
  };

  const hasUnread = notifications.some((n) => !n.read_at);
  const isMarkingAll = markAllReadMutation.isPending;

  return (
    <div className="w-96">
      <div className="flex items-center justify-between px-2 py-1.5">
        <DropdownMenuLabel className="px-0">Notifications</DropdownMenuLabel>
        {/* Sound toggle */}
        <Button
          variant="ghost"
          size="sm"
          className="h-8 w-8 p-0"
          onClick={toggleSound}
          title={soundEnabled ? 'Mute notifications' : 'Unmute notifications'}
        >
          {soundEnabled ? (
            <Volume2 className="h-4 w-4 text-primary" />
          ) : (
            <VolumeX className="h-4 w-4 text-muted-foreground" />
          )}
        </Button>
      </div>

      {/* Volume slider - shows when sound is enabled */}
      {soundEnabled && (
        <div className="px-3 pb-2">
          <div className="flex items-center gap-2">
            {/* Dynamic volume icon based on level */}
            {soundVolume === 0 ? (
              <VolumeX className="h-4 w-4 text-muted-foreground flex-shrink-0" />
            ) : soundVolume < 0.4 ? (
              <Volume className="h-4 w-4 text-muted-foreground flex-shrink-0" />
            ) : soundVolume < 0.7 ? (
              <Volume1 className="h-4 w-4 text-muted-foreground flex-shrink-0" />
            ) : (
              <Volume2 className="h-4 w-4 text-muted-foreground flex-shrink-0" />
            )}
            <Slider
              value={[soundVolume]}
              min={0}
              max={1}
              step={0.1}
              onValueChange={handleVolumeChange}
              onValueCommit={handleVolumeCommit}
              className="flex-1"
            />
          </div>
        </div>
      )}
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
          {/* Footer with split actions */}
          <div className="flex items-center gap-2 px-2 py-2">
            <Button
              variant="outline"
              size="sm"
              className="flex-1"
              onClick={handleMarkAllRead}
              disabled={isMarkingAll || !hasUnread}
            >
              {isMarkingAll ? 'Marking...' : 'Mark all read'}
            </Button>
            <Button variant="outline" size="sm" className="flex-1" asChild>
              <Link href={route('notifications.index')}>View all</Link>
            </Button>
          </div>
        </>
      )}
    </div>
  );
}
