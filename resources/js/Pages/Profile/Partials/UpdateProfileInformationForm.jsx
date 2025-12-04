import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { Link, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { Controller } from 'react-hook-form';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FilePicker from '@/Components/Form/FilePicker';
import { Button } from '@/Components/ui/button';

export default function UpdateProfileInformation({
  mustVerifyEmail,
  status,
  avatar,
  className = '',
}) {
  const user = usePage().props.auth.user;

  const form = useInertiaForm(
    {
      name: user.name,
      email: user.email,
      avatar_id: avatar?.id || null,
    },
    {
      toast: {
        success: 'Profile updated successfully!',
        error: 'Failed to update profile. Please check the form for errors.',
      },
    }
  );

  const handleSubmit = (e) => {
    e.preventDefault();
    form.patch(route('profile.update'));
  };

  return (
    <FormCard
      title="Profile Information"
      description="Update your account's profile information and email address."
      className={className}
    >
      <form onSubmit={handleSubmit} className="space-y-6" noValidate>
        <Controller
          name="avatar_id"
          control={form.control}
          render={({ field, fieldState: { error } }) => (
            <div className="space-y-2">
              <FilePicker
                label="Avatar"
                value={avatar?.url || (field.value ? String(field.value) : null)}
                onChange={(value) => {
                  field.onChange(value ? parseInt(value) : null);
                }}
                accept="image/*"
                directory="avatars"
                error={error?.message}
              />
              {error && <p className="text-sm text-destructive">{error.message}</p>}
            </div>
          )}
        />

        <FormFieldRHF
          name="name"
          control={form.control}
          label="Name"
          required
          autoComplete="name"
          rules={{
            required: 'Name is required',
            maxLength: {
              value: 255,
              message: 'Name must not exceed 255 characters',
            },
          }}
        />

        <FormFieldRHF
          name="email"
          control={form.control}
          label="Email"
          type="email"
          required
          autoComplete="username"
          rules={{
            required: 'Email is required',
            pattern: {
              value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
              message: 'Invalid email address',
            },
          }}
        />

        {mustVerifyEmail && user.email_verified_at === null && (
          <div className="rounded-md border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
            <p className="text-sm text-yellow-800 dark:text-yellow-200">
              Your email address is unverified.{' '}
              <Link
                href={route('verification.send')}
                method="post"
                as="button"
                className="font-medium underline hover:no-underline"
              >
                Click here to re-send the verification email.
              </Link>
            </p>

            {status === 'verification-link-sent' && (
              <div className="mt-2 flex items-center gap-2 text-sm font-medium text-green-600 dark:text-green-400">
                <CheckCircle2 className="h-4 w-4" />A new verification link has been sent to your
                email address.
              </div>
            )}
          </div>
        )}

        <div className="flex items-center gap-4">
          <Button type="submit" disabled={form.processing}>
            {form.processing ? 'Saving...' : 'Save'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
