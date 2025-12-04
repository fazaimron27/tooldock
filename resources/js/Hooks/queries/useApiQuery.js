/**
 * Base hook for API queries using TanStack Query
 * Provides a consistent interface for fetching data from external APIs
 * or non-Inertia endpoints
 */
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

/**
 * Custom hook for API queries
 *
 * @param {string|Array} queryKey - Unique identifier for the query
 * @param {string|Function} endpoint - API endpoint URL or function that returns URL
 * @param {Object} options - Additional TanStack Query options
 * @returns {Object} Query result object from useQuery
 */
export function useApiQuery(queryKey, endpoint, options = {}) {
  return useQuery({
    queryKey: Array.isArray(queryKey) ? queryKey : [queryKey],
    queryFn: async () => {
      const url = typeof endpoint === 'function' ? endpoint() : endpoint;
      const response = await axios.get(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      });
      return response.data;
    },
    ...options,
  });
}
