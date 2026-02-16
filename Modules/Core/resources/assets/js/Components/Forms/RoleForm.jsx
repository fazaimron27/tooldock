/**
 * Shared Role Form component - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Handles both create and edit modes, with Super Admin special case
 */
import { Link } from '@inertiajs/react';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PermissionMatrixRHF from '@/Components/Common/PermissionMatrixRHF';
import { Button } from '@/Components/ui/button';

/**
 * Get default form values for role
 */
export function getRoleDefaults(role = null) {
  if (role) {
    return {
      name: role.name || '',
      permissions: role.permissions?.map((p) => p.id) || [],
    };
  }

  return {
    name: '',
    permissions: [],
  };
}

export default function RoleForm({
  control,
  onSubmit,
  isSubmitting = false,
  isEdit = false,
  isSuperAdmin = false,
  groupedPermissions = {},
  cancelUrl,
}) {
  return (
    <FormCard
      title={isEdit ? 'Edit Role' : 'New Role'}
      description={
        isEdit ? 'Update role and permissions' : 'Create a new role and assign permissions'
      }
      className="max-w-4xl"
    >
      <form onSubmit={onSubmit} className="space-y-6" noValidate>
        <FormFieldRHF
          name="name"
          control={control}
          label="Role Name"
          required
          disabled={isSuperAdmin}
          placeholder="Enter role name (e.g., Editor, Manager)"
        />
        {isSuperAdmin && (
          <p className="text-xs text-muted-foreground">
            Super Admin role name cannot be changed for security reasons.
          </p>
        )}

        {isSuperAdmin ? (
          <div className="rounded-md border border-primary/20 bg-primary/5 p-4">
            <p className="text-sm text-muted-foreground">
              <strong className="text-foreground">Super Admin</strong> role bypasses all permission
              checks via{' '}
              <code className="text-xs bg-background px-1 py-0.5 rounded">Gate::before()</code>.
              Permissions are not applicable to this role.
            </p>
          </div>
        ) : (
          <PermissionMatrixRHF
            name="permissions"
            control={control}
            label="Permissions"
            groupedPermissions={groupedPermissions}
          />
        )}

        <div className="flex items-center justify-end gap-4">
          <Link href={cancelUrl || route('core.roles.index')}>
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
                : 'Create Role'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
