/**
 * Enhanced useForm hook with automatic toast notifications and component-level loading support.
 *
 * @deprecated This hook is deprecated. Please use `useInertiaForm` from '@/Hooks/useInertiaForm' instead.
 * `useInertiaForm` provides better performance with React Hook Form, improved validation,
 * and better error handling.
 *
 * Migration guide:
 * - Replace `useSmartForm` with `useInertiaForm`
 * - Replace `FormField` with `FormFieldRHF` (or use `Controller` for custom inputs)
 * - Replace `FormTextarea` with `FormTextareaRHF`
 * - Update form state access: `form.data` → `form.watch('fieldName')`
 * - Update errors: `form.errors` → `form.formState.errors`
 * - Update processing: `form.processing` → `form.formState.isSubmitting`
 * - Use `form.setValue()` instead of `form.setData()` for programmatic updates
 *
 * Example migration:
 * ```jsx
 * // Old
 * const form = useSmartForm({ name: '', email: '' });
 * <FormField value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
 *
 * // New
 * const form = useInertiaForm({ name: '', email: '' });
 * <FormFieldRHF name="name" control={form.control} label="Name" />
 * ```
 *
 * Provides a wrapper around Inertia's useForm that automatically displays
 * toast notifications for form submission success/error states.
 * Server-side flash messages are handled globally by useFlashNotifications
 * in DashboardLayout, so this hook only handles client-side toasts.
 *
 * Supports component-level loading mode where skipLoadingIndicator is automatically
 * set to prevent global navigation spinner from showing.
 *
 * @param {Object} initialData - Initial form data
 * @param {Object} options - Configuration options
 * @param {boolean} options.componentLevel - If true, automatically sets skipLoadingIndicator (default: false)
 * @param {Object} options.toast - Toast notification configuration
 * @returns {Object} Enhanced form object
 */
import { useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import { toast } from 'sonner';

export function useSmartForm(initialData, options = {}) {
  const toastConfig = useMemo(() => options.toast || {}, [options.toast]);
  const toastEnabled = toastConfig.enabled !== false;
  const componentLevel = options.componentLevel === true;

  const form = useForm(initialData);

  const enhancedForm = {
    ...form,
    submit: (method, url, submitOptions = {}) => {
      const originalOnSuccess = submitOptions.onSuccess;
      const originalOnError = submitOptions.onError;

      /**
       * Automatically inject skipLoadingIndicator for component-level forms.
       * Prevents global navigation spinner from showing during form submission.
       * Can be overridden via submitOptions if needed.
       */
      const finalOptions = {
        ...submitOptions,
        ...(componentLevel && !submitOptions.skipLoadingIndicator
          ? { skipLoadingIndicator: true }
          : {}),
      };

      return form.submit(method, url, {
        ...finalOptions,
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
