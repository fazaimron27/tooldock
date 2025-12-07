import AuthLayout from '@Core/Layouts/AuthLayout';
import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Lock } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';

export default function ConfirmPassword() {
  const [showPassword, setShowPassword] = useState(false);
  const { data, setData, post, processing, errors, reset } = useForm({
    password: '',
  });

  const submit = (e) => {
    e.preventDefault();

    post(route('password.confirm'), {
      onFinish: () => reset('password'),
    });
  };

  return (
    <AuthLayout>
      <Head title="Confirm Password" />

      <div className="flex flex-col space-y-2 text-center">
        <h1 className="text-3xl font-bold tracking-tight">Confirm your password</h1>
        <p className="text-muted-foreground">
          This is a secure area of the application. Please confirm your password before continuing.
        </p>
      </div>

      <form onSubmit={submit} className="space-y-4">
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
              autoComplete="current-password"
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

        <Button type="submit" className="w-full" disabled={processing}>
          {processing && <Spinner className="mr-2" />}
          {processing ? 'Confirming...' : 'Confirm'}
        </Button>
      </form>
    </AuthLayout>
  );
}
