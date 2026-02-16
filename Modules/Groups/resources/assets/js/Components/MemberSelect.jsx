/**
 * Searchable multi-select component for selecting group members
 * Uses React Query for data fetching with caching and automatic cleanup.
 */
import { useUserSearch } from '@/Hooks/queries/useUserSearch';
import { cn } from '@/Utils/utils';
import { Check, ChevronsUpDown, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';
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
  const [debouncedSearch] = useDebounce(search, 300);

  const { data: searchData, isLoading: loading } = useUserSearch(debouncedSearch, {
    enabled: open,
    limit: 20,
  });
  const options = useMemo(() => {
    const users = searchData?.data || [];
    return users.map((user) => ({
      label: user.name || user.label,
      value: user.value,
      email: user.email,
      fullLabel: user.label,
    }));
  }, [searchData?.data]);

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
