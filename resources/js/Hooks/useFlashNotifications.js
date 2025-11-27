/**
 * Hook for displaying flash notifications from server-side flash messages
 * Automatically shows toast notifications for success, error, and warning messages
 */
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export function useFlashNotifications() {
  const { flash } = usePage().props;

  useEffect(() => {
    if (flash?.success) {
      toast.success(flash.success);
    }

    if (flash?.error) {
      toast.error(flash.error);
    }

    if (flash?.warning) {
      toast.warning(flash.warning);
    }
  }, [flash]);
}
