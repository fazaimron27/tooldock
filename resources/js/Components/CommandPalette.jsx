/**
 * Command Palette component for quick navigation
 *
 * Provides a modal command palette (Cmd+K) for searching and navigating
 * to pages across the application. Reads commands from page props for
 * searchable display, grouped hierarchically.
 *
 * @param {boolean} open - Whether the palette is open
 * @param {(open: boolean) => void} onOpenChange - Callback when open state changes
 */
import { getIcon } from '@/Utils/iconResolver';
import { router, usePage } from '@inertiajs/react';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden';
import { FileText } from 'lucide-react';
import { useCallback, useMemo } from 'react';

import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandShortcut,
} from '@/Components/ui/command';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/Components/ui/dialog';

/**
 * Handle special action commands (not routes).
 */
const actionHandlers = {
  logout: () => {
    router.post(route('logout'));
  },
};

export default function CommandPalette({ open, onOpenChange }) {
  const { commands } = usePage().props;

  const groupedCommands = useMemo(() => {
    const grouped = {};

    Object.entries(commands || {}).forEach(([group, groupCommands]) => {
      const validCommands = [];

      groupCommands.forEach((cmd) => {
        if (cmd.url) {
          validCommands.push({
            type: 'command',
            group,
            label: cmd.label,
            url: cmd.url,
            newTab: cmd.newTab || false,
            description: cmd.description || null,
            icon: cmd.icon,
            keywords: cmd.keywords || [],
          });
        } else if (cmd.route && route().has(cmd.route)) {
          validCommands.push({
            type: 'command',
            group,
            label: cmd.label,
            route: cmd.route,
            description: cmd.description || null,
            icon: cmd.icon,
            keywords: cmd.keywords || [],
          });
        } else if (cmd.action) {
          validCommands.push({
            type: 'command',
            group,
            label: cmd.label,
            action: cmd.action,
            description: cmd.description || null,
            icon: cmd.icon,
            keywords: cmd.keywords || [],
          });
        }
      });

      if (validCommands.length > 0) {
        grouped[group] = validCommands;
      }
    });

    return grouped;
  }, [commands]);

  const handleSelect = useCallback(
    (item) => {
      onOpenChange(false);

      if (item.url) {
        if (item.newTab) {
          window.open(item.url, '_blank');
        } else {
          window.location.href = item.url;
        }
      } else if (item.action && actionHandlers[item.action]) {
        actionHandlers[item.action]();
      } else if (item.route) {
        router.visit(route(item.route));
      }
    },
    [onOpenChange]
  );

  const getSearchValue = (item) => {
    const parts = [item.label, item.group || ''];
    if (item.keywords) {
      parts.push(...item.keywords);
    }
    return parts.join(' ');
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="overflow-hidden p-0 max-w-2xl">
        <VisuallyHidden>
          <DialogTitle>Command Palette</DialogTitle>
          <DialogDescription>Search and navigate to pages across the application</DialogDescription>
        </VisuallyHidden>
        <Command className="[&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:font-medium [&_[cmdk-group-heading]]:text-muted-foreground [&_[cmdk-group]:not([hidden])_~[cmdk-group]]:pt-0 [&_[cmdk-group]]:px-2 [&_[cmdk-input-wrapper]_svg]:h-5 [&_[cmdk-input-wrapper]_svg]:w-5 [&_[cmdk-input]]:h-12 [&_[cmdk-item]]:px-2 [&_[cmdk-item]]:py-3 [&_[cmdk-item]_svg]:h-5 [&_[cmdk-item]_svg]:w-5">
          <CommandInput placeholder="Search or jump to..." />
          <CommandList className="max-h-[400px]">
            <CommandEmpty>No results found.</CommandEmpty>
            {Object.entries(groupedCommands).map(([groupName, items]) => (
              <CommandGroup key={groupName} heading={groupName}>
                {items.map((item, itemIndex) => {
                  const Icon = getIcon(item.icon) || FileText;
                  const key = item.route || item.url || `${item.action}-${itemIndex}`;
                  const isDestructive = item.action === 'logout';
                  return (
                    <CommandItem
                      key={key}
                      value={getSearchValue(item)}
                      onSelect={() => handleSelect(item)}
                      className={`cursor-pointer py-3 gap-3 ${isDestructive ? 'text-destructive' : ''}`}
                    >
                      <Icon className="h-4 w-4 shrink-0" />
                      <div className="flex flex-col flex-1">
                        <span className="font-medium">{item.label}</span>
                        {item.description && (
                          <span className="text-xs text-muted-foreground">{item.description}</span>
                        )}
                      </div>
                    </CommandItem>
                  );
                })}
              </CommandGroup>
            ))}
          </CommandList>
          {/* Footer with keyboard hints */}
          <div className="flex items-center justify-between border-t px-3 py-2 text-xs text-muted-foreground">
            <div className="flex items-center gap-3">
              <span className="flex items-center gap-1">
                <kbd className="rounded border bg-muted px-1.5 py-0.5 font-mono">↑</kbd>
                <kbd className="rounded border bg-muted px-1.5 py-0.5 font-mono">↓</kbd>
                <span>to navigate</span>
              </span>
              <span className="flex items-center gap-1">
                <kbd className="rounded border bg-muted px-1.5 py-0.5 font-mono">↵</kbd>
                <span>to select</span>
              </span>
              <span className="flex items-center gap-1">
                <kbd className="rounded border bg-muted px-1.5 py-0.5 font-mono">⌘K</kbd>
                <span>to close</span>
              </span>
            </div>
          </div>
        </Command>
      </DialogContent>
    </Dialog>
  );
}
