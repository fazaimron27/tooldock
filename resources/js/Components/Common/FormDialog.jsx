/**
 * Form dialog component for displaying forms in a modal dialog
 * Integrates with form submission and provides processing state feedback
 */
import { cn } from '@/Utils/utils';

import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';

export default function FormDialog({
  open,
  onOpenChange,
  title,
  description,
  children,
  trigger,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  variant = 'default',
  processing = false,
  processingLabel = 'Processing...',
  className,
  formId = 'form-dialog-form',
}) {
  const isDestructive = variant === 'destructive';

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      {trigger && <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger>}
      <AlertDialogContent className={cn(className)}>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          {description && <AlertDialogDescription>{description}</AlertDialogDescription>}
        </AlertDialogHeader>
        {children}
        <AlertDialogFooter>
          <AlertDialogCancel type="button" onClick={() => onOpenChange?.(false)}>
            {cancelLabel}
          </AlertDialogCancel>
          <Button
            type="submit"
            variant={isDestructive ? 'destructive' : 'default'}
            disabled={processing}
            form={formId}
          >
            {processing ? processingLabel : confirmLabel}
          </Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
