import { cn } from '@/Utils/utils';

import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog';

/**
 * Simple dialog component for non-form dialogs
 * @param {object} props
 * @param {boolean} props.open - Whether dialog is open
 * @param {function} props.onOpenChange - Callback when open state changes
 * @param {string} props.title - Dialog title
 * @param {string|React.ReactNode} props.description - Dialog description
 * @param {React.ReactNode} props.children - Dialog content
 * @param {React.ReactNode} props.trigger - Trigger button/element
 * @param {React.ReactNode} props.footer - Custom footer content (optional, defaults to Cancel button)
 * @param {string} props.className - Additional CSS classes
 */
export default function SimpleDialog({
  open,
  onOpenChange,
  title,
  description,
  children,
  trigger,
  footer,
  className,
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
      <DialogContent className={cn(className)}>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          {description && <DialogDescription>{description}</DialogDescription>}
        </DialogHeader>
        <div className="py-4">{children}</div>
        {footer ? (
          <DialogFooter>{footer}</DialogFooter>
        ) : (
          <DialogFooter>
            <Button variant="outline" onClick={() => onOpenChange?.(false)}>
              Close
            </Button>
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  );
}
