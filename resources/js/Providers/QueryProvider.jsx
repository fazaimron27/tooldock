/**
 * React Query Provider component
 * Wraps the application with QueryClientProvider for React Query functionality
 */
import { createQueryClient } from '@/Config/react-query';
import { QueryClientProvider } from '@tanstack/react-query';
import { useMemo } from 'react';

export function QueryProvider({ children }) {
  /**
   * Create a single QueryClient instance per component tree.
   * Prevents creating a new client on every render for better performance.
   */
  const queryClient = useMemo(() => createQueryClient(), []);

  return (
    <QueryClientProvider client={queryClient}>
      {children}
      {/* Devtools can be added later if needed - install @tanstack/react-query-devtools as devDependency */}
    </QueryClientProvider>
  );
}
