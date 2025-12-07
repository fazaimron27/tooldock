/**
 * Card component displaying group roles and permissions.
 */
import { Shield } from 'lucide-react';

import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function PermissionsCard({ group, groupedPermissions, permissionCount }) {
  return (
    <>
      {/* Base Roles */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Shield className="h-5 w-5 text-primary" />
              <CardTitle>Base Roles</CardTitle>
            </div>
            <Badge variant="secondary">
              {group.roles?.length || 0} {group.roles?.length === 1 ? 'role' : 'roles'}
            </Badge>
          </div>
          <CardDescription>
            Roles assigned to this group. Members inherit all permissions from these roles.
          </CardDescription>
        </CardHeader>
        <CardContent>
          {!group.roles || group.roles.length === 0 ? (
            <div className="py-8 text-center text-muted-foreground">
              <Shield className="mx-auto h-12 w-12 opacity-50" />
              <p className="mt-2">No roles assigned to this group</p>
            </div>
          ) : (
            <div className="flex flex-wrap gap-2">
              {group.roles.map((role) => (
                <Badge key={role.id} variant="default" className="text-sm">
                  {role.name}
                </Badge>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Ad-Hoc Permissions */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Shield className="h-5 w-5 text-primary" />
              <CardTitle>Ad-Hoc Permissions</CardTitle>
            </div>
            <Badge variant="secondary">
              {permissionCount} {permissionCount === 1 ? 'permission' : 'permissions'}
            </Badge>
          </div>
          <CardDescription>
            Direct permissions assigned to this group. Members inherit these permissions in addition
            to permissions from base roles.
          </CardDescription>
        </CardHeader>
        <CardContent>
          {permissionCount === 0 ? (
            <div className="py-8 text-center text-muted-foreground">
              <Shield className="mx-auto h-12 w-12 opacity-50" />
              <p className="mt-2">No permissions assigned to this group</p>
            </div>
          ) : (
            <div className="space-y-6">
              {Object.entries(groupedPermissions).map(([module, resources]) => (
                <div key={module} className="space-y-3">
                  <div className="flex items-center gap-2">
                    <h4 className="font-semibold capitalize">{module}</h4>
                    <Badge variant="outline" className="text-xs">
                      {Object.values(resources).reduce((sum, perms) => sum + perms.length, 0)}
                    </Badge>
                  </div>
                  {Object.entries(resources).map(([resource, permissions]) => (
                    <div key={`${module}.${resource}`} className="ml-4 space-y-2">
                      <div className="text-sm font-medium text-muted-foreground capitalize">
                        {resource}
                      </div>
                      <div className="ml-4 flex flex-wrap gap-2">
                        {permissions.map((permission) => (
                          <Badge
                            key={permission.id}
                            variant="secondary"
                            className="font-mono text-xs"
                          >
                            {permission.action}
                          </Badge>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </>
  );
}
