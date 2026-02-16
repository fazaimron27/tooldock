/**
 * Edit group page
 * Uses shared GroupForm component with useInertiaForm
 * Note: Member management is handled on the Show page, not here
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import GroupForm, { getGroupDefaults } from '@Groups/Components/Forms/GroupForm';
import { updateGroupResolver } from '@Groups/Schemas/groupSchemas';
import { ROLES } from '@Modules/Core/resources/assets/js/constants';
import { useMemo } from 'react';

import PageShell from '@/Components/Layouts/PageShell';

export default function Edit({ group, roles = [], groupedPermissions = {} }) {
  const availableRoles = useMemo(
    () => roles.filter((role) => role.name !== ROLES.SUPER_ADMIN),
    [roles]
  );

  // Edit mode doesn't include members (managed on Show page)
  const form = useInertiaForm(getGroupDefaults(group, false), {
    resolver: updateGroupResolver,
    toast: {
      success: 'Group updated successfully!',
      error: 'Failed to update group. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('groups.groups.update', { group: group.id }));
  };

  return (
    <PageShell title="Edit Group">
      <div className="space-y-6">
        <GroupForm
          control={form.control}
          watch={form.watch}
          setValue={form.setValue}
          formState={form.formState}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          isEdit
          showMembers={false}
          availableRoles={availableRoles}
          groupedPermissions={groupedPermissions}
        />
      </div>
    </PageShell>
  );
}
