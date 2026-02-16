/**
 * Create user page
 * Uses shared UserForm component with useInertiaForm
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import UserForm, { getUserDefaults } from '@Core/Components/Forms/UserForm';
import { createUserResolver } from '@Core/Schemas/userSchemas';

import PageShell from '@/Components/Layouts/PageShell';

export default function Create({ roles = [] }) {
  const form = useInertiaForm(getUserDefaults(), {
    resolver: createUserResolver,
    toast: {
      success: 'User created successfully!',
      error: 'Failed to create user. Please check the form for errors.',
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('core.users.store'));
  };

  return (
    <PageShell title="Create User">
      <div className="space-y-6">
        <UserForm
          control={form.control}
          onSubmit={handleSubmit}
          isSubmitting={form.formState.isSubmitting}
          roles={roles}
        />
      </div>
    </PageShell>
  );
}
