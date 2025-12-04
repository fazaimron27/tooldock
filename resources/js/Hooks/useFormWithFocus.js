/**
 * Form handler hook with automatic focus management on validation errors
 * Uses React Hook Form which automatically focuses the first field with an error
 *
 * @deprecated This hook is deprecated. Please use `useInertiaForm` from '@/Hooks/useInertiaForm' directly.
 * React Hook Form automatically focuses the first field with an error, so this wrapper is no longer needed.
 *
 * Migration guide:
 * - Replace `useFormWithFocus` with `useInertiaForm`
 * - Replace `FormField` with `FormFieldRHF` (or use `Controller` for custom inputs)
 * - Replace `FormTextarea` with `FormTextareaRHF`
 * - Update form state access: `form.data` → `form.watch('fieldName')`
 * - Update errors: `form.errors` → `form.formState.errors`
 * - Update processing: `form.processing` → `form.formState.isSubmitting`
 * - Use `form.setValue()` instead of `form.setData()` for programmatic updates
 * - Replace `submit` function with direct method calls: `form.post()`, `form.put()`, etc.
 *
 * Example migration:
 * ```jsx
 * // Old
 * const form = useFormWithFocus({ name: '', email: '' }, { route: 'users.store', method: 'post' });
 * <form onSubmit={form.submit}>...</form>
 *
 * // New
 * const form = useInertiaForm({ name: '', email: '' });
 * <form onSubmit={(e) => { e.preventDefault(); form.post(route('users.store')); }}>...</form>
 * ```
 */
import { useCallback } from 'react';

import { useInertiaForm } from './useInertiaForm';

export function useFormWithFocus(initialData, options = {}) {
  const {
    route: routeName,
    method = 'post',
    preserveScroll = true,
    onSuccess,
    onError,
    onFinish,
    ...formOptions
  } = options;

  const form = useInertiaForm(initialData, {
    ...formOptions,
  });

  const handleError = useCallback(
    (errors) => {
      onError?.(errors);
    },
    [onError]
  );

  const submit = useCallback(
    (e) => {
      if (e) {
        e.preventDefault();
      }

      const routeUrl = typeof routeName === 'function' ? routeName() : route(routeName);

      const submitOptions = {
        preserveScroll,
        onSuccess: (page) => {
          onSuccess?.(page);
          onFinish?.(page);
        },
        onError: handleError,
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
    [form, routeName, method, preserveScroll, onSuccess, handleError, onFinish]
  );

  return {
    ...form,
    submit,
    /**
     * Backward compatibility property for legacy code.
     * React Hook Form handles focus management automatically.
     */
    fieldRefs: {},
  };
}
