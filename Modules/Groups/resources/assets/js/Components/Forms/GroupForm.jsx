/**
 * Shared Group Form component - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Handles both create and edit modes
 */
import MemberSelect from '@Groups/Components/MemberSelect';
import { Link } from '@inertiajs/react';
import { useMemo } from 'react';

import CheckboxListRHF from '@/Components/Common/CheckboxListRHF';
import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import PermissionMatrixRHF from '@/Components/Common/PermissionMatrixRHF';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

/**
 * Get default form values for group
 */
export function getGroupDefaults(group = null, includeMembers = true) {
  if (group) {
    const defaults = {
      name: group.name || '',
      description: group.description || '',
      roles: group.roles?.map((r) => r.id) || [],
      permissions: group.permissions?.map((p) => p.id) || [],
    };

    if (includeMembers) {
      defaults.members = group.users?.map((u) => u.id) || [];
    }

    return defaults;
  }

  const defaults = {
    name: '',
    description: '',
    roles: [],
    permissions: [],
  };

  if (includeMembers) {
    defaults.members = [];
  }

  return defaults;
}

export default function GroupForm({
  control,
  watch,
  setValue,
  formState,
  onSubmit,
  isSubmitting = false,
  isEdit = false,
  showMembers = true,
  availableRoles = [],
  groupedPermissions = {},
  cancelUrl,
}) {
  const watchedRoles = watch('roles');

  const inheritedPermissionsMap = useMemo(() => {
    const selectedRoleIds = watchedRoles || [];
    const selectedRoles = availableRoles.filter((role) => selectedRoleIds.includes(role.id));

    const permissionToRoles = new Map();

    selectedRoles.forEach((role) => {
      (role.permissions || []).forEach((permission) => {
        if (!permissionToRoles.has(permission.id)) {
          permissionToRoles.set(permission.id, []);
        }
        permissionToRoles.get(permission.id).push(role.name);
      });
    });

    return permissionToRoles;
  }, [watchedRoles, availableRoles]);

  return (
    <FormCard
      title={isEdit ? 'Edit Group' : 'New Group'}
      description={
        isEdit
          ? 'Update group details, roles, and permissions' +
            (!showMembers ? '. To manage members, visit the group detail page.' : '')
          : 'Create a new group and assign members and permissions'
      }
      className="max-w-4xl"
    >
      <form onSubmit={onSubmit} className="space-y-6" noValidate>
        <FormFieldRHF
          name="name"
          control={control}
          label="Group Name"
          required
          placeholder="Enter group name (e.g., Editors, Marketing Team)"
        />

        <FormTextareaRHF
          name="description"
          control={control}
          label="Description"
          placeholder="Enter group description (optional)"
        />

        {/* Members (only for create mode) */}
        {showMembers && (
          <div className="space-y-4">
            <Label>Members</Label>
            <MemberSelect
              value={watch('members') || []}
              onChange={(selected) => setValue('members', selected, { shouldValidate: false })}
              placeholder="Select users for this group"
              emptyMessage="No users found."
            />
            {formState.errors.members && (
              <p className="text-sm text-destructive">{formState.errors.members.message}</p>
            )}
          </div>
        )}

        {/* Base Roles */}
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <Label className="text-base font-semibold">Base Roles</Label>
            <span className="text-sm text-muted-foreground">
              {watch('roles')?.length || 0} selected
            </span>
          </div>
          <p className="text-sm text-muted-foreground">
            Select base roles that members of this group will inherit. Members will receive all
            permissions from these roles.
          </p>
          <CheckboxListRHF
            name="roles"
            control={control}
            options={availableRoles}
            emptyMessage="No roles available"
            getOptionId={(role) => role.id}
            getOptionLabel={(role) => role.name}
          />
        </div>

        {/* Ad-Hoc Permissions */}
        <div className="space-y-4">
          <p className="text-sm text-muted-foreground">
            Select additional permissions that members of this group will inherit. Members will
            receive BOTH the selected base roles AND these ad-hoc permissions.
          </p>
          <PermissionMatrixRHF
            name="permissions"
            control={control}
            label="Ad-Hoc Permissions"
            groupedPermissions={groupedPermissions}
            inheritedPermissionsMap={inheritedPermissionsMap}
            showInheritedBadge
          />
        </div>

        <div className="flex items-center justify-end gap-4">
          <Link href={cancelUrl || route('groups.groups.index')}>
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
                : 'Create Group'}
          </Button>
        </div>
      </form>
    </FormCard>
  );
}
