/**
 * Example hook for client-side cached data
 * Demonstrates how to use TanStack Query for data that benefits from caching
 *
 * This is an example - replace with your actual use case
 */
import { useApiQuery } from './useApiQuery';

/**
 * Example: Fetch data that benefits from client-side caching
 *
 * @param {string} id - Resource identifier
 * @param {Object} options - Query options
 * @returns {Object} Query result from useApiQuery
 */
export function useCachedData(id, options = {}) {
  return useApiQuery(
    ['cached-data', id],
    () => {
      // Replace with your actual endpoint
      return `/api/cached-data/${id}`;
    },
    {
      staleTime: 2 * 60 * 1000, // Data is fresh for 2 minutes
      gcTime: 5 * 60 * 1000, // Cache for 5 minutes
      enabled: !!id, // Only fetch if id is provided
      ...options,
    }
  );
}
