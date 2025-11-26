import { useFormWithFocus } from '@/Hooks/useFormWithFocus';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import { Button } from '@/Components/ui/button';

export default function UpdatePasswordForm({ className = '' }) {
  const { data, setData, errors, reset, processing, submit, fieldRefs } = useFormWithFocus(
    {
      current_password: '',
      password: '',
      password_confirmation: '',
    },
    {
      route: 'password.update',
      method: 'put',
      focusFields: ['password', 'current_password'],
      onSuccess: () => reset(),
      onError: (errors) => {
        if (errors.password) {
          reset('password', 'password_confirmation');
        }
        if (errors.current_password) {
          reset('current_password');
        }
      },
    }
  );

  return (
    <FormCard
      title="Update Password"
      description="Ensure your account is using a long, random password to stay secure."
      className={className}
    >
      <form onSubmit={submit} className="space-y-6">
        <FormField
          name="current_password"
          label="Current Password"
          type="password"
          value={data.current_password}
          onChange={(e) => setData('current_password', e.target.value)}
          error={errors.current_password}
          autoComplete="current-password"
          inputRef={fieldRefs.current_password}
        />

        <FormField
          name="password"
          label="New Password"
          type="password"
          value={data.password}
          onChange={(e) => setData('password', e.target.value)}
          error={errors.password}
          autoComplete="new-password"
          inputRef={fieldRefs.password}
        />

        <FormField
          name="password_confirmation"
          label="Confirm Password"
          type="password"
          value={data.password_confirmation}
          onChange={(e) => setData('password_confirmation', e.target.value)}
          error={errors.password_confirmation}
          autoComplete="new-password"
        />

        <div className="flex items-center gap-4">
          <Button type="submit" disabled={processing}>
            {processing ? 'Saving...' : 'Save'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
