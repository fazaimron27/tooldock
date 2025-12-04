/**
 * Generic form handler hook that wraps useForm with common submission patterns
 * Provides simplified form submission with route-based handling
 *
 * @deprecated This hook is deprecated. Please use `useInertiaForm` from '@/Hooks/useInertiaForm' instead.
 * `useInertiaForm` provides better performance with React Hook Form, improved validation,
 * and better error handling.
 *
 * Migration guide:
 * - Replace `useFormHandler` with `useInertiaForm`
 * - Replace `FormField` with `FormFieldRHF` (or use `Controller` for custom inputs)
 * - Replace `FormTextarea` with `FormTextareaRHF`
 * - Update form state access: `data.field` → `form.watch('field')`
 * - Update errors: `errors.field` → `form.formState.errors.field`
 * - Update processing: `processing` → `form.formState.isSubmitting`
 * - Use `form.setValue()` instead of `setData()` for programmatic updates
 * - Replace `submit` function with direct method calls: `form.post()`, `form.put()`, etc.
 *
 * Example migration:
 * ```jsx
 * // Old
 * const { data, setData, errors, processing, submit } = useFormHandler(
 *   { name: '', email: '' },
 *   { route: 'users.store', method: 'post' }
 * );
 * <FormField value={data.name} onChange={(e) => setData('name', e.target.value)} />
 * <form onSubmit={submit}>...</form>
 *
 * // New
 * const form = useInertiaForm(
 *   { name: '', email: '' },
 *   { toast: { success: 'User created!', error: 'Failed to create user.' } }
 * );
 * <FormFieldRHF name="name" control={form.control} label="Name" />
 * <form onSubmit={(e) => { e.preventDefault(); form.post(route('users.store')); }}>...</form>
 * ```
 */
import { useForm } from '@inertiajs/react';
import { useCallback } from 'react';

export function useFormHandler(initialData, options = {}) {
  const {
    route: routeName,
    method = 'post',
    preserveScroll = true,
    onSuccess,
    onError,
    onFinish,
  } = options;

  const form = useForm(initialData);

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
        onError: (errors) => {
          onError?.(errors);
          onFinish?.(errors);
        },
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
    [form, routeName, method, preserveScroll, onSuccess, onError, onFinish]
  );

  return {
    ...form,
    submit,
  };
}
