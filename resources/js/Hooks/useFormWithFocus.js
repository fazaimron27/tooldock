import { useForm } from '@inertiajs/react';
import { useCallback, useMemo, useRef } from 'react';

/**
 * Form handler with automatic focus management on errors
 * @param {object} initialData - Initial form data
 * @param {object} options - Form options
 * @param {string|function} options.route - Route name or function that returns route
 * @param {string} options.method - HTTP method: 'post', 'put', 'patch', 'delete' (default: 'post')
 * @param {boolean} options.preserveScroll - Preserve scroll position (default: true)
 * @param {array|object} options.focusFields - Array of field names ['field1', 'field2'] or object map { fieldName: ref } (for backward compatibility)
 * @param {function} options.onSuccess - Success callback
 * @param {function} options.onError - Error callback
 * @param {function} options.onFinish - Finish callback
 * @returns {object} Form handler with submit function, form state, and field refs
 */
export function useFormWithFocus(initialData, options = {}) {
  const {
    route: routeName,
    method = 'post',
    preserveScroll = true,
    focusFields = [],
    onSuccess,
    onError,
    onFinish,
  } = options;

  const form = useForm(initialData);

  // Determine field names that need refs
  const fieldNames = useMemo(() => {
    if (Array.isArray(focusFields)) {
      return focusFields;
    }
    if (typeof focusFields === 'object' && focusFields !== null) {
      // Backward compatibility: if object, use keys as field names
      return Object.keys(focusFields);
    }
    return [];
  }, [focusFields]);

  // Create refs on-demand for specified fields
  const fieldRefsStorage = useRef({});

  // Initialize refs for all field names
  fieldNames.forEach((fieldName) => {
    if (!fieldRefsStorage.current[fieldName]) {
      // Create a ref-like object (React refs are just objects with 'current' property)
      fieldRefsStorage.current[fieldName] = { current: null };
    }
  });

  // Create or use provided refs for each field
  const fieldRefs = useMemo(() => {
    const refs = {};
    fieldNames.forEach((fieldName) => {
      // Use provided ref if available (backward compatibility), otherwise use created ref
      if (
        typeof focusFields === 'object' &&
        !Array.isArray(focusFields) &&
        focusFields[fieldName]
      ) {
        refs[fieldName] = focusFields[fieldName];
      } else {
        refs[fieldName] = fieldRefsStorage.current[fieldName];
      }
    });
    return refs;
  }, [fieldNames, focusFields]);

  const handleError = useCallback(
    (errors) => {
      // Focus on first error field
      const errorField = Object.keys(errors)[0];
      if (errorField && fieldRefs[errorField]?.current) {
        fieldRefs[errorField].current?.focus();
      }

      // Call custom error handler
      onError?.(errors);
    },
    [onError, fieldRefs]
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
        onFinish: (result) => {
          if (!form.errors || Object.keys(form.errors).length === 0) {
            onFinish?.(result);
          }
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
    [form, routeName, method, preserveScroll, onSuccess, handleError, onFinish]
  );

  return {
    ...form,
    submit,
    fieldRefs,
  };
}
