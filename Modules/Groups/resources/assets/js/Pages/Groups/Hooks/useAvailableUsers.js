/**
 * Custom hook for fetching and managing available users for adding to a group.
 */
import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useDebounce } from 'use-debounce';

export function useAvailableUsers({
  group,
  addMembersDialog,
  availableUsers: initialAvailableUsers,
}) {
  const page = usePage();
  const [isLoadingAvailableUsers, setIsLoadingAvailableUsers] = useState(false);
  const [search, setSearch] = useState('');
  const [debouncedSearch] = useDebounce(search, 300);

  const availableUsers = page?.props?.availableUsers || initialAvailableUsers;

  useEffect(() => {
    if (!addMembersDialog.isOpen || !group?.id) {
      return;
    }

    const timeoutId = window.setTimeout(() => {
      setIsLoadingAvailableUsers(true);
      router.get(
        route('groups.available-users', { group: group.id }),
        {
          search: debouncedSearch || undefined,
          page: 1,
          per_page: 20,
        },
        {
          only: ['availableUsers', 'defaultPerPage'],
          preserveState: true,
          preserveScroll: true,
          replace: true,
          skipLoadingIndicator: true,
          onFinish: () => {
            setIsLoadingAvailableUsers(false);
          },
        }
      );
    }, 50);

    return () => window.clearTimeout(timeoutId);
  }, [addMembersDialog.isOpen, debouncedSearch, group?.id]);

  useEffect(() => {
    if (!addMembersDialog.isOpen) {
      setSearch('');
    }
  }, [addMembersDialog.isOpen]);

  return {
    availableUsers,
    isLoadingAvailableUsers,
    search,
    setSearch,
  };
}
