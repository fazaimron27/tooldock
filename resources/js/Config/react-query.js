/**
 * React Query configuration
 * Configured for use with Inertia.js server-driven architecture
 */
import { QueryClient } from '@tanstack/react-query';

export function createQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: {
        /**
         * With Inertia.js handling most server state, React Query is primarily
         * for external APIs, real-time data, or client-side caching.
         */
        staleTime: 60 * 1000,
        gcTime: 5 * 60 * 1000,
        retry: 1,
        refetchOnWindowFocus: false,
        refetchOnMount: true,
        refetchOnReconnect: true,
      },
      mutations: {
        retry: 1,
      },
    },
  });
}
