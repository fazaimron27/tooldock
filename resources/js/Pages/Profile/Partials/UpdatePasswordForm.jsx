import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { updatePasswordResolver } from '@modules/Core/resources/assets/js/Schemas/profileSchemas.js';

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
      resolver: updatePasswordResolver,
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
