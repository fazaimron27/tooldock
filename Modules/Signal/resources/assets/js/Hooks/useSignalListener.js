/**
 * Global Signal listener hook
 *
 * Listens for all Signal notification broadcasts and handles them based on
 * their delivery mode:
 * - 'silent': Updates notification count only (SignalBell handles display)
 * - 'flash': Shows a toast notification only (no inbox)
 * - 'trigger': Shows toast + executes frontend action (e.g., page reload)
 * - 'broadcast': Full notification - inbox + toast + action
 *
 * This hook should be used in the main layout to ensure global coverage.
 *
 * @see SignalService for backend delivery mode documentation
 */
import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

import { initNotificationSoundUnlock, playNotificationSound } from '../Utils/notificationSound';

/**
 * Supported frontend actions that can be triggered by action notifications.
 */
const ACTIONS = {
  /**
   * Force a full browser reload. Use this for permission changes
   * where cached state may be stale.
   */
  reload_permissions: () => {
    window.setTimeout(() => {
      window.location.reload();
    }, 3500);
  },

  /**
   * Force a full browser reload (alias for reload_permissions).
   */
  reload: () => {
    window.location.reload();
  },

  /**
   * Navigate to a URL using hard navigation.
   */
  navigate: (url) => {
    if (url) {
      window.location.href = url;
    }
  },

  /**
   * Soft refresh current page via Inertia (not currently used).
   * Prefer hard reload for permission changes.
   */
  refresh: () => {
    window.location.reload();
  },
};

/**
 * Map notification type to toast variant
 */
const TYPE_TO_TOAST = {
  info: 'info',
  success: 'success',
  warning: 'warning',
  error: 'error',
};

/**
 * Hook to listen for Signal notifications globally.
 *
 * Handles flash, trigger, and broadcast delivery modes by showing toasts
 * and executing actions. Silent (inbox-only) notifications are handled
 * by SignalBell component separately.
 */
export function useSignalListener() {
  const { auth, userPreferences } = usePage().props;
  const userId = auth?.user?.id;

  // Get sound settings from server-provided preferences (fallback to defaults)
  const soundEnabled = userPreferences?.notificationSound ?? true;
  const soundVolume = userPreferences?.notificationVolume ?? 0.5;

  // Track if component is mounted to prevent state updates after unmount
  const isMounted = useRef(true);

  // Use refs to avoid stale closure in WebSocket callback
  const soundEnabledRef = useRef(soundEnabled);
  const soundVolumeRef = useRef(soundVolume);
  useEffect(() => {
    soundEnabledRef.current = soundEnabled;
    soundVolumeRef.current = soundVolume;
  }, [soundEnabled, soundVolume]);

  // Initialize audio unlock listener on mount
  useEffect(() => {
    isMounted.current = true;
    initNotificationSoundUnlock();

    return () => {
      isMounted.current = false;
    };
  }, []);

  useEffect(() => {
    // Only subscribe if user is authenticated and Echo is available
    if (!userId || typeof window.Echo === 'undefined') {
      return;
    }

    const channel = window.Echo.private(`App.Models.User.${userId}`);

    // Listen for all notification broadcasts
    channel.listen('.notification.received', (data) => {
      // Skip if component unmounted
      if (!isMounted.current) {
        return;
      }

      const { delivery, action, title, message, type, url } = data;

      // Log for debugging
      if (import.meta.env.DEV) {
        console.log('[SignalListener] Received:', { delivery, action, title });
      }

      // Play notification sound for non-silent modes
      if (soundEnabledRef.current && delivery !== 'silent') {
        playNotificationSound(type, soundVolumeRef.current);
      }

      // Handle based on delivery mode
      switch (delivery) {
        case 'flash':
          // Show toast only, no inbox update
          showToast(type, title, message, url);
          break;

        case 'trigger':
          // Show toast if title provided, then execute action
          if (title) {
            showToast(type, title, message, url, {
              duration: 4000, // Longer duration for action notifications
            });
          }

          // Execute the action after a short delay for toast to show
          if (action && ACTIONS[action]) {
            ACTIONS[action](url);
          } else if (action) {
            console.warn(`[SignalListener] Unknown action: ${action}`);
          }
          break;

        case 'broadcast':
          // Full notification: show toast AND execute action
          // (inbox update is handled by SignalBell via the same broadcast)
          if (title) {
            showToast(type, title, message, url, {
              duration: 4000,
            });
          }

          // Execute action if provided
          if (action && ACTIONS[action]) {
            ACTIONS[action](url);
          } else if (action) {
            console.warn(`[SignalListener] Unknown action: ${action}`);
          }
          break;

        case 'silent':
        default:
          // Silent notifications: SignalBell handles these
          // No toast, no action here
          break;
      }
    });

    // Cleanup on unmount - only stop listening, don't leave channel
    // This keeps the WebSocket connection alive across Inertia navigations
    // avoiding re-authentication on every page change (~200-300ms savings)
    return () => {
      channel.stopListening('.notification.received');
    };
  }, [userId]);
}

/**
 * Show a toast notification using sonner.
 *
 * @param {string} type - Notification type (info, success, warning, error)
 * @param {string} title - Toast title
 * @param {string} message - Toast message/description
 * @param {string|null} url - Optional action URL
 * @param {object} options - Additional toast options
 */
function showToast(type, title, message, url, options = {}) {
  const toastFn = toast[TYPE_TO_TOAST[type]] || toast.info;

  toastFn(title || 'Notification', {
    description: message || undefined,
    duration: 3000,
    ...options,
    ...(url && {
      action: {
        label: 'View',
        onClick: () => {
          window.location.href = url;
        },
      },
    }),
  });
}

export default useSignalListener;
