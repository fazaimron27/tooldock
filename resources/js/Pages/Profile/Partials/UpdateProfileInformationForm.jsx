import { cn } from '@/lib/utils';
import { Link, useForm, usePage } from '@inertiajs/react';

import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function UpdateProfileInformation({ mustVerifyEmail, status, className = '' }) {
  const user = usePage().props.auth.user;

  const {
    data,
    setData,
    patch,
    errors,
    processing,
    recentlySuccessful: _recentlySuccessful,
  } = useForm({
    name: user.name,
    email: user.email,
  });

  const submit = (e) => {
    e.preventDefault();

    patch(route('profile.update'));
  };

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>Profile Information</CardTitle>
        <CardDescription>
          Update your account's profile information and email address.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={submit} className="space-y-6">
          <div className="space-y-2">
            <Label htmlFor="name">Name</Label>
            <Input
              id="name"
              value={data.name}
              onChange={(e) => setData('name', e.target.value)}
              required
              autoComplete="name"
              className={cn(errors.name && 'border-destructive')}
            />
            {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
          </div>

          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              value={data.email}
              onChange={(e) => setData('email', e.target.value)}
              required
              autoComplete="username"
              className={cn(errors.email && 'border-destructive')}
            />
            {errors.email && <p className="text-sm text-destructive">{errors.email}</p>}
          </div>

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
      </CardContent>
    </Card>
  );
}
