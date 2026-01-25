/**
 * SignalBell - Navbar notification bell component
 *
 * Features:
 * - Bell icon with unread count badge
 * - Real-time WebSocket subscription via Laravel Echo
 * - Polling fallback (30s) to trigger middleware for auto-lock detection
 * - Dropdown with recent notifications
 */
import { usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

import SignalDropdown from './SignalDropdown';

const POLLING_INTERVAL = 30000;

export default function SignalBell() {
  const { auth, signal } = usePage().props;
  const [unreadCount, setUnreadCount] = useState(signal?.unread_count ?? 0);
  const [notifications, setNotifications] = useState([]);
  const [isOpen, setIsOpen] = useState(false);
  const [error, setError] = useState(null);

  const isOpenRef = useRef(isOpen);
  useEffect(() => {
    isOpenRef.current = isOpen;
  }, [isOpen]);

  const fetchRecentNotifications = useCallback(async () => {
    try {
      setError(null);
      const response = await fetch(route('notifications.recent'));
      if (!response.ok) throw new Error('Failed to fetch');
      const data = await response.json();
      setNotifications(data.notifications);
    } catch (err) {
      setError('Failed to load notifications');
      console.error('Failed to fetch notifications:', err);
    }
  }, []);

  const fetchUnreadCount = useCallback(async () => {
    try {
      const response = await fetch(route('notifications.unread-count'));
      if (!response.ok) throw new Error('Failed to fetch');
      const data = await response.json();
      setUnreadCount(data.count);
    } catch (err) {
      console.error('Failed to fetch unread count:', err);
    }
  }, []);

  const handleRefresh = useCallback(() => {
    fetchRecentNotifications();
    fetchUnreadCount();
  }, [fetchRecentNotifications, fetchUnreadCount]);

  useEffect(() => {
    if (!auth?.user?.id || typeof window.Echo === 'undefined') {
      return;
    }

    const channel = window.Echo.private(`App.Models.User.${auth.user.id}`);

    channel.listen('.notification.received', (data) => {
      setUnreadCount((prev) => prev + 1);
      if (isOpenRef.current) {
        const notification = {
          id: data.id,
          type: data.type,
          title: data.title,
          message: data.message,
          action_url: data.url,
          module_source: data.module_source,
          created_at: data.created_at,
          created_at_human: 'Just now',
          read_at: null,
        };
        setNotifications((prev) => [notification, ...prev].slice(0, 5));
      }
    });

    return () => {
      channel.stopListening('.notification.received');
    };
  }, [auth?.user?.id]);

  useEffect(() => {
    if (signal?.unread_count !== undefined) {
      setUnreadCount(signal.unread_count);
    }
  }, [signal?.unread_count]);

  useEffect(() => {
    fetchUnreadCount();

    const intervalId = window.setInterval(fetchUnreadCount, POLLING_INTERVAL);

    return () => window.clearInterval(intervalId);
  }, [fetchUnreadCount]);

  useEffect(() => {
    if (isOpen) {
      fetchRecentNotifications();
    }
  }, [isOpen, fetchRecentNotifications]);

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
