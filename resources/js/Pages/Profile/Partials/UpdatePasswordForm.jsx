import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { updatePasswordResolver } from '@Modules/Core/resources/assets/js/Schemas/profileSchemas.js';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import FormCard from '@/Components/Common/FormCard';
import PasswordFieldRHF from '@/Components/Common/PasswordFieldRHF';
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
        <PasswordFieldRHF
          name="current_password"
          control={form.control}
          label="Current Password"
          required
          autoComplete="current-password"
        />

        <PasswordFieldRHF
          name="password"
          control={form.control}
          label="New Password"
          required
          autoComplete="new-password"
        />

        <PasswordFieldRHF
          name="password_confirmation"
          control={form.control}
          label="Confirm Password"
          required
          autoComplete="new-password"
        />

        <div className="flex items-center justify-end gap-4">
          <Button type="submit" disabled={form.processing}>
            {form.processing ? 'Saving...' : 'Save'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
