/**
 * React Hook Form integration with Inertia.js
 *
 * Provides a bridge between react-hook-form and Inertia.js router methods.
 * Automatically maps server-side validation errors from Laravel to react-hook-form.
 * Supports toast notifications and component-level loading indicators.
 *
 * @param {Object} defaultValues - Initial form values
 * @param {Object} options - Configuration options
 * @param {Object} options.toast - Toast notification configuration
 * @param {boolean} options.componentLevel - If true, automatically sets skipLoadingIndicator
 * @param {Object} options.resolver - Validation resolver (e.g., zodResolver)
 * @param {string} options.mode - Validation mode ('onChange', 'onBlur', 'onSubmit', etc.)
 * @returns {Object} Enhanced form object with Inertia integration
 */
import { router, usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';

export function useInertiaForm(defaultValues = {}, options = {}) {
  const toastConfig = useMemo(() => options.toast || {}, [options.toast]);
  const toastEnabled = toastConfig.enabled !== false;
  const componentLevel = options.componentLevel === true;

  const form = useForm({
    defaultValues,
    resolver: options.resolver,
    mode: options.mode || 'onSubmit',
    ...options.formOptions,
  });

  const page = usePage();

  /**
   * Maps Laravel validation errors to react-hook-form format.
   * Laravel returns: { field: ['message1', 'message2'] }
   * react-hook-form expects: { field: { message: 'message' } }
   */
  const mapServerErrors = useCallback(
    (errors) => {
      if (!errors || typeof errors !== 'object') {
        return;
      }

      Object.keys(errors).forEach((field) => {
        const errorValue = errors[field];
        if (!errorValue) return;

        /**
         * Handle both array of errors and single error string.
         * Always use the first error message for consistency.
         */
        const errorMessages = Array.isArray(errorValue) ? errorValue : [errorValue];
        const firstError = errorMessages[0];

        if (firstError) {
          form.setError(field, {
            type: 'server',
            message: String(firstError),
          });
        }
      });
    },
    [form]
  );

  /**
   * Submit handler that integrates with Inertia router.
   * Errors are handled in callbacks to prevent cross-form contamination.
   * Inertia automatically calls onError when validation errors exist.
   */
  const submitInertia = (method, url, submitOptions = {}) => {
    const errorBag = submitOptions.errorBag;
    const originalOnSuccess = submitOptions.onSuccess;
    const originalOnError = submitOptions.onError;

    /**
     * Automatically inject skipLoadingIndicator for component-level forms.
     * Prevents global navigation spinner during form submission.
     */
    const finalOptions = {
      ...submitOptions,
      ...(componentLevel && !submitOptions.skipLoadingIndicator
        ? { skipLoadingIndicator: true }
        : {}),
    };

    form.handleSubmit(
      (data) => {
        const httpMethod = method.toLowerCase();

        /**
         * Filter out null/undefined values, but preserve empty strings.
         * Empty strings are valid form values (e.g., password fields) and should be validated server-side.
         */
        const filteredData = Object.fromEntries(
          Object.entries(data).filter(([_, value]) => value !== null && value !== undefined)
        );

        /**
         * Success handler called by Inertia when there are no validation errors.
         */
        const handleSuccess = (page) => {
          if (toastEnabled && !submitOptions.silent) {
            const message =
              typeof toastConfig.success === 'function'
                ? toastConfig.success()
                : toastConfig.success || 'Operation completed successfully!';
            toast.success(message);
          }
          originalOnSuccess?.(page);
        };

        /**
         * Error handler processes server-side validation errors.
         * Errors may come from callback or page props (errorBag scoped).
         */
        const handleError = (errors) => {
          const pageErrors = errorBag ? page?.props?.errors?.[errorBag] : page?.props?.errors;
          const errorData = errors || pageErrors || {};

          /**
           * Only map errors for fields that exist in this form.
           * Prevents cross-form error contamination.
           */
          const formFields = Object.keys(defaultValues);
          const relevantErrors = Object.keys(errorData)
            .filter((field) => formFields.includes(field))
            .reduce((acc, field) => {
              acc[field] = errorData[field];
              return acc;
            }, {});

          if (Object.keys(relevantErrors).length > 0) {
            mapServerErrors(relevantErrors);

            if (toastEnabled && !submitOptions.silent) {
              const firstErrorField = Object.keys(relevantErrors)[0];
              const firstError = Array.isArray(relevantErrors[firstErrorField])
                ? relevantErrors[firstErrorField][0]
                : relevantErrors[firstErrorField];

              let toastMessage;
              if (typeof toastConfig.error === 'function') {
                const result = toastConfig.error(relevantErrors);
                toastMessage =
                  typeof result === 'string'
                    ? result
                    : String(result || 'Please check the form for errors.');
              } else {
                toastMessage =
                  firstError || toastConfig.error || 'Please check the form for errors.';
              }
              toast.error(String(toastMessage));
            }
          }

          originalOnError?.(relevantErrors);
        };

        /**
         * Use appropriate Inertia router method based on HTTP verb.
         * POST uses router.post, other methods use router.visit with method option.
         */
        if (httpMethod === 'post') {
          router.post(url, filteredData, {
            ...finalOptions,
            preserveScroll: finalOptions.preserveScroll !== false,
            onSuccess: handleSuccess,
            onError: handleError,
            ...(errorBag && { errorBag }),
          });
        } else {
          router.visit(url, {
            method: httpMethod,
            data: filteredData,
            ...finalOptions,
            preserveScroll: finalOptions.preserveScroll !== false,
            onSuccess: handleSuccess,
            onError: handleError,
            ...(errorBag && { errorBag }),
          });
        }
      },
      (errors) => {
        /**
         * Client-side validation failed.
         * Display toast notification and call error callback.
         */
        if (toastEnabled && !submitOptions.silent) {
          let message;
          if (typeof toastConfig.error === 'function') {
            const result = toastConfig.error(errors);
            message =
              typeof result === 'string'
                ? result
                : String(result || 'Please check the form for errors.');
          } else {
            message = toastConfig.error || 'Please check the form for errors.';
          }
          toast.error(String(message));
        }
        originalOnError?.(errors);
      }
    )();
  };

  const enhancedForm = {
    ...form,
    /**
     * Inertia-specific submission methods.
     */
    submit: submitInertia,
    post: (url, submitOptions = {}) => submitInertia('post', url, submitOptions),
    put: (url, submitOptions = {}) => submitInertia('put', url, submitOptions),
    patch: (url, submitOptions = {}) => submitInertia('patch', url, submitOptions),
    delete: (url, submitOptions = {}) => submitInertia('delete', url, submitOptions),
    /**
     * Processing state for backward compatibility with useSmartForm.
     */
    get processing() {
      return form.formState.isSubmitting;
    },
    /**
     * Errors in format compatible with existing code.
     */
    get errors() {
      const rhfErrors = form.formState.errors;
      const formattedErrors = {};
      Object.keys(rhfErrors).forEach((key) => {
        formattedErrors[key] = rhfErrors[key]?.message || rhfErrors[key];
      });
      return formattedErrors;
    },
    /**
     * Form data getter for backward compatibility.
     */
    get data() {
      return form.getValues();
    },
    /**
     * Set form data method for backward compatibility.
     * Supports both single field and bulk updates.
     */
    setData: (name, value) => {
      if (typeof name === 'string') {
        form.setValue(name, value, { shouldValidate: false });
      } else {
        Object.keys(name).forEach((key) => {
          form.setValue(key, name[key], { shouldValidate: false });
        });
      }
    },
    /**
     * Clear form errors method.
     */
    clearErrors: (name) => {
      if (name) {
        form.clearErrors(name);
      } else {
        form.clearErrors();
      }
    },
    /**
     * Reset form to initial or provided values.
     */
    reset: (values) => {
      form.reset(values || defaultValues);
    },
  };

  return enhancedForm;
}
