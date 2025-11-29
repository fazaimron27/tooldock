/**
 * Edit role page with form for updating existing roles
 * Includes permission matrix grouped by resource/module with pre-selected permissions
 */
import { useSmartForm } from '@/Hooks/useSmartForm';
import { Link } from '@inertiajs/react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

import { ROLES } from '../../constants';

export default function Edit({ role, groupedPermissions = {} }) {
  const isSuperAdmin = role.name === ROLES.SUPER_ADMIN;

  const form = useSmartForm(
    {
      name: role.name || '',
      permissions: role.permissions?.map((p) => p.id) || [],
    },
    {
      toast: {
        success: 'Role updated successfully!',
        error: 'Failed to update role. Please check the form for errors.',
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

  const handlePermissionToggle = (permissionId) => {
    const currentPermissions = form.data.permissions || [];
    if (currentPermissions.includes(permissionId)) {
      form.setData(
        'permissions',
        currentPermissions.filter((id) => id !== permissionId)
      );
    } else {
      form.setData('permissions', [...currentPermissions, permissionId]);
    }
  };

  const handleSelectAllInResource = (module, resource) => {
    const resourcePermissions = groupedPermissions[module]?.[resource] || [];
    const currentPermissions = form.data.permissions || [];
    const resourcePermissionIds = resourcePermissions.map((p) => p.id);

    const allSelected = resourcePermissionIds.every((id) => currentPermissions.includes(id));

    if (allSelected) {
      form.setData(
        'permissions',
        currentPermissions.filter((id) => !resourcePermissionIds.includes(id))
      );
    } else {
      const newPermissions = [...new Set([...currentPermissions, ...resourcePermissionIds])];
      form.setData('permissions', newPermissions);
    }
  };

  const formatPermissionName = (name) => {
    // Handle new format: module.resource.action -> show "Action"
    if (name.includes('.')) {
      const parts = name.split('.');
      const action = parts[parts.length - 1];
      return action.charAt(0).toUpperCase() + action.slice(1);
    }
    // Handle old format: "action resource" -> show as is
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
    form.put(route('core.roles.update', { role: role.id }));
  };

  return (
    <DashboardLayout header="Core">
      <PageShell title="Edit Role">
        <div className="space-y-6">
          <FormCard
            title="Edit Role"
            description="Update role information and permissions"
            className="max-w-4xl"
          >
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              <FormField
                name="name"
                label="Role Name"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                error={form.errors.name}
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
                    <strong className="text-foreground">Super Admin</strong> role bypasses all
                    permission checks via{' '}
                    <code className="text-xs bg-background px-1 py-0.5 rounded">
                      Gate::before()
                    </code>
                    . Permissions are not applicable to this role.
                  </p>
                </div>
              ) : (
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <Label className="text-base font-semibold">Permissions</Label>
                    <span className="text-sm text-muted-foreground">
                      {form.data.permissions?.length || 0} selected
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

                        // Calculate total permissions for module
                        const allModulePermissions = resourceKeys.flatMap(
                          (resource) => moduleResources[resource] || []
                        );
                        const modulePermissionIds = allModulePermissions.map((p) => p.id);
                        const moduleSelectedCount = (form.data.permissions || []).filter((id) =>
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
                                  const resourcePermissionIds = resourcePermissions.map(
                                    (p) => p.id
                                  );
                                  const resourceSelectedCount = (
                                    form.data.permissions || []
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
                                                  form.data.permissions?.includes(permission.id) ||
                                                  false
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
                  {form.errors.permissions && (
                    <p className="text-sm text-destructive">{form.errors.permissions}</p>
                  )}
                </div>
              )}

              <div className="flex items-center gap-4">
                <Button type="submit" disabled={form.processing}>
                  {form.processing ? 'Updating...' : 'Update Role'}
                </Button>
                <Link href={route('core.roles.index')}>
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
