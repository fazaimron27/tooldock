/**
 * Create role page
 * Uses shared RoleForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import RoleForm, { getRoleDefaults } from '@Core/Components/Forms/RoleForm';
import { createRoleResolver } from '@Core/Schemas/roleSchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Create({ groupedPermissions = {} }) {
  const form = useInertiaForm(getRoleDefaults(), {
    resolver: createRoleResolver,
    toast: {
      success: 'Role created successfully!',
      error: 'Failed to create role. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('core.roles.store'));
  };

  return (
    <PageShell title="Create Role">
      <div className="space-y-6">
        <RoleForm
          control={form.control}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          groupedPermissions={groupedPermissions}
        />
      </div>
    </PageShell>
  );
}
