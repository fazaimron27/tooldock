/**
 * Base hook for API mutations using TanStack Query
 * Provides a consistent interface for POST/PUT/PATCH/DELETE operations
 * on external APIs or non-Inertia endpoints
 */
import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

/**
 * Custom hook for API mutations
 *
 * @param {string|Function} endpoint - API endpoint URL or function that returns URL
 * @param {string} method - HTTP method (post, put, patch, delete)
 * @param {Object} options - Additional TanStack Query mutation options
 * @returns {Object} Mutation result object from useMutation
 */
export function useApiMutation(endpoint, method = 'post', options = {}) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data) => {
      const url = typeof endpoint === 'function' ? endpoint() : endpoint;
      const response = await axios[method](url, data, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
      });
      return response.data;
    },
    onSuccess: (data, variables, context) => {
      if (options.invalidateQueries) {
        queryClient.invalidateQueries({ queryKey: options.invalidateQueries });
      }
      if (options.onSuccess) {
        options.onSuccess(data, variables, context);
      }
    },
    ...options,
  });
}
