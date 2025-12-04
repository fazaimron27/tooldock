/**
 * Hook for debounced search functionality
 * Provides a reusable pattern for search inputs that need debouncing
 *
 * @param {string} initialValue - Initial search value
 * @param {number} delay - Debounce delay in milliseconds (default: 300)
 * @returns {Object} { search, setSearch, debouncedSearch }
 */
import { useState } from 'react';
import { useDebounce } from 'use-debounce';

export function useDebouncedSearch(initialValue = '', delay = 300) {
  const [search, setSearch] = useState(initialValue);
  const [debouncedSearch] = useDebounce(search, delay);

  return {
    search,
    setSearch,
    debouncedSearch,
  };
}
