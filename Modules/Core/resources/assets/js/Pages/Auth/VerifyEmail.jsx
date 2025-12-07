import AuthLayout from '@Core/Layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, LogOut, Mail } from 'lucide-react';

import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';

export default function VerifyEmail({ status }) {
  const { post, processing } = useForm({});

  const submit = (e) => {
    e.preventDefault();

    post(route('verification.send'));
  };

  return (
    <AuthLayout>
      <Head title="Email Verification" />

      <div className="flex flex-col space-y-2 text-center">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
          <Mail className="h-8 w-8 text-primary" />
        </div>
        <h1 className="text-3xl font-bold tracking-tight">Verify your email</h1>
        <p className="text-muted-foreground">
          Thanks for signing up! Before getting started, could you verify your email address by
          clicking on the link we just emailed to you? If you didn't receive the email, we will
          gladly send you another.
        </p>
      </div>

      {status === 'verification-link-sent' && (
        <div className="rounded-md bg-green-50 p-4 text-sm font-medium text-green-800 dark:bg-green-900/20 dark:text-green-400">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="h-4 w-4" />A new verification link has been sent to the email
            address you provided during registration.
          </div>
        </div>
      )}

      <form onSubmit={submit} className="space-y-4">
        <Button type="submit" className="w-full" disabled={processing}>
          {processing && <Spinner className="mr-2" />}
          {processing ? 'Sending...' : 'Resend Verification Email'}
        </Button>

        <div className="flex items-center justify-center">
          <Button asChild variant="link" className="h-auto p-0">
            <Link
              href={route('logout')}
              method="post"
              as="button"
              className="inline-flex items-center gap-2"
            >
              <LogOut className="h-4 w-4" />
              Log Out
            </Link>
          </Button>
        </div>
      </form>
    </AuthLayout>
  );
}
