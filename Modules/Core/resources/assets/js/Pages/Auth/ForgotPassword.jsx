import AuthLayout from '@Core/Layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Mail } from 'lucide-react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';

export default function ForgotPassword({ status }) {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
  });

  const submit = (e) => {
    e.preventDefault();

    post(route('password.email'));
  };

  return (
    <AuthLayout>
      <Head title="Forgot Password" />

      <div className="flex flex-col space-y-2 text-center">
        <h1 className="text-3xl font-bold tracking-tight">Forgot your password?</h1>
        <p className="text-muted-foreground">
          No problem. Just let us know your email address and we will email you a password reset
          link that will allow you to choose a new one.
        </p>
      </div>

      {status && (
        <div className="rounded-md bg-green-50 p-4 text-sm font-medium text-green-800 dark:bg-green-900/20 dark:text-green-400">
          {status}
        </div>
      )}

      <form onSubmit={submit} className="space-y-4">
        <div className="space-y-2">
          <Label htmlFor="email">Email</Label>
          <div className="relative">
            <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              id="email"
              type="email"
              name="email"
              value={data.email}
              className="pl-10"
              autoComplete="email"
              autoFocus
              onChange={(e) => setData('email', e.target.value)}
              required
            />
          </div>
          <InputError message={errors.email} />
        </div>

        <Button type="submit" className="w-full" disabled={processing}>
          {processing && <Spinner className="mr-2" />}
          {processing ? 'Sending...' : 'Email Password Reset Link'}
        </Button>

        <div className="text-center text-sm">
          <Button asChild variant="link" className="h-auto p-0">
            <Link href={route('login')} className="inline-flex items-center gap-2">
              <ArrowLeft className="h-4 w-4" />
              Back to login
            </Link>
          </Button>
        </div>
      </form>
    </AuthLayout>
  );
}
