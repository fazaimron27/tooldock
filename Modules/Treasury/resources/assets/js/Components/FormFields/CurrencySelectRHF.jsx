/**
 * Currency Select field component for React Hook Form
 * Searchable currency dropdown with RHF Controller integration
 */
import { cn } from '@/Utils/utils';
import { usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Controller } from 'react-hook-form';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

export default function CurrencySelectRHF({
  name,
  control,
  label,
  required = false,
  helperText,
  className,
  id,
}) {
  const { currency_map: currencyMap = {} } = usePage().props;
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');

  const inputId = id || name;

  const currencyOptions = Object.entries(currencyMap).map(([code, info]) => ({
    value: code,
    label: `${code} - ${info.label}`,
  }));

  const filteredCurrencies = useMemo(() => {
    if (!search) return currencyOptions;
    const searchLower = search.toLowerCase();
    return currencyOptions.filter((opt) => opt.label.toLowerCase().includes(searchLower));
  }, [currencyOptions, search]);

  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => {
        const selectedLabel =
          currencyOptions.find((opt) => opt.value === field.value)?.label || 'Select currency...';

        return (
          <div className={cn('space-y-2', className)}>
            {label && (
              <Label htmlFor={inputId}>
                {label}
                {required && <span className="text-destructive ml-1">*</span>}
              </Label>
            )}
            <Popover
              open={open}
              onOpenChange={(isOpen) => {
                setOpen(isOpen);
                if (!isOpen) setSearch('');
              }}
            >
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  role="combobox"
                  aria-expanded={open}
                  className={cn(
                    'w-full justify-between font-normal',
                    !field.value && 'text-muted-foreground',
                    error && 'border-destructive'
                  )}
                >
                  {selectedLabel}
                  <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-[400px] p-0" align="start">
                <div className="flex items-center border-b px-3">
                  <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
                  <Input
                    placeholder="Search currency..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border-0 shadow-none focus-visible:ring-0 h-9 flex-1 min-w-0"
                  />
                </div>
                <div className="max-h-[300px] overflow-y-auto overflow-x-hidden p-1">
                  {filteredCurrencies.length === 0 ? (
                    <div className="py-6 text-center text-sm text-muted-foreground">
                      No currency found.
                    </div>
                  ) : (
                    <div className="space-y-1">
                      {filteredCurrencies.map((option) => (
                        <div
                          key={option.value}
                          role="button"
                          tabIndex={0}
                          className={cn(
                            'flex items-center rounded-sm px-2 py-1.5 text-sm cursor-pointer hover:bg-accent focus:bg-accent focus:outline-none min-w-0',
                            field.value === option.value ? 'bg-accent' : ''
                          )}
                          onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            field.onChange(option.value);
                            setOpen(false);
                            setSearch('');
                          }}
                          onKeyDown={(e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                              e.preventDefault();
                              field.onChange(option.value);
                              setOpen(false);
                              setSearch('');
                            }
                          }}
                        >
                          <Check
                            className={cn(
                              'mr-2 h-4 w-4 shrink-0',
                              field.value === option.value ? 'opacity-100' : 'opacity-0'
                            )}
                          />
                          <span className="truncate">{option.label}</span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </PopoverContent>
            </Popover>
            {error && <p className="text-sm text-destructive">{error.message}</p>}
            {helperText && !error && <p className="text-xs text-muted-foreground">{helperText}</p>}
          </div>
        );
      }}
    />
  );
}
