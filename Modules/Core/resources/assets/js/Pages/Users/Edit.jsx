/**
 * Edit user page
 * Uses shared UserForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import UserForm, { getUserDefaults } from '@Core/Components/Forms/UserForm';
import { updateUserResolver } from '@Core/Schemas/userSchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Edit({ user, roles = [] }) {
  const form = useInertiaForm(getUserDefaults(user), {
    resolver: updateUserResolver,
    toast: {
      success: 'User updated successfully!',
      error: 'Failed to update user. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('core.users.update', { user: user.id }));
  };

  return (
    <PageShell title="Edit User">
      <div className="space-y-6">
        <UserForm
          control={form.control}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          isEdit
          roles={roles}
        />
      </div>
    </PageShell>
  );
}
