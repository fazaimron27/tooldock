/**
 * Create group page with form for creating new groups
 * Includes member selection and permission matrix grouped by resource/module
 * Uses React Hook Form for improved performance and validation
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { Link } from '@inertiajs/react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

import MemberSelect from '../../Components/MemberSelect';
import { createGroupResolver } from '../../Schemas/groupSchemas';

export default function Create({ users = [], groupedPermissions = {} }) {
  const form = useInertiaForm(
    {
      name: '',
      description: '',
      members: [],
      permissions: [],
    },
    {
      resolver: createGroupResolver,
      toast: {
        success: 'Group created successfully!',
        error: 'Failed to create group. Please check the form for errors.',
      },
    }
  );

  const [openModules, setOpenModules] = useState({});
  const [openResources, setOpenResources] = useState({});

  const toggleModule = (module) => {
    setOpenModules((prev) => ({
      ...prev,
      [module]: !prev[module],
    }));
  };

  const toggleResource = (module, resource) => {
    const key = `${module}.${resource}`;
    setOpenResources((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  };

  const userOptions = useMemo(() => {
    return users.map((user) => ({
      label: user.name,
      value: user.id,
      email: user.email,
    }));
  }, [users]);

  const handlePermissionToggle = (permissionId) => {
    const currentPermissions = form.watch('permissions') || [];
    if (currentPermissions.includes(permissionId)) {
      form.setValue(
        'permissions',
        currentPermissions.filter((id) => id !== permissionId),
        { shouldValidate: false }
      );
    } else {
      form.setValue('permissions', [...currentPermissions, permissionId], {
        shouldValidate: false,
      });
    }
  };

  const handleSelectAllInResource = (module, resource) => {
    const resourcePermissions = groupedPermissions[module]?.[resource] || [];
    const currentPermissions = form.watch('permissions') || [];
    const resourcePermissionIds = resourcePermissions.map((p) => p.id);

    const allSelected = resourcePermissionIds.every((id) => currentPermissions.includes(id));

    if (allSelected) {
      form.setValue(
        'permissions',
        currentPermissions.filter((id) => !resourcePermissionIds.includes(id)),
        { shouldValidate: false }
      );
    } else {
      const newPermissions = [...new Set([...currentPermissions, ...resourcePermissionIds])];
      form.setValue('permissions', newPermissions, { shouldValidate: false });
    }
  };

  /**
   * Format permission name for display.
   * Handles both new format (module.resource.action) and old format (action resource).
   */
  const formatPermissionName = (name) => {
    if (name.includes('.')) {
      const parts = name.split('.');
      const action = parts[parts.length - 1];
      return action.charAt(0).toUpperCase() + action.slice(1);
    }
    return name
      .split(' ')
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  };

  const formatModuleName = (module) => {
    return module.charAt(0).toUpperCase() + module.slice(1);
  };

  const formatResourceName = (resource) => {
    return resource.charAt(0).toUpperCase() + resource.slice(1);
  };

  const moduleKeys = useMemo(() => {
    return Object.keys(groupedPermissions).sort();
  }, [groupedPermissions]);

  const handleSubmit = (e) => {
    e.preventDefault();
    form.post(route('groups.groups.store'));
  };

  return (
    <DashboardLayout header="Groups">
      <PageShell title="Create Group">
        <div className="space-y-6">
          <FormCard
            title="New Group"
            description="Create a new group and assign members and permissions"
            className="max-w-4xl"
          >
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <FormFieldRHF
                name="name"
                control={form.control}
                label="Group Name"
                required
                placeholder="Enter group name (e.g., Editors, Marketing Team)"
              />

              <FormTextareaRHF
                name="description"
                control={form.control}
                label="Description"
                placeholder="Enter group description (optional)"
              />

              <div className="space-y-4">
                <Label>Members</Label>
                <MemberSelect
                  options={userOptions}
                  value={form.watch('members') || []}
                  onChange={(selected) =>
                    form.setValue('members', selected, { shouldValidate: false })
                  }
                  placeholder="Select users for this group"
                  emptyMessage="No users found."
                />
                {form.formState.errors.members && (
                  <p className="text-sm text-destructive">
                    {form.formState.errors.members.message}
                  </p>
                )}
              </div>

              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <Label className="text-base font-semibold">Permissions</Label>
                  <span className="text-sm text-muted-foreground">
                    {form.watch('permissions')?.length || 0} selected
                  </span>
                </div>

                {moduleKeys.length === 0 ? (
                  <p className="text-sm text-muted-foreground">No permissions available</p>
                ) : (
                  <div className="space-y-2 rounded-md border p-4">
                    {moduleKeys.map((module) => {
                      const moduleResources = groupedPermissions[module] || {};
                      const resourceKeys = Object.keys(moduleResources).sort();
                      const isModuleOpen = openModules[module] ?? true;

                      const allModulePermissions = resourceKeys.flatMap(
                        (resource) => moduleResources[resource] || []
                      );
                      const modulePermissionIds = allModulePermissions.map((p) => p.id);
                      const moduleSelectedCount = (form.watch('permissions') || []).filter((id) =>
                        modulePermissionIds.includes(id)
                      ).length;

                      return (
                        <Collapsible
                          key={module}
                          open={isModuleOpen}
                          onOpenChange={() => toggleModule(module)}
                        >
                          <div className="flex items-center gap-2 border-b pb-2">
                            <CollapsibleTrigger asChild>
                              <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                {isModuleOpen ? (
                                  <ChevronDown className="h-4 w-4" />
                                ) : (
                                  <ChevronRight className="h-4 w-4" />
                                )}
                              </Button>
                            </CollapsibleTrigger>
                            <div className="flex flex-1 items-center justify-between">
                              <Label
                                className="text-sm font-semibold cursor-pointer"
                                onClick={() => toggleModule(module)}
                              >
                                {formatModuleName(module)} ({moduleSelectedCount}/
                                {allModulePermissions.length})
                              </Label>
                            </div>
                          </div>
                          <CollapsibleContent className="pt-2">
                            <div className="space-y-2 pl-6">
                              {resourceKeys.map((resource) => {
                                const resourcePermissions = moduleResources[resource] || [];
                                const resourceKey = `${module}.${resource}`;
                                const isResourceOpen = openResources[resourceKey] ?? true;
                                const resourcePermissionIds = resourcePermissions.map((p) => p.id);
                                const resourceSelectedCount = (
                                  form.watch('permissions') || []
                                ).filter((id) => resourcePermissionIds.includes(id)).length;
                                const allResourceSelected =
                                  resourcePermissions.length > 0 &&
                                  resourceSelectedCount === resourcePermissions.length;

                                return (
                                  <Collapsible
                                    key={resourceKey}
                                    open={isResourceOpen}
                                    onOpenChange={() => toggleResource(module, resource)}
                                  >
                                    <div className="flex items-center gap-2 border-b pb-2">
                                      <CollapsibleTrigger asChild>
                                        <Button variant="ghost" size="sm" className="h-7 w-7 p-0">
                                          {isResourceOpen ? (
                                            <ChevronDown className="h-3 w-3" />
                                          ) : (
                                            <ChevronRight className="h-3 w-3" />
                                          )}
                                        </Button>
                                      </CollapsibleTrigger>
                                      <div className="flex flex-1 items-center justify-between">
                                        <Label
                                          className="text-sm font-medium cursor-pointer"
                                          onClick={() => toggleResource(module, resource)}
                                        >
                                          {formatResourceName(resource)} ({resourceSelectedCount}/
                                          {resourcePermissions.length})
                                        </Label>
                                        <Button
                                          type="button"
                                          variant="ghost"
                                          size="sm"
                                          className="h-7 text-xs"
                                          onClick={(e) => {
                                            e.preventDefault();
                                            handleSelectAllInResource(module, resource);
                                          }}
                                        >
                                          {allResourceSelected ? 'Deselect All' : 'Select All'}
                                        </Button>
                                      </div>
                                    </div>
                                    <CollapsibleContent className="pt-2">
                                      <div className="space-y-2 pl-6">
                                        {resourcePermissions.map((permission) => (
                                          <div
                                            key={permission.id}
                                            className="flex items-center space-x-2"
                                          >
                                            <Checkbox
                                              id={`permission-${permission.id}`}
                                              checked={
                                                form
                                                  .watch('permissions')
                                                  ?.includes(permission.id) || false
                                              }
                                              onCheckedChange={() =>
                                                handlePermissionToggle(permission.id)
                                              }
                                            />
                                            <Label
                                              htmlFor={`permission-${permission.id}`}
                                              className="text-sm font-normal cursor-pointer"
                                            >
                                              {formatPermissionName(permission.name)}
                                            </Label>
                                          </div>
                                        ))}
                                      </div>
                                    </CollapsibleContent>
                                  </Collapsible>
                                );
                              })}
                            </div>
                          </CollapsibleContent>
                        </Collapsible>
                      );
                    })}
                  </div>
                )}
                {form.formState.errors.permissions && (
                  <p className="text-sm text-destructive">
                    {form.formState.errors.permissions.message}
                  </p>
                )}
              </div>

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={form.formState.isSubmitting}>
                  {form.formState.isSubmitting ? 'Creating...' : 'Create Group'}
                </Button>
                <Link href={route('groups.groups.index')}>
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
