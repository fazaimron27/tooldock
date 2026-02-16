/**
 * Notification Query Hooks using TanStack Query
 *
 * React Query hooks for fetching notification data.
 * These hooks provide caching, automatic refetching, and optimistic updates.
 *
 * @module Signal/Hooks/useNotificationQueries
 */
import { useApiMutation } from '@/Hooks/queries/useApiMutation';
import { useApiQuery } from '@/Hooks/queries/useApiQuery';

/**
 * Query key factory for notifications
 * Provides consistent, hierarchical query keys for cache management
 */
export const notificationKeys = {
  all: ['notifications'],
  unreadCount: () => [...notificationKeys.all, 'unread-count'],
  recent: () => [...notificationKeys.all, 'recent'],
  list: (filters) => [...notificationKeys.all, 'list', filters],
};

/**
 * Fetch unread notification count
 *
 * @param {Object} options - Additional query options
 * @returns {Object} Query result with count data
 *
 * @example
 * const { data, isLoading } = useUnreadCount();
 * // data.count = 5
 */
export function useUnreadCount(options = {}) {
  return useApiQuery(notificationKeys.unreadCount(), route('notifications.unread-count'), {
    staleTime: 30 * 1000, // 30 seconds - matches polling interval
    refetchInterval: 30 * 1000, // Background polling
    refetchOnWindowFocus: true,
    ...options,
  });
}

/**
 * Fetch recent notifications for dropdown
 *
 * @param {Object} options - Additional query options
 * @returns {Object} Query result with notifications array
 *
 * @example
 * const { data, isLoading } = useRecentNotifications();
 * // data.notifications = [...]
 */
export function useRecentNotifications(options = {}) {
  return useApiQuery(notificationKeys.recent(), route('notifications.recent'), {
    staleTime: 10 * 1000, // 10 seconds
    refetchOnWindowFocus: true,
    ...options,
  });
}

/**
 * Mark a single notification as read
 *
 * @param {Object} options - Additional mutation options
 * @returns {Object} Mutation result
 *
 * @example
 * const mutation = useMarkAsRead();
 * mutation.mutate(notificationId);
 */
export function useMarkAsRead(options = {}) {
  return useApiMutation((id) => route('notifications.read', { notification: id }), 'post', {
    invalidateQueries: notificationKeys.all,
    ...options,
  });
}

/**
 * Mark all notifications as read
 *
 * @param {Object} options - Additional mutation options
 * @returns {Object} Mutation result
 *
 * @example
 * const mutation = useMarkAllAsRead();
 * mutation.mutate();
 */
export function useMarkAllAsRead(options = {}) {
  return useApiMutation(route('notifications.read-all'), 'post', {
    invalidateQueries: notificationKeys.all,
    ...options,
  });
}

/**
 * Bulk mark notifications as read
 *
 * @param {Object} options - Additional mutation options
 * @returns {Object} Mutation result
 *
 * @example
 * const mutation = useBulkMarkAsRead();
 * mutation.mutate({ ids: ['uuid1', 'uuid2'] });
 */
export function useBulkMarkAsRead(options = {}) {
  return useApiMutation(route('notifications.bulk-read'), 'post', {
    invalidateQueries: notificationKeys.all,
    ...options,
  });
}

/**
 * Delete a single notification
 *
 * @param {Object} options - Additional mutation options
 * @returns {Object} Mutation result
 *
 * @example
 * const mutation = useDeleteNotification();
 * mutation.mutate(notificationId);
 */
export function useDeleteNotification(options = {}) {
  return useApiMutation((id) => route('notifications.destroy', { notification: id }), 'delete', {
    invalidateQueries: notificationKeys.all,
    ...options,
  });
}

/**
 * Bulk delete notifications
 *
 * @param {Object} options - Additional mutation options
 * @returns {Object} Mutation result
 *
 * @example
 * const mutation = useBulkDelete();
 * mutation.mutate({ ids: ['uuid1', 'uuid2'] });
 */
export function useBulkDelete(options = {}) {
  return useApiMutation(route('notifications.bulk-destroy'), 'delete', {
    invalidateQueries: notificationKeys.all,
    ...options,
  });
}
