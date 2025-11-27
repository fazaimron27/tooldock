/**
 * Enhanced useForm hook with automatic toast notifications
 * Automatically displays toast messages for success and error states
 */
import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import { toast } from 'sonner';

export function useSmartForm(initialData, options = {}) {
  const { flash } = usePage().props;
  const toastConfig = useMemo(() => options.toast || {}, [options.toast]);
  const toastEnabled = toastConfig.enabled !== false;

  const form = useForm(initialData);

  // Handle flash messages from server
  useEffect(() => {
    if (flash?.success && toastEnabled) {
      const message =
        typeof toastConfig.success === 'function'
          ? toastConfig.success(flash.success)
          : toastConfig.success || flash.success;
      toast.success(message);
    }

    if (flash?.error && toastEnabled) {
      const message =
        typeof toastConfig.error === 'function'
          ? toastConfig.error(flash.error)
          : toastConfig.error || flash.error;
      toast.error(message);
    }
  }, [flash, toastConfig, toastEnabled]);

  // Enhanced submit methods with auto-toast
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
