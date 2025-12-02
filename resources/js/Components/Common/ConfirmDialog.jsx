/**
 * Confirmation dialog component for user confirmations
 * Supports default and destructive variants with customizable labels
 */
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

export default function ConfirmDialog({
  isOpen,
  onConfirm,
  onCancel,
  title,
  message,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  variant = 'default',
  disabled = false,
}) {
  const handleConfirm = () => {
    if (!disabled) {
      onConfirm?.();
    }
  };

  const handleCancel = () => {
    if (!disabled) {
      onCancel?.();
    }
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
          <AlertDialogCancel onClick={handleCancel} disabled={disabled}>
            {cancelLabel}
          </AlertDialogCancel>
          <AlertDialogAction
            onClick={handleConfirm}
            disabled={disabled}
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
