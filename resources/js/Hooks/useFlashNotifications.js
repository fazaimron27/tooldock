/**
 * Hook for displaying flash notifications from server-side flash messages
 * Automatically shows toast notifications for success, error, and warning messages
 * Uses a request counter to track each navigation, allowing flash messages to show
 * on every save even when on the same page/tab
 */
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

export function useFlashNotifications() {
  const { flash, url, component } = usePage().props;
  const lastUrlRef = useRef(url);
  const lastComponentRef = useRef(component);
  const requestCounterRef = useRef(0);
  const shownRequestRef = useRef({ success: -1, error: -1, warning: -1 });

  useEffect(() => {
    const urlChanged = lastUrlRef.current !== url;
    const componentChanged = lastComponentRef.current !== component;

    if (urlChanged || componentChanged) {
      lastUrlRef.current = url;
      lastComponentRef.current = component;
      requestCounterRef.current = 0;
      shownRequestRef.current = { success: -1, error: -1, warning: -1 };
    }

    const removeStartListener = router.on('start', () => {
      requestCounterRef.current += 1;
    });

    const removeSuccessListener = router.on('success', () => {
      shownRequestRef.current = { success: -1, error: -1, warning: -1 };
    });

    if (flash?.success) {
      if (shownRequestRef.current.success < requestCounterRef.current) {
        shownRequestRef.current.success = requestCounterRef.current;
        toast.success(flash.success);
      }
    } else {
      shownRequestRef.current.success = -1;
    }

    if (flash?.error) {
      if (shownRequestRef.current.error < requestCounterRef.current) {
        shownRequestRef.current.error = requestCounterRef.current;
        toast.error(flash.error);
      }
    } else {
      shownRequestRef.current.error = -1;
    }

    if (flash?.warning) {
      if (shownRequestRef.current.warning < requestCounterRef.current) {
        shownRequestRef.current.warning = requestCounterRef.current;
        toast.warning(flash.warning);
      }
    } else {
      shownRequestRef.current.warning = -1;
    }

    return () => {
      removeStartListener();
      removeSuccessListener();
    };
  }, [flash, url, component]);
}
