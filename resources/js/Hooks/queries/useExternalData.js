/**
 * Example hook for fetching external API data
 * Demonstrates how to use TanStack Query for non-Inertia data fetching
 *
 * This is an example - replace with your actual external API endpoint
 */
import { useApiQuery } from './useApiQuery';

/**
 * Example: Fetch external data from a third-party API
 *
 * @param {Object} options - Query options
 * @returns {Object} Query result from useApiQuery
 */
export function useExternalData(options = {}) {
  return useApiQuery(
    ['external-data'],
    () => {
      // Replace with your actual external API endpoint
      return '/api/external-data';
    },
    {
      staleTime: 5 * 60 * 1000, // Data is fresh for 5 minutes
      gcTime: 10 * 60 * 1000, // Cache for 10 minutes
      refetchOnWindowFocus: false, // Don't refetch on window focus
      ...options,
    }
  );
}
