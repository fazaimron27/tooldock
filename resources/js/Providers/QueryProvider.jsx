/**
 * React Query Provider component
 * Wraps the application with QueryClientProvider for React Query functionality
 */
import { createQueryClient } from '@/Config/react-query';
import { QueryClientProvider } from '@tanstack/react-query';
import { useMemo } from 'react';

/**
 * React Query Devtools (optional)
 * To enable devtools in development, install the package:
 * npm install -D @tanstack/react-query-devtools
 *
 * Then uncomment the import and component below:
 */
// import { ReactQueryDevtools } from '@tanstack/react-query-devtools';

export function QueryProvider({ children }) {
  /**
   * Create a single QueryClient instance per component tree.
   * Prevents creating a new client on every render for better performance.
   */
  const queryClient = useMemo(() => createQueryClient(), []);

  return (
    <QueryClientProvider client={queryClient}>
      {children}
      {/* Uncomment to enable React Query Devtools in development:
      {import.meta.env.DEV && <ReactQueryDevtools initialIsOpen={false} />}
      */}
    </QueryClientProvider>
  );
}
