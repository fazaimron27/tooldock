import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { usePage } from '@inertiajs/react';
import { updatePasswordResolver } from '@modules/Core/resources/assets/js/Schemas/profileSchemas.js';
import { useEffect } from 'react';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import { Button } from '@/Components/ui/button';

export default function UpdatePasswordForm({ className = '' }) {
  const page = usePage();
  const form = useInertiaForm(
    {
      current_password: '',
      password: '',
      password_confirmation: '',
    },
    {
      resolver: updatePasswordResolver,
      toast: {
        success: 'Password updated successfully!',
        error: 'Failed to update password. Please check the form for errors.',
      },
    }
  );

  useEffect(() => {
    const pageErrors = page?.props?.errors;
    if (pageErrors && Object.keys(pageErrors).length > 0) {
      const formFields = ['current_password', 'password', 'password_confirmation'];
      const relevantErrors = Object.keys(pageErrors)
        .filter((field) => formFields.includes(field))
        .reduce((acc, field) => {
          acc[field] = pageErrors[field];
          return acc;
        }, {});

      if (Object.keys(relevantErrors).length > 0) {
        Object.keys(relevantErrors).forEach((field) => {
          const errorValue = relevantErrors[field];
          const errorMessages = Array.isArray(errorValue) ? errorValue : [errorValue];
          const firstError = errorMessages[0];

          if (firstError) {
            form.setError(field, {
              type: 'server',
              message: String(firstError),
            });
          }
        });
      }
    }
  }, [page?.props?.errors, form]);

  const handleSubmit = (e) => {
    e.preventDefault();

    form.put(route('password.update'), {
      onSuccess: () => {
        form.reset();
      },
    });
  };

  return (
    <FormCard
      title="Update Password"
      description="Ensure your account is using a long, random password to stay secure."
      className={className}
    >
      <form onSubmit={handleSubmit} className="space-y-6" noValidate>
        <FormFieldRHF
          name="current_password"
          control={form.control}
          label="Current Password"
          type="password"
          required
          autoComplete="current-password"
        />

        <FormFieldRHF
          name="password"
          control={form.control}
          label="New Password"
          type="password"
          required
          autoComplete="new-password"
        />

        <FormFieldRHF
          name="password_confirmation"
          control={form.control}
          label="Confirm Password"
          type="password"
          required
          autoComplete="new-password"
        />

        <div className="flex items-center gap-4">
          <Button type="submit" disabled={form.processing}>
            {form.processing ? 'Saving...' : 'Save'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
