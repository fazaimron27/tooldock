/**
 * Empty state component for when there's no data to display
 * Shows icon, message, and optional action button
 */
import { cn } from '@/Utils/utils';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { Button } from '@/Components/ui/button';

export default function EmptyState({ icon: Icon, message, actionLabel, actionRoute, className }) {
  return (
    <div
      className={cn('h-full flex flex-col items-center justify-center text-center py-6', className)}
    >
      {Icon && <Icon className="w-10 h-10 mx-auto text-muted-foreground mb-2" />}
      <p className="text-muted-foreground mb-3">{message}</p>
      {actionRoute && actionLabel && (
        <Link href={actionRoute}>
          <Button size="sm">
            <Plus className="w-4 h-4 mr-1" /> {actionLabel}
          </Button>
        </Link>
      )}
    </div>
  );
}
