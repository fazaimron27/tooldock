import { useFormHandler } from '@/Hooks/useFormHandler';
import { Link, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import FilePicker from '@/Components/Form/FilePicker';
import { Button } from '@/Components/ui/button';

export default function UpdateProfileInformation({
  mustVerifyEmail,
  status,
  avatar,
  className = '',
}) {
  const user = usePage().props.auth.user;

  const { data, setData, errors, processing, submit } = useFormHandler(
    {
      name: user.name,
      email: user.email,
      avatar_id: avatar?.id || null,
    },
    {
      route: 'profile.update',
      method: 'patch',
    }
  );

  return (
    <FormCard
      title="Profile Information"
      description="Update your account's profile information and email address."
      className={className}
    >
      <form onSubmit={submit} className="space-y-6">
        <FilePicker
          label="Avatar"
          value={avatar?.url || data.avatar_id}
          onChange={(value) => {
            setData('avatar_id', value ? parseInt(value) : null);
          }}
          accept="image/*"
          directory="avatars"
          error={errors.avatar_id}
        />

        <FormField
          name="name"
          label="Name"
          value={data.name}
          onChange={(e) => setData('name', e.target.value)}
          error={errors.name}
          required
          autoComplete="name"
        />

        <FormField
          name="email"
          label="Email"
          type="email"
          value={data.email}
          onChange={(e) => setData('email', e.target.value)}
          error={errors.email}
          required
          autoComplete="username"
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
          <Button type="submit" disabled={processing}>
            {processing ? 'Saving...' : 'Save'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
