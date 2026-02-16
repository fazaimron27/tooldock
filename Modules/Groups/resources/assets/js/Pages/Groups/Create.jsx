/**
 * Create group page
 * Uses shared GroupForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import GroupForm, { getGroupDefaults } from '@Groups/Components/Forms/GroupForm';
import { createGroupResolver } from '@Groups/Schemas/groupSchemas';
import { ROLES } from '@Modules/Core/resources/assets/js/constants';
import { useMemo } from 'react';

import PageShell from '@/Components/Layouts/PageShell';

export default function Create({ roles = [], groupedPermissions = {} }) {
  const availableRoles = useMemo(
    () => roles.filter((role) => role.name !== ROLES.SUPER_ADMIN),
    [roles]
  );

  const form = useInertiaForm(getGroupDefaults(), {
    resolver: createGroupResolver,
    toast: {
      success: 'Group created successfully!',
      error: 'Failed to create group. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('groups.groups.store'));
  };

  return (
    <PageShell title="Create Group">
      <div className="space-y-6">
        <GroupForm
          control={form.control}
          watch={form.watch}
          setValue={form.setValue}
          formState={form.formState}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          availableRoles={availableRoles}
          groupedPermissions={groupedPermissions}
        />
      </div>
    </PageShell>
  );
}
