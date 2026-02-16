/**
 * Shared User Form component - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Handles both create and edit modes
 */
import { Link } from '@inertiajs/react';

import CheckboxListRHF from '@/Components/Common/CheckboxListRHF';
import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PasswordFieldRHF from '@/Components/Common/PasswordFieldRHF';
import { Button } from '@/Components/ui/button';

/**
 * Get default form values for user
 */
export function getUserDefaults(user = null) {
  if (user) {
    return {
      name: user.name || '',
      email: user.email || '',
      password: '',
      password_confirmation: '',
      roles: user.roles?.map((r) => r.id) || [],
    };
  }

  return {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [],
  };
}

export default function UserForm({
  control,
  onSubmit,
  isSubmitting = false,
  isEdit = false,
  roles = [],
  cancelUrl,
}) {
  return (
    <FormCard
      title={isEdit ? 'Edit User' : 'New User'}
      description={isEdit ? 'Update user account details' : 'Create a new user account'}
      className="max-w-3xl"
    >
      <form onSubmit={onSubmit} className="space-y-6" noValidate>
        <FormFieldRHF
          name="name"
          control={control}
          label="Name"
          required
          placeholder="Enter full name"
        />

        <FormFieldRHF
          name="email"
          control={control}
          label="Email"
          type="email"
          required
          placeholder="Enter email address"
        />

        <PasswordFieldRHF
          name="password"
          control={control}
          label="Password"
          required={!isEdit}
          placeholder={isEdit ? 'Leave blank to keep current password' : 'Enter password'}
          autoComplete="new-password"
        />

        <PasswordFieldRHF
          name="password_confirmation"
          control={control}
          label="Confirm Password"
          required={!isEdit}
          placeholder="Confirm password"
          autoComplete="new-password"
        />

        <CheckboxListRHF
          name="roles"
          control={control}
          label="Roles"
          options={roles}
          emptyMessage="No roles available"
          getOptionId={(role) => role.id}
          getOptionLabel={(role) => role.name}
        />

        <div className="flex items-center justify-end gap-4">
          <Link href={cancelUrl || route('core.users.index')}>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </Link>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? isEdit
                ? 'Saving...'
                : 'Creating...'
              : isEdit
                ? 'Save Changes'
                : 'Create User'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
