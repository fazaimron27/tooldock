/**
 * Edit user page with form for updating existing users
 * Pre-fills form fields with current user data
 * Uses React Hook Form for improved performance and validation
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { Link } from '@inertiajs/react';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

import { updateUserResolver } from '../../Schemas/userSchemas';

export default function Edit({ user, roles = [] }) {
  const form = useInertiaForm(
    {
      name: user.name || '',
      email: user.email || '',
      password: '',
      password_confirmation: '',
      roles: user.roles?.map((role) => role.id) || [],
    },
    {
      resolver: updateUserResolver,
      toast: {
        success: 'User updated successfully!',
        error: 'Failed to update user. Please check the form for errors.',
      },
    }
  );

  const handleRoleToggle = (roleId) => {
    const currentRoles = form.watch('roles') || [];
    if (currentRoles.includes(roleId)) {
      form.setValue(
        'roles',
        currentRoles.filter((id) => id !== roleId),
        { shouldValidate: false }
      );
    } else {
      form.setValue('roles', [...currentRoles, roleId], { shouldValidate: false });
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('core.users.update', { user: user.id }));
  };

  return (
    <DashboardLayout header="Users">
      <PageShell title="Edit User">
        <div className="space-y-6">
          <FormCard title="Edit User" description="Update user information" className="max-w-3xl">
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <FormFieldRHF
                name="name"
                control={form.control}
                label="Name"
                required
                placeholder="Enter full name"
              />

              <FormFieldRHF
                name="email"
                control={form.control}
                label="Email"
                type="email"
                required
                placeholder="Enter email address"
              />

              <FormFieldRHF
                name="password"
                control={form.control}
                label="Password"
                type="password"
                placeholder="Leave empty to keep current password"
              />

              <FormFieldRHF
                name="password_confirmation"
                control={form.control}
                label="Confirm Password"
                type="password"
                placeholder="Confirm new password"
              />

              <div className="space-y-4">
                <Label>Roles</Label>
                <div className="space-y-3 rounded-md border p-4">
                  {roles.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No roles available</p>
                  ) : (
                    roles.map((role) => (
                      <div key={role.id} className="flex items-center space-x-2">
                        <Checkbox
                          id={`role-${role.id}`}
                          checked={form.watch('roles')?.includes(role.id) || false}
                          onCheckedChange={() => handleRoleToggle(role.id)}
                        />
                        <Label
                          htmlFor={`role-${role.id}`}
                          className="text-sm font-normal cursor-pointer"
                        >
                          {role.name}
                        </Label>
                      </div>
                    ))
                  )}
                </div>
                {form.formState.errors.roles && (
                  <p className="text-sm text-destructive">{form.formState.errors.roles.message}</p>
                )}
              </div>

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={form.formState.isSubmitting}>
                  {form.formState.isSubmitting ? 'Updating...' : 'Update User'}
                </Button>
                <Link href={route('core.users.index')}>
                  <Button type="button" variant="outline">
                    Cancel
                  </Button>
                </Link>
              </div>
            </form>
          </FormCard>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
