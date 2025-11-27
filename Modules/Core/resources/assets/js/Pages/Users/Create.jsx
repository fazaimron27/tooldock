/**
 * Create user page with form for creating new users
 * Includes fields for name, email, password, and role assignment
 */
import { useSmartForm } from '@/Hooks/useSmartForm';
import { Link } from '@inertiajs/react';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Create({ roles = [] }) {
  const form = useSmartForm(
    {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      roles: [],
    },
    {
      toast: {
        success: 'User created successfully!',
        error: 'Failed to create user. Please check the form for errors.',
      },
    }
  );

  const handleRoleToggle = (roleId) => {
    const currentRoles = form.data.roles || [];
    if (currentRoles.includes(roleId)) {
      form.setData(
        'roles',
        currentRoles.filter((id) => id !== roleId)
      );
    } else {
      form.setData('roles', [...currentRoles, roleId]);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('core.users.store'));
  };

  return (
    <DashboardLayout header="Core">
      <PageShell title="Create User">
        <div className="space-y-6">
          <FormCard title="New User" description="Create a new user account" className="max-w-3xl">
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <FormField
                name="name"
                label="Name"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                error={form.errors.name}
                required
                placeholder="Enter full name"
              />

              <FormField
                name="email"
                label="Email"
                type="email"
                value={form.data.email}
                onChange={(e) => form.setData('email', e.target.value)}
                error={form.errors.email}
                required
                placeholder="Enter email address"
              />

              <FormField
                name="password"
                label="Password"
                type="password"
                value={form.data.password}
                onChange={(e) => form.setData('password', e.target.value)}
                error={form.errors.password}
                required
                placeholder="Enter password"
              />

              <FormField
                name="password_confirmation"
                label="Confirm Password"
                type="password"
                value={form.data.password_confirmation}
                onChange={(e) => form.setData('password_confirmation', e.target.value)}
                error={form.errors.password_confirmation}
                required
                placeholder="Confirm password"
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
                          checked={form.data.roles?.includes(role.id) || false}
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
                {form.errors.roles && (
                  <p className="text-sm text-destructive">{form.errors.roles}</p>
                )}
              </div>

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={form.processing}>
                  {form.processing ? 'Creating...' : 'Create User'}
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
