/**
 * Create user page with form for creating new users
 * Includes fields for name, email, password, and role assignment
 * Uses React Hook Form for improved performance and validation
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { Link } from '@inertiajs/react';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PasswordFieldRHF from '@/Components/Common/PasswordFieldRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

import { createUserResolver } from '../../Schemas/userSchemas';

export default function Create({ roles = [] }) {
  const form = useInertiaForm(
    {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      roles: [],
    },
    {
      resolver: createUserResolver,
      toast: {
        success: 'User created successfully!',
        error: 'Failed to create user. Please check the form for errors.',
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
    form.post(route('core.users.store'));
  };

  return (
    <DashboardLayout header="Users">
      <PageShell title="Create User">
        <div className="space-y-6">
          <FormCard title="New User" description="Create a new user account" className="max-w-3xl">
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

              <PasswordFieldRHF
                name="password"
                control={form.control}
                label="Password"
                required
                placeholder="Enter password"
                autoComplete="new-password"
              />

              <PasswordFieldRHF
                name="password_confirmation"
                control={form.control}
                label="Confirm Password"
                required
                placeholder="Confirm password"
                autoComplete="new-password"
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

              <div className="flex items-center justify-end gap-4">
                <Link href={route('core.users.index')}>
                  <Button type="button" variant="outline">
                    Cancel
                  </Button>
                </Link>
                <Button type="submit" disabled={form.formState.isSubmitting}>
                  {form.formState.isSubmitting ? 'Creating...' : 'Create User'}
                </Button>
              </div>
            </form>
          </FormCard>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
