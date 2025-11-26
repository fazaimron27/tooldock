import { useForm } from '@inertiajs/react';
import { useCallback } from 'react';

/**
 * Generic form handler hook that wraps useForm with common submission patterns
 * @param {object} initialData - Initial form data
 * @param {object} options - Form options
 * @param {string|function} options.route - Route name or function that returns route
 * @param {string} options.method - HTTP method: 'post', 'put', 'patch', 'delete' (default: 'post')
 * @param {boolean} options.preserveScroll - Preserve scroll position (default: true)
 * @param {function} options.onSuccess - Success callback
 * @param {function} options.onError - Error callback
 * @param {function} options.onFinish - Finish callback (runs after success/error)
 * @returns {object} Form handler with submit function and form state
 */
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
