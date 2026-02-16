/**
 * SearchableSelect - A searchable dropdown component
 *
 * Features:
 * - Search/filter functionality
 * - Support for hierarchical items (parent/children)
 * - Color indicators for categories
 * - Icon support
 * - Keyboard navigation
 *
 * Based on the WalletForm currency select pattern
 */
import { cn } from '@/Utils/utils';
import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

/**
 * @typedef {Object} SelectOption
 * @property {string} value - Unique value for the option
 * @property {string} label - Display label
 * @property {string} [color] - Optional color for indicator
 * @property {string} [description] - Optional description/subtitle
 * @property {string} [parentValue] - Parent value for hierarchical grouping
 * @property {React.ReactNode} [icon] - Optional icon element
 * @property {boolean} [disabled] - Whether this option is disabled
 */

/**
 * SearchableSelect Component
 *
 * @param {Object} props
 * @param {string} props.value - Currently selected value
 * @param {function} props.onValueChange - Callback when value changes
 * @param {SelectOption[]} props.options - Array of options
 * @param {string} [props.placeholder] - Placeholder text when no value selected
 * @param {string} [props.searchPlaceholder] - Placeholder for search input
 * @param {string} [props.emptyMessage] - Message when no options match search
 * @param {boolean} [props.disabled] - Disable the select
 * @param {string} [props.className] - Additional classes for trigger button
 * @param {string} [props.contentClassName] - Additional classes for popover content
 * @param {boolean} [props.showColors] - Show color indicators
 * @param {boolean} [props.hierarchical] - Enable hierarchical display (parent/children)
 * @param {string} [props.error] - Error state (adds border-destructive)
 */
export default function SearchableSelect({
  value,
  onValueChange,
  options = [],
  placeholder = 'Select...',
  searchPlaceholder = 'Search...',
  emptyMessage = 'No results found.',
  disabled = false,
  className,
  contentClassName,
  showColors = false,
  hierarchical = false,
  error,
}) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');

  const selectedOption = useMemo(() => {
    return options.find((opt) => String(opt.value) === String(value));
  }, [options, value]);

  const filteredOptions = useMemo(() => {
    if (!search.trim()) return options;
    const searchLower = search.toLowerCase();
    return options.filter(
      (opt) =>
        opt.label.toLowerCase().includes(searchLower) ||
        opt.description?.toLowerCase().includes(searchLower)
    );
  }, [options, search]);

  const groupedOptions = useMemo(() => {
    if (!hierarchical) return { roots: filteredOptions, children: {} };

    const searchLower = search.toLowerCase().trim();

    if (searchLower) {
      const matchingOptions = options.filter(
        (opt) =>
          opt.label.toLowerCase().includes(searchLower) ||
          opt.description?.toLowerCase().includes(searchLower)
      );

      const parentValuesOfMatchingChildren = new Set();
      matchingOptions.forEach((opt) => {
        if (opt.parentValue) {
          parentValuesOfMatchingChildren.add(opt.parentValue);
        }
      });

      const allParents = options.filter((opt) => !opt.parentValue);
      const visibleParents = allParents.filter(
        (parent) =>
          parent.label.toLowerCase().includes(searchLower) ||
          parent.description?.toLowerCase().includes(searchLower) ||
          parentValuesOfMatchingChildren.has(String(parent.value))
      );

      const matchingChildren = matchingOptions.filter((opt) => opt.parentValue);

      const children = {};
      matchingChildren.forEach((child) => {
        if (!children[child.parentValue]) {
          children[child.parentValue] = [];
        }
        children[child.parentValue].push(child);
      });

      return { roots: visibleParents, children };
    }

    const roots = [];
    const children = {};

    options.forEach((opt) => {
      if (opt.parentValue) {
        if (!children[opt.parentValue]) {
          children[opt.parentValue] = [];
        }
        children[opt.parentValue].push(opt);
      } else {
        roots.push(opt);
      }
    });

    return { roots, children };
  }, [options, filteredOptions, hierarchical, search]);

  const handleSelect = (optionValue) => {
    onValueChange(optionValue);
    setOpen(false);
    setSearch('');
  };

  const handleOpenChange = (isOpen) => {
    setOpen(isOpen);
    if (!isOpen) setSearch('');
  };

  const renderOption = (option, isChild = false) => {
    const isSelected = String(value) === String(option.value);
    const isDisabled = option.disabled === true;

    return (
      <div
        key={option.value}
        role="option"
        aria-selected={isSelected}
        aria-disabled={isDisabled}
        tabIndex={isDisabled ? -1 : 0}
        className={cn(
          'flex items-center rounded-sm px-2 py-1.5 text-sm',
          isDisabled
            ? 'opacity-50 cursor-not-allowed'
            : 'cursor-pointer hover:bg-accent focus:bg-accent focus:outline-none',
          isSelected && !isDisabled && 'bg-accent',
          isChild && 'pl-6'
        )}
        onClick={(e) => {
          e.preventDefault();
          e.stopPropagation();
          if (!isDisabled) {
            handleSelect(option.value);
          }
        }}
        onKeyDown={(e) => {
          if (!isDisabled && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            handleSelect(option.value);
          }
        }}
      >
        <Check className={cn('mr-2 h-4 w-4 shrink-0', isSelected ? 'opacity-100' : 'opacity-0')} />
        {showColors && option.color && (
          <span
            className="mr-2 h-3 w-3 rounded-full shrink-0"
            style={{ backgroundColor: option.color }}
          />
        )}
        {option.icon && <span className="mr-2 shrink-0">{option.icon}</span>}
        <div className="flex flex-col min-w-0 flex-1">
          <span className="truncate">{option.label}</span>
          {option.description && (
            <span
              className={cn(
                'text-xs truncate',
                isDisabled ? 'text-amber-500' : 'text-muted-foreground'
              )}
            >
              {option.description}
            </span>
          )}
        </div>
      </div>
    );
  };

  const renderHierarchicalOptions = () => {
    const { roots, children } = groupedOptions;

    if (roots.length === 0) {
      return <div className="py-6 text-center text-sm text-muted-foreground">{emptyMessage}</div>;
    }

    return roots.map((parent) => {
      const parentChildren = children[parent.value] || [];
      const hasVisibleChildren = parentChildren.length > 0;

      return (
        <div key={parent.value}>
          {renderOption(parent)}
          {hasVisibleChildren && (
            <div className="ml-2 border-l border-muted pl-1">
              {parentChildren.map((child) => renderOption(child, true))}
            </div>
          )}
        </div>
      );
    });
  };

  const renderFlatOptions = () => {
    if (filteredOptions.length === 0) {
      return <div className="py-6 text-center text-sm text-muted-foreground">{emptyMessage}</div>;
    }

    return filteredOptions.map((option) => renderOption(option));
  };

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          disabled={disabled}
          className={cn(
            'w-full justify-between font-normal bg-transparent',
            !value && 'text-muted-foreground',
            error && 'border-destructive',
            className
          )}
        >
          <span className="flex items-center gap-2 truncate">
            {selectedOption ? (
              <>
                {showColors && selectedOption.color && (
                  <span
                    className="h-3 w-3 rounded-full shrink-0"
                    style={{ backgroundColor: selectedOption.color }}
                  />
                )}
                {selectedOption.icon && <span className="shrink-0">{selectedOption.icon}</span>}
                <span className="truncate">{selectedOption.label}</span>
              </>
            ) : (
              placeholder
            )}
          </span>
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className={cn('w-[400px] p-0', contentClassName)} align="start">
        {/* Search Input */}
        <div className="flex items-center border-b px-3">
          <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
          <Input
            placeholder={searchPlaceholder}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="border-0 shadow-none focus-visible:ring-0 h-9 flex-1 min-w-0"
          />
        </div>

        {/* Options List */}
        <div className="max-h-[300px] overflow-y-auto overflow-x-hidden p-1">
          {hierarchical ? renderHierarchicalOptions() : renderFlatOptions()}
        </div>
      </PopoverContent>
    </Popover>
  );
}

/**
 * Helper to convert wallet array to options format
 */
export function walletsToOptions(wallets, options = {}) {
  const { showCurrency = true, showBalance = false } = options;

  return wallets.map((wallet) => ({
    value: String(wallet.id),
    label: showCurrency ? `${wallet.name} (${wallet.currency})` : wallet.name,
    description: showBalance ? `Balance: ${wallet.balance}` : wallet.description,
    color: wallet.color,
  }));
}

/**
 * Helper to convert categories array to options format (with hierarchy)
 */
export function categoriesToOptions(categories, options = {}) {
  const { showDescription = false } = options;

  return categories.map((category) => ({
    value: String(category.id),
    label: category.name,
    description: showDescription ? category.description : undefined,
    color: category.color,
    parentValue: category.parent_id ? String(category.parent_id) : undefined,
  }));
}

/**
 * Helper to convert simple key-value objects to options
 */
export function objectToOptions(obj, options = {}) {
  const { labelFormatter = (key, value) => `${key} - ${value}` } = options;

  return Object.entries(obj).map(([key, value]) => ({
    value: key,
    label:
      typeof value === 'object'
        ? labelFormatter(key, value.label || value.name)
        : labelFormatter(key, value),
    color: typeof value === 'object' ? value.color : undefined,
    description: typeof value === 'object' ? value.description : undefined,
  }));
}
