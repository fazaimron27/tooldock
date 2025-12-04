/**
 * Form handler hook integrated with dialog state management
 * Combines React Hook Form with dialog open/close state and automatic focus management
 */
import { useCallback } from 'react';

import { useDisclosure } from './useDisclosure';
import { useInertiaForm } from './useInertiaForm';

export function useFormWithDialog(initialData, options = {}) {
  const {
    route: routeName,
    method = 'post',
    preserveScroll = true,
    closeOnSuccess = true,
    resetOnClose = true,
    onSuccess,
    onError,
    onFinish,
    ...formOptions
  } = options;

  const dialog = useDisclosure();

  const form = useInertiaForm(initialData, formOptions);

  /**
   * Handles successful form submission.
   * Closes dialog if closeOnSuccess is enabled.
   */
  const handleSuccess = useCallback(
    (page) => {
      if (closeOnSuccess) {
        dialog.onClose();
      }
      onSuccess?.(page);
      onFinish?.(page);
    },
    [closeOnSuccess, dialog, onSuccess, onFinish]
  );

  /**
   * Handles form submission errors.
   * Keeps dialog open to display validation errors to the user.
   */
  const handleError = useCallback(
    (errors) => {
      if (errors && Object.keys(errors).length > 0 && !dialog.isOpen) {
        dialog.onOpen();
      }
      onError?.(errors);
      onFinish?.(errors);
    },
    [onError, onFinish, dialog]
  );

  const submit = useCallback(
    (e) => {
      if (e) {
        e.preventDefault();
      }

      const routeUrl = typeof routeName === 'function' ? routeName() : route(routeName);

      const submitOptions = {
        preserveScroll,
        onSuccess: handleSuccess,
        onError: handleError,
        /**
         * Error bag scopes validation errors to this specific form instance.
         * Prevents cross-form error contamination on pages with multiple forms.
         */
        errorBag: options.errorBag,
      };

      switch (method.toLowerCase()) {
        case 'put':
          form.put(routeUrl, submitOptions);
          break;
        case 'patch':
          form.patch(routeUrl, submitOptions);
          break;
        case 'delete':
          form.delete(routeUrl, submitOptions);
          break;
        case 'post':
        default:
          form.post(routeUrl, submitOptions);
          break;
      }
    },
    [form, routeName, method, preserveScroll, handleSuccess, handleError, options.errorBag]
  );

  /**
   * Handles dialog open/close state changes.
   * Prevents accidental closure during form submission with validation errors.
   * Allows manual closure via Cancel button even when errors exist.
   */
  const handleDialogChange = useCallback(
    (isOpen) => {
      if (isOpen) {
        dialog.onOpen();
      } else {
        const hasErrors = Object.keys(form.formState.errors || {}).length > 0;
        const isSubmitting = form.formState.isSubmitting;

        /**
         * Prevent auto-closure during submission with errors.
         * User-initiated closure (Cancel button) is handled separately.
         */
        if (isSubmitting && hasErrors) {
          if (!dialog.isOpen) {
            dialog.onOpen();
          }
          return;
        }

        dialog.onClose();
        if (resetOnClose) {
          form.clearErrors();
          form.reset();
        }
      }
    },
    [dialog, form, resetOnClose]
  );

  /**
   * Cancel button handler that always closes dialog and clears form state.
   * Bypasses validation error checks to allow user-initiated cancellation.
   */
  const handleCancel = useCallback(() => {
    dialog.onClose();
    if (resetOnClose) {
      form.clearErrors();
      form.reset();
    }
  }, [dialog, form, resetOnClose]);

  return {
    ...form,
    submit,
    dialog,
    handleDialogChange,
    handleCancel,
  };
}
