/**
 * Group detail page displaying full group information
 * Shows group details, list of users, and permissions attached to the group
 */
import { useDisclosure } from '@/Hooks/useDisclosure';
import { formatDate, getInitials } from '@/Utils/format';
import { Link, router } from '@inertiajs/react';
import {
  ArrowLeft,
  ArrowRight,
  ArrowRightLeft,
  Pencil,
  Search,
  Shield,
  Trash2,
  UserPlus,
  Users,
  X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Show({ group, groupedPermissions = {}, availableGroups = [] }) {
  const deleteDialog = useDisclosure();
  const transferDialog = useDisclosure();
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedUser, setSelectedUser] = useState(null);
  const [targetGroupId, setTargetGroupId] = useState('');
  const [isTransferring, setIsTransferring] = useState(false);

  const permissionCount = useMemo(() => {
    return Object.values(groupedPermissions).reduce(
      (total, resources) =>
        total + Object.values(resources).reduce((sum, perms) => sum + perms.length, 0),
      0
    );
  }, [groupedPermissions]);

  // Filter members based on search query
  const filteredMembers = useMemo(() => {
    if (!group.users || group.users.length === 0) {
      return [];
    }

    if (!searchQuery.trim()) {
      return group.users;
    }

    const query = searchQuery.toLowerCase();
    return group.users.filter(
      (user) => user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query)
    );
  }, [group.users, searchQuery]);

  const handleTransferClick = (user) => {
    setSelectedUser(user);
    setTargetGroupId('');
    transferDialog.onOpen();
  };

  const handleTransferConfirm = () => {
    if (!selectedUser || !targetGroupId) {
      return;
    }

    setIsTransferring(true);
    router.post(
      route('groups.transfer-user', { group: group.id }),
      {
        user_id: selectedUser.id,
        target_group_id: targetGroupId,
      },
      {
        onSuccess: () => {
          transferDialog.onClose();
          setSelectedUser(null);
          setTargetGroupId('');
          setIsTransferring(false);
        },
        onError: () => {
          setIsTransferring(false);
        },
      }
    );
  };

  const handleTransferCancel = () => {
    transferDialog.onClose();
    setSelectedUser(null);
    setTargetGroupId('');
  };

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
                <div className="space-y-4">
                  {/* Search Input */}
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      type="text"
                      placeholder="Search members by name or email..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="pl-9 pr-9"
                    />
                    {searchQuery && (
                      <Button
                        variant="ghost"
                        size="sm"
                        className="absolute right-1 top-1/2 h-7 w-7 -translate-y-1/2 p-0"
                        onClick={() => setSearchQuery('')}
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    )}
                  </div>

                  {/* Members List */}
                  {filteredMembers.length === 0 ? (
                    <div className="py-8 text-center text-muted-foreground">
                      <Users className="mx-auto h-12 w-12 opacity-50" />
                      <p className="mt-2">No members found matching your search</p>
                    </div>
                  ) : (
                    <div className="space-y-3">
                      {filteredMembers.map((user) => (
                        <div
                          key={user.id}
                          className="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent"
                        >
                          <Avatar className="h-10 w-10 shrink-0">
                            {user.avatar?.url ? (
                              <AvatarImage src={user.avatar.url} alt={user.name} />
                            ) : null}
                            <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
                          </Avatar>
                          <div className="min-w-0 flex-1">
                            <Link
                              href={route('core.users.edit', { user: user.id })}
                              className="block truncate font-medium hover:underline"
                            >
                              {user.name}
                            </Link>
                            <div className="truncate text-sm text-muted-foreground">
                              {user.email}
                            </div>
                          </div>
                          {availableGroups.length > 0 && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleTransferClick(user)}
                              className="shrink-0 text-muted-foreground hover:text-foreground"
                              title="Transfer user"
                            >
                              <ArrowRight className="h-4 w-4 sm:mr-2" />
                              <span className="hidden sm:inline">Transfer</span>
                            </Button>
                          )}
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </CardContent>
          </Card>

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
                Direct permissions assigned to this group. Members inherit these permissions in
                addition to permissions from base roles.
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

      {/* Transfer User Dialog */}
      <Dialog open={transferDialog.isOpen} onOpenChange={handleTransferCancel}>
        <DialogContent className="sm:max-w-[500px]">
          <div className="p-6">
            <DialogHeader className="pb-4 text-left sm:text-left">
              <div className="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                  <ArrowRightLeft className="h-5 w-5 text-primary" />
                </div>
                <div className="flex-1 text-center sm:text-left">
                  <DialogTitle className="text-lg sm:text-xl">
                    Transfer User to Another Group
                  </DialogTitle>
                  <DialogDescription className="mt-1.5 text-sm sm:text-base">
                    Move this user from the current group to a different group
                  </DialogDescription>
                </div>
              </div>
            </DialogHeader>

            {selectedUser && (
              <div className="space-y-6">
                {/* User Info Card */}
                <div className="rounded-lg border bg-muted/50 p-4">
                  <div className="flex items-center gap-3">
                    <Avatar className="h-12 w-12 shrink-0">
                      {selectedUser.avatar?.url ? (
                        <AvatarImage src={selectedUser.avatar.url} alt={selectedUser.name} />
                      ) : null}
                      <AvatarFallback>{getInitials(selectedUser.name)}</AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                      <div className="truncate font-medium">{selectedUser.name}</div>
                      <div className="truncate text-sm text-muted-foreground">
                        {selectedUser.email}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Transfer Flow */}
                <div className="space-y-4">
                  {/* From Group */}
                  <div className="space-y-2">
                    <Label className="text-sm font-medium text-muted-foreground">
                      Current Group
                    </Label>
                    <div className="flex items-center gap-2 rounded-md border bg-background px-3 py-2.5">
                      <Users className="h-4 w-4 shrink-0 text-muted-foreground" />
                      <span className="truncate font-medium">{group.name}</span>
                    </div>
                  </div>

                  {/* Arrow Icon */}
                  <div className="flex justify-center">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                      <ArrowRight className="h-4 w-4 text-primary" />
                    </div>
                  </div>

                  {/* To Group Select */}
                  <div className="space-y-2">
                    <Label htmlFor="target-group" className="text-sm font-medium">
                      Target Group
                    </Label>
                    <Select value={targetGroupId} onValueChange={setTargetGroupId}>
                      <SelectTrigger id="target-group" className="h-11">
                        <SelectValue placeholder="Select a group to transfer to..." />
                      </SelectTrigger>
                      <SelectContent>
                        {availableGroups.length === 0 ? (
                          <div className="px-2 py-6 text-center text-sm text-muted-foreground">
                            No other groups available
                          </div>
                        ) : (
                          availableGroups.map((g) => (
                            <SelectItem key={g.id} value={String(g.id)}>
                              <div className="flex items-center gap-2">
                                <UserPlus className="h-4 w-4 shrink-0 text-muted-foreground" />
                                <span className="truncate">{g.name}</span>
                              </div>
                            </SelectItem>
                          ))
                        )}
                      </SelectContent>
                    </Select>
                    {availableGroups.length === 0 && (
                      <p className="text-xs text-muted-foreground">
                        Create another group first to enable transfers
                      </p>
                    )}
                  </div>
                </div>

                {/* Info Message */}
                <div className="rounded-md border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
                  <div className="flex items-start gap-2">
                    <Shield className="mt-0.5 h-4 w-4 shrink-0" />
                    <div className="min-w-0 flex-1">
                      The user will be <strong>removed</strong> from <strong>{group.name}</strong>{' '}
                      and <strong>added</strong> to the selected group. Their permissions will be
                      updated accordingly.
                    </div>
                  </div>
                </div>
              </div>
            )}

            <DialogFooter className="gap-2 pt-4 sm:gap-0">
              <Button variant="outline" onClick={handleTransferCancel} disabled={isTransferring}>
                Cancel
              </Button>
              <Button
                onClick={handleTransferConfirm}
                disabled={!targetGroupId || isTransferring || availableGroups.length === 0}
              >
                {isTransferring ? (
                  <>
                    <span className="mr-2">Transferring...</span>
                  </>
                ) : (
                  <>
                    <ArrowRightLeft className="mr-2 h-4 w-4" />
                    Transfer User
                  </>
                )}
              </Button>
            </DialogFooter>
          </div>
        </DialogContent>
      </Dialog>
    </DashboardLayout>
  );
}
