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

/**
 * Reusable dialog component for forms with confirmation
 * @param {object} props
 * @param {boolean} props.open - Whether dialog is open
 * @param {function} props.onOpenChange - Callback when open state changes
 * @param {string} props.title - Dialog title
 * @param {string|React.ReactNode} props.description - Dialog description
 * @param {React.ReactNode} props.children - Form content (should include a form element)
 * @param {React.ReactNode} props.trigger - Trigger button/element
 * @param {string} props.confirmLabel - Confirm button label (default: "Confirm")
 * @param {string} props.cancelLabel - Cancel button label (default: "Cancel")
 * @param {string} props.variant - Button variant: "default" or "destructive" (default: "default")
 * @param {boolean} props.processing - Whether form is processing
 * @param {string} props.processingLabel - Label when processing (default: "Processing...")
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.formId - Form ID for submit button (default: "form-dialog-form")
 */
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
