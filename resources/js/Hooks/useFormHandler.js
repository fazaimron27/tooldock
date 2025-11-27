/**
 * Generic form handler hook that wraps useForm with common submission patterns
 * Provides simplified form submission with route-based handling
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
