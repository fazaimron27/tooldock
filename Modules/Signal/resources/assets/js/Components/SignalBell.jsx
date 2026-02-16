/**
 * SignalBell - Navbar notification bell component
 *
 * Features:
 * - Bell icon with unread count badge
 * - Real-time WebSocket subscription via Laravel Echo
 * - React Query for data fetching with caching and auto-refetch
 * - Dropdown with recent notifications
 */
import { usePage } from '@inertiajs/react';
import { useQueryClient } from '@tanstack/react-query';
import { Bell } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

import {
  notificationKeys,
  useRecentNotifications,
  useUnreadCount,
} from '../Hooks/useNotificationQueries';
import SignalDropdown from './SignalDropdown';

export default function SignalBell() {
  const { auth, signal } = usePage().props;
  const queryClient = useQueryClient();
  const [isOpen, setIsOpen] = useState(false);

  const isOpenRef = useRef(isOpen);
  useEffect(() => {
    isOpenRef.current = isOpen;
  }, [isOpen]);

  // React Query: Unread count with background polling
  const { data: unreadData, error: unreadError } = useUnreadCount({
    // Initialize with server-provided count to avoid flash
    initialData: signal?.unread_count !== undefined ? { count: signal.unread_count } : undefined,
  });

  // React Query: Recent notifications (only fetch when dropdown is open)
  const {
    data: recentData,
    error: recentError,
    refetch: refetchRecent,
  } = useRecentNotifications({
    enabled: isOpen, // Only fetch when dropdown is open
  });

  const unreadCount = unreadData?.count ?? 0;
  const notifications = recentData?.notifications ?? [];
  const error = unreadError || recentError ? 'Failed to load notifications' : null;

  // Handle refresh - invalidate both queries
  const handleRefresh = () => {
    queryClient.invalidateQueries({ queryKey: notificationKeys.all });
  };

  // WebSocket: Real-time notification updates
  useEffect(() => {
    if (!auth?.user?.id || typeof window.Echo === 'undefined') {
      return;
    }

    const channel = window.Echo.private(`App.Models.User.${auth.user.id}`);

    channel.listen('.notification.received', (data) => {
      // Invalidate queries to refetch fresh data
      queryClient.invalidateQueries({ queryKey: notificationKeys.unreadCount() });

      // If dropdown is open, also refetch recent notifications
      if (isOpenRef.current) {
        queryClient.invalidateQueries({ queryKey: notificationKeys.recent() });
      }

      // Optimistic update for immediate feedback on unread count
      if (data.unread_count !== null && data.unread_count !== undefined) {
        queryClient.setQueryData(notificationKeys.unreadCount(), { count: data.unread_count });
      }
    });

    return () => {
      channel.stopListening('.notification.received');
    };
  }, [auth?.user?.id, queryClient]);

  // Sync with Inertia props when they change (page navigation)
  useEffect(() => {
    if (signal?.unread_count !== undefined) {
      queryClient.setQueryData(notificationKeys.unreadCount(), { count: signal.unread_count });
    }
  }, [signal?.unread_count, queryClient]);

  // Refetch recent notifications when dropdown opens
  useEffect(() => {
    if (isOpen) {
      refetchRecent();
    }
  }, [isOpen, refetchRecent]);

  return (
    <DropdownMenu open={isOpen} onOpenChange={setIsOpen}>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <Bell className="h-5 w-5" />
          {unreadCount > 0 && (
            <>
              {/* Ping animation overlay */}
              <span className="absolute -top-1 -right-1 h-5 min-w-5 rounded-full bg-destructive animate-ping opacity-75" />
              {/* Actual badge */}
              <Badge
                variant="destructive"
                className="absolute -top-1 -right-1 h-5 min-w-5 px-1 flex items-center justify-center text-xs"
              >
                {unreadCount >= 100
                  ? '99+'
                  : unreadCount >= 10
                    ? `${Math.floor(unreadCount / 10) * 10}+`
                    : unreadCount}
              </Badge>
            </>
          )}
          <span className="sr-only">
            {unreadCount > 0 ? `${unreadCount} unread notifications` : 'Notifications'}
          </span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="p-0">
        <SignalDropdown notifications={notifications} onRefresh={handleRefresh} error={error} />
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
