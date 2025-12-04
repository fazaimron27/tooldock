import { useInertiaForm } from '@/Hooks/useInertiaForm';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import { Button } from '@/Components/ui/button';

export default function UpdatePasswordForm({ className = '' }) {
  const form = useInertiaForm(
    {
      current_password: '',
      password: '',
      password_confirmation: '',
    },
    {
      toast: {
        success: 'Password updated successfully!',
        error: 'Failed to update password. Please check the form for errors.',
      },
    }
  );

  const handleSubmit = (e) => {
    e.preventDefault();

    form.put(route('password.update'), {
      onSuccess: () => {
        form.reset();
      },
      /**
       * Don't clear fields on error to allow error display.
       * mapServerErrors in useInertiaForm handles error mapping automatically.
       */
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
          rules={{
            required: 'Current password is required',
          }}
        />

        <FormFieldRHF
          name="password"
          control={form.control}
          label="New Password"
          type="password"
          required
          autoComplete="new-password"
          rules={{
            required: 'New password is required',
            minLength: {
              value: 8,
              message: 'Password must be at least 8 characters',
            },
          }}
        />

        <FormFieldRHF
          name="password_confirmation"
          control={form.control}
          label="Confirm Password"
          type="password"
          required
          autoComplete="new-password"
          rules={{
            required: 'Password confirmation is required',
            validate: (value) => {
              const password = form.getValues('password');
              return value === password || 'Passwords do not match';
            },
          }}
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
