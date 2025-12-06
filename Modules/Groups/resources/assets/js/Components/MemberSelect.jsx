/**
 * Searchable multi-select component for selecting group members
 * Fetches users from API on search to avoid loading all users.
 * Uses Popover + Input + Checkbox for better UX with large lists
 */
import { API_BASEURL, API_VERSION } from '@/Utils/constants';
import { cn } from '@/Utils/utils';
import axios from 'axios';
import { Check, ChevronsUpDown, Search, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useDebounce } from 'use-debounce';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

export default function MemberSelect({
  value = [],
  onChange,
  placeholder = 'Select members...',
  emptyMessage = 'No members found.',
  className,
}) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [debouncedSearch] = useDebounce(search, 300);

  /**
   * Fetch users from API when search term changes or popover opens.
   * Uses debounced search to reduce API calls and AbortController for request cancellation.
   */
  useEffect(() => {
    if (!open) {
      return;
    }

    let cancelled = false;
    /**
     * AbortController is a browser API available in modern browsers.
     * ESLint doesn't recognize it, so we disable the no-undef warning.
     */
    // eslint-disable-next-line no-undef
    const controller = new AbortController();

    const fetchUsers = async () => {
      setLoading(true);
      try {
        const params = new URLSearchParams();
        if (debouncedSearch) {
          params.append('search', debouncedSearch);
        }
        params.append('limit', '20');

        const response = await axios.get(
          `${API_BASEURL}/${API_VERSION}/users/search?${params.toString()}`,
          {
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            withCredentials: true,
            signal: controller.signal,
          }
        );

        if (!cancelled && response.data?.data) {
          setUsers(response.data.data);
        }
      } catch (error) {
        if (axios.isCancel(error) || cancelled) {
          return;
        }
        console.error('Failed to fetch users:', error);
        if (!cancelled) {
          setUsers([]);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    };

    fetchUsers();

    return () => {
      cancelled = true;
      controller.abort();
    };
  }, [debouncedSearch, open]);

  /**
   * Load selected users by ID when values are provided but not present in current user list.
   * Fetches missing users in parallel to ensure all selected users are displayed.
   */
  useEffect(() => {
    if (!value.length || !open) {
      return;
    }

    const missingUserIds = value.filter(
      (userId) => !users.find((u) => String(u.value) === String(userId))
    );

    if (missingUserIds.length === 0) {
      return;
    }

    let cancelled = false;
    /**
     * AbortController is a browser API available in modern browsers.
     * ESLint doesn't recognize it, so we disable the no-undef warning.
     */
    // eslint-disable-next-line no-undef
    const controllers = missingUserIds.map(() => new AbortController());

    const fetchSelectedUsers = async () => {
      try {
        const promises = missingUserIds.map((userId, index) =>
          axios.get(`${API_BASEURL}/${API_VERSION}/users/search?id=${userId}`, {
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            withCredentials: true,
            signal: controllers[index].signal,
          })
        );

        const responses = await Promise.all(promises);

        if (!cancelled) {
          const selectedUsers = responses.map((res) => res.data?.data?.[0]).filter(Boolean);

          setUsers((prev) => {
            const existing = prev.map((u) => u.value);
            const newUsers = selectedUsers.filter((u) => !existing.includes(u.value));
            return [...newUsers, ...prev];
          });
        }
      } catch (error) {
        if (axios.isCancel(error) || cancelled) {
          return;
        }
        console.error('Failed to fetch selected users:', error);
      }
    };

    fetchSelectedUsers();

    return () => {
      cancelled = true;
      controllers.forEach((c) => c.abort());
    };
  }, [value, users, open]);

  const options = useMemo(() => {
    return users.map((user) => ({
      label: user.name || user.label,
      value: user.value,
      email: user.email,
      fullLabel: user.label,
    }));
  }, [users]);

  const selectedOptions = useMemo(() => {
    return options.filter((option) => value.includes(option.value));
  }, [options, value]);

  const handleToggle = (optionValue) => {
    const newValue = value.includes(optionValue)
      ? value.filter((v) => v !== optionValue)
      : [...value, optionValue];
    onChange(newValue);
  };

  const handleRemove = (optionValue, e) => {
    e.stopPropagation();
    onChange(value.filter((v) => v !== optionValue));
  };

  return (
    <div className={cn('space-y-2', className)}>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            role="combobox"
            aria-expanded={open}
            className="w-full justify-between min-h-10 h-auto"
          >
            <div className="flex flex-wrap gap-1 flex-1">
              {selectedOptions.length === 0 ? (
                <span className="text-muted-foreground">{placeholder}</span>
              ) : (
                selectedOptions.map((option) => (
                  <Badge key={option.value} variant="secondary" className="mr-1 mb-1">
                    {option.label}
                    <span
                      role="button"
                      tabIndex={0}
                      className="ml-1 ring-offset-background rounded-full outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 cursor-pointer"
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          handleRemove(option.value, e);
                        }
                      }}
                      onMouseDown={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                      }}
                      onClick={(e) => handleRemove(option.value, e)}
                    >
                      <X className="h-3 w-3 text-muted-foreground hover:text-foreground" />
                    </span>
                  </Badge>
                ))
              )}
            </div>
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
              placeholder="Search members..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="border-0 focus-visible:ring-0 h-9 flex-1 min-w-0"
            />
          </div>
          <div className="max-h-[300px] overflow-y-auto overflow-x-hidden p-1">
            {loading ? (
              <div className="py-6 text-center text-sm text-muted-foreground">Loading...</div>
            ) : options.length === 0 && !loading ? (
              <div className="py-6 text-center text-sm text-muted-foreground">{emptyMessage}</div>
            ) : (
              <div className="space-y-1">
                {options.map((option) => {
                  const isSelected = value.includes(option.value);
                  return (
                    <div
                      key={option.value}
                      role="button"
                      tabIndex={0}
                      className="flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent cursor-pointer min-w-0"
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleToggle(option.value);
                      }}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          handleToggle(option.value);
                        }
                      }}
                    >
                      <Checkbox
                        id={`member-${option.value}`}
                        checked={isSelected}
                        onCheckedChange={() => handleToggle(option.value)}
                        className="shrink-0"
                      />
                      <Label
                        htmlFor={`member-${option.value}`}
                        className="flex-1 cursor-pointer text-sm font-normal min-w-0"
                      >
                        <div className="truncate">{option.label}</div>
                        {option.email && option.email !== option.label && (
                          <div className="text-xs text-muted-foreground truncate">
                            {option.email}
                          </div>
                        )}
                      </Label>
                      {isSelected && <Check className="h-4 w-4 text-primary shrink-0" />}
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
