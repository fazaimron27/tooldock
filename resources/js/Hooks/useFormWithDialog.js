/**
 * Form handler hook integrated with dialog state management
 * Combines form handling with dialog open/close state and automatic focus management
 */
import { useForm } from '@inertiajs/react';
import { useCallback, useMemo, useRef } from 'react';

import { useDisclosure } from './useDisclosure';

export function useFormWithDialog(initialData, options = {}) {
  const {
    route: routeName,
    method = 'post',
    preserveScroll = true,
    closeOnSuccess = true,
    resetOnClose = true,
    focusFields = [],
    onSuccess,
    onError,
    onFinish,
  } = options;

  const form = useForm(initialData);
  const dialog = useDisclosure();

  // Determine field names that need refs
  const fieldNames = useMemo(() => {
    if (Array.isArray(focusFields)) {
      return focusFields;
    }
    if (typeof focusFields === 'object' && focusFields !== null) {
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

  const handleError = useCallback(
    (errors) => {
      // Focus on first error field if refs are available
      const errorField = Object.keys(errors)[0];
      if (errorField && fieldRefs[errorField]?.current) {
        fieldRefs[errorField].current?.focus();
      }

      onError?.(errors);
      onFinish?.(errors);
    },
    [onError, onFinish, fieldRefs]
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
    [form, routeName, method, preserveScroll, handleSuccess, handleError, onFinish]
  );

  const handleDialogChange = useCallback(
    (isOpen) => {
      if (isOpen) {
        dialog.onOpen();
      } else {
        dialog.onClose();
        if (resetOnClose) {
          form.clearErrors();
          form.reset();
        }
      }
    },
    [dialog, form, resetOnClose]
  );

  return {
    ...form,
    submit,
    dialog,
    handleDialogChange,
    fieldRefs,
  };
}
