/**
 * Group detail page displaying full group information
 * Shows group details, list of users, and permissions attached to the group
 */
import { useDisclosure } from '@/Hooks/useDisclosure';
import { formatDate, getInitials } from '@/Utils/format';
import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Shield, Trash2, Users } from 'lucide-react';
import { useMemo } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Show({ group, groupedPermissions = {} }) {
  const deleteDialog = useDisclosure();

  const permissionCount = useMemo(() => {
    return Object.values(groupedPermissions).reduce(
      (total, resources) =>
        total + Object.values(resources).reduce((sum, perms) => sum + perms.length, 0),
      0
    );
  }, [groupedPermissions]);

  if (!group || !group.id) {
    return (
      <DashboardLayout header="Groups">
        <PageShell title="Group Not Found">
          <div className="text-center py-8">
            <p className="text-muted-foreground">Group not found or invalid.</p>
            <Link href={route('groups.groups.index')}>
              <Button variant="outline" className="mt-4">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Groups
              </Button>
            </Link>
          </div>
        </PageShell>
      </DashboardLayout>
    );
  }

  const handleDeleteClick = () => {
    deleteDialog.onOpen();
  };

  const handleDeleteConfirm = () => {
    router.delete(route('groups.groups.destroy', { group: group.id }), {
      onSuccess: () => {
        deleteDialog.onClose();
      },
    });
  };

  return (
    <DashboardLayout header="Groups">
      <PageShell
        title={group.name}
        breadcrumbs={[
          { label: 'Groups', href: route('groups.groups.index') },
          { label: group.name },
        ]}
        actions={
          <div className="flex items-center gap-2">
            <Link href={route('groups.groups.edit', { group: group.id })}>
              <Button variant="outline">
                <Pencil className="mr-2 h-4 w-4" />
                Edit
              </Button>
            </Link>
            <Button variant="outline" onClick={handleDeleteClick} className="text-destructive">
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </Button>
            <Link href={route('groups.groups.index')}>
              <Button variant="outline">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back
              </Button>
            </Link>
          </div>
        }
      >
        <div className="space-y-6">
          {/* Group Details */}
          <Card>
            <CardHeader>
              <div className="flex items-center gap-2">
                <Users className="h-5 w-5 text-primary" />
                <CardTitle>Group Details</CardTitle>
              </div>
              <CardDescription>Basic information about this group</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <div className="text-sm font-medium text-muted-foreground">Name</div>
                  <div className="mt-1 font-medium">{group.name}</div>
                </div>
                <div>
                  <div className="text-sm font-medium text-muted-foreground">Slug</div>
                  <div className="mt-1 font-mono text-sm">{group.slug}</div>
                </div>
                {group.description && (
                  <div className="md:col-span-2">
                    <div className="text-sm font-medium text-muted-foreground">Description</div>
                    <div className="mt-1 text-sm">{group.description}</div>
                  </div>
                )}
                <div>
                  <div className="text-sm font-medium text-muted-foreground">Created</div>
                  <div className="mt-1 text-sm">{formatDate(group.created_at, 'full')}</div>
                </div>
                <div>
                  <div className="text-sm font-medium text-muted-foreground">Last Updated</div>
                  <div className="mt-1 text-sm">{formatDate(group.updated_at, 'full')}</div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Members */}
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Users className="h-5 w-5 text-primary" />
                  <CardTitle>Members</CardTitle>
                </div>
                <Badge variant="secondary">
                  {group.users?.length || 0} {group.users?.length === 1 ? 'member' : 'members'}
                </Badge>
              </div>
              <CardDescription>Users assigned to this group</CardDescription>
            </CardHeader>
            <CardContent>
              {!group.users || group.users.length === 0 ? (
                <div className="py-8 text-center text-muted-foreground">
                  <Users className="mx-auto h-12 w-12 opacity-50" />
                  <p className="mt-2">No members assigned to this group</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {group.users.map((user) => (
                    <div
                      key={user.id}
                      className="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent"
                    >
                      <Avatar className="h-10 w-10">
                        {user.avatar?.url ? (
                          <AvatarImage src={user.avatar.url} alt={user.name} />
                        ) : null}
                        <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
                      </Avatar>
                      <div className="flex-1">
                        <Link
                          href={route('core.users.edit', { user: user.id })}
                          className="font-medium hover:underline"
                        >
                          {user.name}
                        </Link>
                        <div className="text-sm text-muted-foreground">{user.email}</div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Permissions */}
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Shield className="h-5 w-5 text-primary" />
                  <CardTitle>Permissions</CardTitle>
                </div>
                <Badge variant="secondary">
                  {permissionCount} {permissionCount === 1 ? 'permission' : 'permissions'}
                </Badge>
              </div>
              <CardDescription>Permissions assigned to this group</CardDescription>
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
        </div>
      </PageShell>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          deleteDialog.onClose();
        }}
        title="Delete Group"
        message={
          group
            ? `Are you sure you want to delete the group "${group.name}"? This action cannot be undone and will remove all members and permissions from this group.`
            : 'Are you sure you want to delete this group?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />
    </DashboardLayout>
  );
}
