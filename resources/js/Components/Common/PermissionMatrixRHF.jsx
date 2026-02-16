/**
 * Permission Matrix field component for React Hook Form
 * Renders a hierarchical permission selector grouped by module and resource
 * Used in Role and Group management
 */
import { cn } from '@/Utils/utils';
import { ChevronDown, ChevronRight, Info } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Controller } from 'react-hook-form';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Label } from '@/Components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

/**
 * Format permission name for display
 * Handles both new format (module.resource.action) and old format (action resource)
 */
function formatPermissionName(name) {
  if (name.includes('.')) {
    const parts = name.split('.');
    const action = parts[parts.length - 1];
    return action.charAt(0).toUpperCase() + action.slice(1);
  }
  return name
    .split(' ')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function formatModuleName(module) {
  return module.charAt(0).toUpperCase() + module.slice(1);
}

function formatResourceName(resource) {
  return resource.charAt(0).toUpperCase() + resource.slice(1);
}

export default function PermissionMatrixRHF({
  name,
  control,
  label = 'Permissions',
  groupedPermissions = {},
  inheritedPermissionsMap = new Map(),
  showInheritedBadge = false,
  className,
}) {
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

  const moduleKeys = useMemo(() => {
    return Object.keys(groupedPermissions).sort();
  }, [groupedPermissions]);

  const inheritedPermissionIds = useMemo(() => {
    return Array.from(inheritedPermissionsMap.keys());
  }, [inheritedPermissionsMap]);

  const isPermissionInherited = (permissionId) => {
    return inheritedPermissionsMap.has(permissionId);
  };

  const getInheritedRoleNames = (permissionId) => {
    return inheritedPermissionsMap.get(permissionId) || [];
  };

  return (
    <Controller
      name={name}
      control={control}
      render={({ field, fieldState: { error } }) => {
        const selectedPermissions = field.value || [];

        const handlePermissionToggle = (permissionId) => {
          if (selectedPermissions.includes(permissionId)) {
            field.onChange(selectedPermissions.filter((id) => id !== permissionId));
          } else {
            field.onChange([...selectedPermissions, permissionId]);
          }
        };

        const handleSelectAllInResource = (module, resource) => {
          const resourcePermissions = groupedPermissions[module]?.[resource] || [];
          const resourcePermissionIds = resourcePermissions.map((p) => p.id);

          const allSelected = resourcePermissionIds.every((id) => selectedPermissions.includes(id));

          if (allSelected) {
            field.onChange(selectedPermissions.filter((id) => !resourcePermissionIds.includes(id)));
          } else {
            const newPermissions = [...new Set([...selectedPermissions, ...resourcePermissionIds])];
            field.onChange(newPermissions);
          }
        };

        return (
          <div className={cn('space-y-4', className)}>
            <div className="flex items-center justify-between">
              <Label className="text-base font-semibold">{label}</Label>
              <span className="text-sm text-muted-foreground">
                {new Set([...selectedPermissions, ...inheritedPermissionIds]).size} selected
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
                  const allActivePermissions = new Set([
                    ...selectedPermissions,
                    ...inheritedPermissionIds,
                  ]);
                  const moduleSelectedCount = modulePermissionIds.filter((id) =>
                    allActivePermissions.has(id)
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
                            const resourceActivePermissions = new Set([
                              ...selectedPermissions,
                              ...inheritedPermissionIds,
                            ]);
                            const resourceSelectedCount = resourcePermissionIds.filter((id) =>
                              resourceActivePermissions.has(id)
                            ).length;
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
                                    {resourcePermissions.map((permission) => {
                                      const isInherited = isPermissionInherited(permission.id);
                                      const isChecked =
                                        selectedPermissions.includes(permission.id) || isInherited;

                                      return (
                                        <div
                                          key={permission.id}
                                          className="flex items-center space-x-2"
                                        >
                                          <Checkbox
                                            id={`permission-${permission.id}`}
                                            checked={isChecked}
                                            disabled={isInherited}
                                            onCheckedChange={() =>
                                              handlePermissionToggle(permission.id)
                                            }
                                          />
                                          <div className="flex items-center gap-2 flex-1">
                                            <Label
                                              htmlFor={`permission-${permission.id}`}
                                              className={cn(
                                                'text-sm font-normal',
                                                isInherited
                                                  ? 'cursor-not-allowed text-muted-foreground'
                                                  : 'cursor-pointer'
                                              )}
                                            >
                                              {formatPermissionName(permission.name)}
                                            </Label>
                                            {showInheritedBadge &&
                                              isInherited &&
                                              (() => {
                                                const roleNames = getInheritedRoleNames(
                                                  permission.id
                                                );
                                                return (
                                                  <TooltipProvider>
                                                    <Tooltip>
                                                      <TooltipTrigger asChild>
                                                        <Info className="h-3.5 w-3.5 text-muted-foreground" />
                                                      </TooltipTrigger>
                                                      <TooltipContent>
                                                        <p>Inherited via {roleNames.join(', ')}</p>
                                                      </TooltipContent>
                                                    </Tooltip>
                                                  </TooltipProvider>
                                                );
                                              })()}
                                          </div>
                                        </div>
                                      );
                                    })}
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
            {error && <p className="text-sm text-destructive">{error.message}</p>}
          </div>
        );
      }}
    />
  );
}
