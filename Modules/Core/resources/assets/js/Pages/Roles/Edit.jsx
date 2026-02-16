/**
 * Edit role page
 * Uses shared RoleForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import RoleForm, { getRoleDefaults } from '@Core/Components/Forms/RoleForm';
import { updateRoleResolver } from '@Core/Schemas/roleSchemas';
import { ROLES } from '@Core/constants';

import PageShell from '@/Components/Layouts/PageShell';

export default function Edit({ role, groupedPermissions = {} }) {
  const isSuperAdmin = role.name === ROLES.SUPER_ADMIN;

  const form = useInertiaForm(getRoleDefaults(role), {
    resolver: updateRoleResolver,
    toast: {
      success: 'Role updated successfully!',
      error: 'Failed to update role. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('core.roles.update', { role: role.id }));
  };

  return (
    <PageShell title="Edit Role">
      <div className="space-y-6">
        <RoleForm
          control={form.control}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          isEdit
          isSuperAdmin={isSuperAdmin}
          groupedPermissions={groupedPermissions}
        />
      </div>
    </PageShell>
  );
}
