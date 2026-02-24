import { ChevronDown } from 'lucide-react';
import { useState } from 'react';

/**
 * Collapsible section wrapper used in the settings panel.
 */
export default function SettingsSection({ icon: Icon, title, children, defaultOpen = false }) {
  const [open, setOpen] = useState(defaultOpen);
  return (
    <div className="border rounded-lg">
      <button
        type="button"
        onClick={() => setOpen(!open)}
        className="flex w-full items-center gap-2 px-4 py-3 text-sm font-semibold hover:bg-accent/50 transition-colors rounded-lg"
      >
        <ChevronDown className={`h-4 w-4 transition-transform ${open ? '' : '-rotate-90'}`} />
        {Icon && <Icon className="h-4 w-4 text-muted-foreground" />}
        <span>{title}</span>
      </button>
      {open && <div className="px-4 pb-4 space-y-4">{children}</div>}
    </div>
  );
}
