import AuthLayout from '@Core/Layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye, EyeOff, Lock, Mail } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';

export default function ResetPassword({ token, email }) {
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
  const { data, setData, post, processing, errors, reset } = useForm({
    token: token,
    email: email,
    password: '',
    password_confirmation: '',
  });

  const submit = (e) => {
    e.preventDefault();

    post(route('password.store'), {
      onFinish: () => reset('password', 'password_confirmation'),
    });
  };

  return (
    <AuthLayout>
      <Head title="Reset Password" />

      <div className="flex flex-col space-y-2 text-center">
        <h1 className="text-3xl font-bold tracking-tight">Reset your password</h1>
        <p className="text-muted-foreground">Enter your new password below</p>
      </div>

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
              autoComplete="username"
              onChange={(e) => setData('email', e.target.value)}
              required
            />
          </div>
          <InputError message={errors.email} />
        </div>

        <div className="space-y-2">
          <Label htmlFor="password">Password</Label>
          <div className="relative">
            <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              id="password"
              type={showPassword ? 'text' : 'password'}
              name="password"
              value={data.password}
              className="pl-10 pr-10"
              autoComplete="new-password"
              autoFocus
              onChange={(e) => setData('password', e.target.value)}
              required
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
              aria-label={showPassword ? 'Hide password' : 'Show password'}
            >
              {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>
          <InputError message={errors.password} />
        </div>

        <div className="space-y-2">
          <Label htmlFor="password_confirmation">Confirm Password</Label>
          <div className="relative">
            <Lock className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              id="password_confirmation"
              type={showPasswordConfirmation ? 'text' : 'password'}
              name="password_confirmation"
              value={data.password_confirmation}
              className="pl-10 pr-10"
              autoComplete="new-password"
              onChange={(e) => setData('password_confirmation', e.target.value)}
              required
            />
            <button
              type="button"
              onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
              aria-label={showPasswordConfirmation ? 'Hide password' : 'Show password'}
            >
              {showPasswordConfirmation ? (
                <EyeOff className="h-4 w-4" />
              ) : (
                <Eye className="h-4 w-4" />
              )}
            </button>
          </div>
          <InputError message={errors.password_confirmation} />
        </div>

        <Button type="submit" className="w-full" disabled={processing}>
          {processing && <Spinner className="mr-2" />}
          {processing ? 'Resetting password...' : 'Reset Password'}
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
