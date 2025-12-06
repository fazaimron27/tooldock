/**
 * Custom dialog component for member management operations.
 *
 * Provides consistent padding and structure for dialogs used in member management
 * (add, remove, transfer). Wraps the standard Dialog component with proper spacing
 * and allows customizable maximum width.
 */
import { cn } from '@/Utils/utils';

import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

export default function MemberDialog({
  open,
  onOpenChange,
  title,
  description,
  children,
  footer,
  className,
  maxWidth = '600px',
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className={cn('sm:max-w-[600px]', className)}
        style={{ maxWidth: `min(${maxWidth}, 100%)` }}
      >
        <div className="p-6">
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
            {description && <DialogDescription>{description}</DialogDescription>}
          </DialogHeader>
          <div className="mt-4">{children}</div>
          {footer && <DialogFooter className="mt-6">{footer}</DialogFooter>}
        </div>
      </DialogContent>
    </Dialog>
  );
}
