/**
 * Enhanced useForm hook with automatic toast notifications.
 *
 * Provides a wrapper around Inertia's useForm that automatically displays
 * toast notifications for form submission success/error states.
 * Server-side flash messages are handled globally by useFlashNotifications
 * in DashboardLayout, so this hook only handles client-side toasts.
 */
import { useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import { toast } from 'sonner';

export function useSmartForm(initialData, options = {}) {
  const toastConfig = useMemo(() => options.toast || {}, [options.toast]);
  const toastEnabled = toastConfig.enabled !== false;

  const form = useForm(initialData);

  /**
   * Enhanced form methods that wrap Inertia's submit methods with
   * automatic toast notifications. Supports silent mode to disable
   * client-side toasts when relying on server flash messages.
   */
  const enhancedForm = {
    ...form,
    submit: (method, url, submitOptions = {}) => {
      const originalOnSuccess = submitOptions.onSuccess;
      const originalOnError = submitOptions.onError;

      return form.submit(method, url, {
        ...submitOptions,
        onSuccess: (page) => {
          if (toastEnabled && !submitOptions.silent) {
            const message =
              typeof toastConfig.success === 'function'
                ? toastConfig.success()
                : toastConfig.success || 'Operation completed successfully!';
            toast.success(message);
          }
          originalOnSuccess?.(page);
        },
        onError: (errors) => {
          if (toastEnabled && !submitOptions.silent) {
            const message =
              typeof toastConfig.error === 'function'
                ? toastConfig.error(errors)
                : toastConfig.error || 'Something went wrong. Please try again.';
            toast.error(message);
          }
          originalOnError?.(errors);
        },
      });
    },
    post: (url, submitOptions = {}) => {
      return enhancedForm.submit('post', url, submitOptions);
    },
    put: (url, submitOptions = {}) => {
      return enhancedForm.submit('put', url, submitOptions);
    },
    patch: (url, submitOptions = {}) => {
      return enhancedForm.submit('patch', url, submitOptions);
    },
    delete: (url, submitOptions = {}) => {
      return enhancedForm.submit('delete', url, submitOptions);
    },
  };

  return enhancedForm;
}
