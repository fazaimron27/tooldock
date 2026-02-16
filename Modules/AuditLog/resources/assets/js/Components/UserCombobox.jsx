/**
 * Searchable combobox component for selecting users in audit log filters.
 * Uses React Query for data fetching with caching and automatic cleanup.
 */
import { useUserById, useUserSearch } from '@/Hooks/queries/useUserSearch';
import { cn } from '@/Utils/utils';
import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useDebounce } from 'use-debounce';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

export default function UserCombobox({ value, onChange, label = 'User', id = 'user_id' }) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');
  const [debouncedSearch] = useDebounce(search, 300);

  // React Query handles fetching, caching, and cleanup
  const {
    data: searchData,
    isLoading: searchLoading,
    error: searchError,
  } = useUserSearch(debouncedSearch, {
    enabled: open,
    limit: 20,
  });

  // Fetch selected user if not in search results
  const usersIncludeSelected = useMemo(() => {
    if (!value || !searchData?.data) return true;
    return searchData.data.some((u) => String(u.value) === String(value));
  }, [value, searchData?.data]);

  const { data: selectedUserData } = useUserById(value, {
    enabled: !!value && open && !usersIncludeSelected,
  });

  // Combine search results with selected user
  const users = useMemo(() => {
    const searchUsers = searchData?.data || [];
    if (selectedUserData?.data?.[0] && !usersIncludeSelected) {
      return [selectedUserData.data[0], ...searchUsers];
    }
    return searchUsers;
  }, [searchData?.data, selectedUserData?.data, usersIncludeSelected]);

  const loading = searchLoading;
  const error = searchError ? 'Failed to load users. Please try again.' : null;

  const selectedUser = useMemo(() => {
    if (!value) return null;
    return users.find((user) => String(user.value) === String(value)) || null;
  }, [users, value]);

  const handleSelect = (userValue) => {
    const newValue = String(userValue) === String(value) ? '' : String(userValue);
    onChange(newValue);
    setOpen(false);
    setSearch('');
  };

  return (
    <div className="space-y-2">
      {label && <Label htmlFor={id}>{label}</Label>}
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            id={id}
            variant="outline"
            role="combobox"
            aria-expanded={open}
            className="w-full justify-between"
          >
            {selectedUser ? (
              <span className="truncate">{selectedUser.label}</span>
            ) : (
              <span className="text-muted-foreground">All Users</span>
            )}
            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent
          className="w-[var(--radix-popover-trigger-width)] min-w-[200px] max-w-[400px] p-0"
          align="start"
          sideOffset={4}
        >
          <div className="flex items-center border-b px-3">
            <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
            <Input
              placeholder="Search users..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="border-0 focus-visible:ring-0 h-9 flex-1 min-w-0"
            />
          </div>
          <div className="max-h-[300px] overflow-y-auto overflow-x-hidden p-1">
            {error ? (
              <div className="py-6 px-3 text-center">
                <p className="text-sm text-destructive">{error}</p>
                <button
                  type="button"
                  onClick={() => setSearch('')}
                  className="mt-2 text-xs text-primary hover:underline"
                >
                  Try again
                </button>
              </div>
            ) : loading ? (
              <div className="py-6 text-center text-sm text-muted-foreground">Loading...</div>
            ) : users.length === 0 && !loading ? (
              <div className="py-6 text-center text-sm text-muted-foreground">No users found.</div>
            ) : (
              <div className="space-y-1">
                <div
                  role="button"
                  tabIndex={0}
                  className={cn(
                    'flex items-center rounded-sm px-2 py-1.5 text-sm cursor-pointer hover:bg-accent focus:bg-accent focus:outline-none min-w-0',
                    !value || value === '' ? 'bg-accent' : ''
                  )}
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    handleSelect('');
                  }}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      e.preventDefault();
                      handleSelect('');
                    }
                  }}
                >
                  <Check
                    className={cn(
                      'mr-2 h-4 w-4 shrink-0',
                      !value || value === '' ? 'opacity-100' : 'opacity-0'
                    )}
                  />
                  <span className="truncate">All Users</span>
                </div>
                {users.map((user) => {
                  const userValue = String(user.value);
                  const isSelected = String(value) === userValue;

                  return (
                    <div
                      key={user.value}
                      role="button"
                      tabIndex={0}
                      className={cn(
                        'flex items-center rounded-sm px-2 py-1.5 text-sm cursor-pointer hover:bg-accent focus:bg-accent focus:outline-none min-w-0',
                        isSelected ? 'bg-accent' : ''
                      )}
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleSelect(userValue);
                      }}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          handleSelect(userValue);
                        }
                      }}
                    >
                      <span className="truncate flex-1 min-w-0">{user.label}</span>
                      <Check
                        className={cn(
                          'ml-2 h-4 w-4 shrink-0',
                          isSelected ? 'opacity-100' : 'opacity-0'
                        )}
                      />
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </PopoverContent>
      </Popover>
    </div>
  );
}
