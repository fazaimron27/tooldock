/**
 * User Search Query Hook using TanStack Query
 *
 * Shared hook for searching users across the application.
 * Used by UserCombobox, MemberSelect, and other components.
 * Provides caching for repeated searches and automatic cleanup.
 *
 * @module Hooks/queries/useUserSearch
 */
import { useApiQuery } from './useApiQuery';

/**
 * Query key factory for user searches
 */
export const userSearchKeys = {
  all: ['users'],
  search: (term) => [...userSearchKeys.all, 'search', term],
  byId: (id) => [...userSearchKeys.all, 'id', id],
};

/**
 * Search users by term
 *
 * Debouncing should be done at the component level before calling this hook.
 * Results are cached for instant display on repeated searches.
 *
 * @param {string} searchTerm - Search term (name or email)
 * @param {Object} options - Additional query options
 * @param {boolean} options.enabled - Enable query (pass false when dropdown closed)
 * @param {number} options.limit - Max results to return (default: 20)
 * @returns {Object} Query result with users array
 *
 * @example
 * const [debouncedSearch] = useDebounce(search, 300);
 * const { data, isLoading } = useUserSearch(debouncedSearch, {
 *   enabled: open,
 * });
 * // data.data = [{ value: 1, label: 'John Doe' }, ...]
 */
export function useUserSearch(searchTerm = '', options = {}) {
  const { limit = 20, ...queryOptions } = options;

  // Build query params
  const params = new URLSearchParams();
  if (searchTerm) {
    params.append('search', searchTerm);
  }
  params.append('limit', String(limit));

  return useApiQuery(
    userSearchKeys.search(searchTerm),
    () =>
      route('api.users.search', params.toString() ? { _query: Object.fromEntries(params) } : {}),
    {
      staleTime: 60 * 1000, // 1 minute - user list doesn't change often
      cacheTime: 5 * 60 * 1000, // 5 minutes
      enabled: options.enabled !== false,
      ...queryOptions,
    }
  );
}

/**
 * Fetch a specific user by ID
 *
 * Used to load selected user details when value is provided
 * but user isn't in the current search results.
 *
 * @param {string|number} userId - User ID to fetch
 * @param {Object} options - Additional query options
 * @returns {Object} Query result with user data
 *
 * @example
 * const { data } = useUserById(selectedUserId, {
 *   enabled: !!selectedUserId && !usersIncludeSelected,
 * });
 */
export function useUserById(userId, options = {}) {
  return useApiQuery(userSearchKeys.byId(userId), () => route('api.users.search', { id: userId }), {
    staleTime: 5 * 60 * 1000, // 5 minutes
    enabled: !!userId && options.enabled !== false,
    ...options,
  });
}
