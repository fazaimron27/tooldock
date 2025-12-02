/**
 * Modern application logo component with icon and styled text
 * Reads the app name and logo icon from Inertia shared props
 * Features a modern design with icon, gradient accent, and dark mode support
 */
import { getIcon } from '@/Utils/iconResolver';
import { cn } from '@/Utils/utils';
import { usePage } from '@inertiajs/react';

export default function ApplicationLogo({ className = '', ...props }) {
  const { app_name, app_logo } = usePage().props;
  const appName = app_name || 'Mosaic';
  const LogoIcon = getIcon(app_logo || 'Grid3x3');

  return (
    <div className={cn('flex items-center gap-2.5', className)} {...props}>
      <div className="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary/80 shadow-lg ring-1 ring-primary/30 backdrop-blur-sm">
        <LogoIcon className="h-5 w-5 text-primary-foreground drop-shadow-sm" strokeWidth={2.5} />
      </div>

      <span className="text-xl font-bold tracking-tight text-foreground">{appName}</span>
    </div>
  );
}
