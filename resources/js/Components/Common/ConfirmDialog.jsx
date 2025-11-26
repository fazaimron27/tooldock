import { cn } from '@/Utils/utils';

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/ui/alert-dialog';

/**
 * Simplified wrapper for AlertDialog with confirmation functionality
 * @param {object} props
 * @param {boolean} props.isOpen - Whether dialog is open
 * @param {function} props.onConfirm - Callback when confirmed
 * @param {function} props.onCancel - Callback when cancelled
 * @param {string} props.title - Dialog title
 * @param {string|React.ReactNode} props.message - Dialog message/description
 * @param {string} props.confirmLabel - Confirm button label (default: "Confirm")
 * @param {string} props.cancelLabel - Cancel button label (default: "Cancel")
 * @param {string} props.variant - Variant: "default" or "destructive" (default: "default")
 */
export default function ConfirmDialog({
  isOpen,
  onConfirm,
  onCancel,
  title,
  message,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  variant = 'default',
}) {
  const handleConfirm = () => {
    onConfirm?.();
  };

  const handleCancel = () => {
    onCancel?.();
  };

  const isDestructive = variant === 'destructive';

  return (
    <AlertDialog open={isOpen} onOpenChange={(open) => !open && handleCancel()}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          <AlertDialogDescription>{message}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel onClick={handleCancel}>{cancelLabel}</AlertDialogCancel>
          <AlertDialogAction
            onClick={handleConfirm}
            className={cn(
              isDestructive &&
                'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90'
            )}
          >
            {confirmLabel}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
