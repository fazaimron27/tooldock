/**
 * Group detail page displaying full group information
 * Shows group details, list of users, and permissions attached to the group
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { formatDate, getInitials } from '@/Utils/format';
import { Link, router } from '@inertiajs/react';
import {
  ArrowLeft,
  ArrowRight,
  ArrowRightLeft,
  Pencil,
  Plus,
  Search,
  Shield,
  Trash2,
  UserMinus,
  UserPlus,
  Users,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { useDebounce } from 'use-debounce';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
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

import MemberDialog from '../../Components/MemberDialog';
import { isSuperAdmin } from '../../Utils/userUtils';

export default function Show({
  group,
  groupedPermissions = {},
  availableGroups = [],
  members = null,
  defaultPerPage = 10,
  availableUsers = null,
}) {
  const deleteDialog = useDisclosure();
  const bulkTransferDialog = useDisclosure();
  const addMembersDialog = useDisclosure();
  const removeMembersDialog = useDisclosure();
  const [bulkTargetGroupId, setBulkTargetGroupId] = useState('');
  const [isBulkTransferring, setIsBulkTransferring] = useState(false);
  const [selectedUserIds, setSelectedUserIds] = useState([]);
  const [addMembersSearch, setAddMembersSearch] = useState('');
  const [usersToAdd, setUsersToAdd] = useState([]);
  const [isAddingMembers, setIsAddingMembers] = useState(false);
  const [isRemovingMembers, setIsRemovingMembers] = useState(false);
  const [isLoadingAvailableUsers, setIsLoadingAvailableUsers] = useState(false);

  const [debouncedSearch] = useDebounce(addMembersSearch, 300);

  const permissionCount = useMemo(() => {
    return Object.values(groupedPermissions).reduce(
      (total, resources) =>
        total + Object.values(resources).reduce((sum, perms) => sum + perms.length, 0),
      0
    );
  }, [groupedPermissions]);

  /**
   * Fetch available users from server when dialog opens or search changes.
   * Uses debounced search to reduce server requests.
   * Uses replace: true to prevent adding to browser history.
   * Small delay ensures this request happens after dialog is fully open.
   */
  useEffect(() => {
    if (!addMembersDialog.isOpen || !group?.id) {
      return;
    }

    /**
     * Delay request to ensure dialog is fully rendered before fetching data.
     * Prevents race conditions and ensures smooth UI transitions.
     */
    const timeoutId = window.setTimeout(() => {
      setIsLoadingAvailableUsers(true);
      router.get(
        route('groups.available-users', { group: group.id }),
        {
          search: debouncedSearch || undefined,
          page: 1,
          per_page: 20,
        },
        {
          only: ['availableUsers', 'defaultPerPage'],
          preserveState: true,
          preserveScroll: true,
          replace: true,
          skipLoadingIndicator: true,
          onFinish: () => {
            setIsLoadingAvailableUsers(false);
          },
        }
      );
    }, 50);

    return () => window.clearTimeout(timeoutId);
  }, [addMembersDialog.isOpen, debouncedSearch, group?.id]);

  /**
   * Get available users from server response or empty array.
   * Server-side search is already handled, so we use the data directly.
   */
  const filteredAvailableUsers = useMemo(() => {
    return availableUsers?.data || [];
  }, [availableUsers?.data]);

  /**
   * Table column definitions for the members data table.
   * Includes selection checkbox, user name with avatar and Super Admin badge, and email.
   */
  const memberColumns = useMemo(
    () => [
      {
        id: 'select',
        header: ({ table }) => (
          <Checkbox
            checked={table.getIsAllPageRowsSelected()}
            onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
            aria-label="Select all"
          />
        ),
        cell: ({ row }) => (
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => row.toggleSelected(!!value)}
            aria-label="Select row"
          />
        ),
        enableSorting: false,
        enableHiding: false,
      },
      {
        accessorKey: 'name',
        header: 'Name',
        cell: (info) => {
          const user = info.row.original;
          const userIsSuperAdmin = isSuperAdmin(user);
          return (
            <div className="flex items-center gap-2">
              <Avatar className="h-8 w-8">
                {user.avatar?.url ? <AvatarImage src={user.avatar.url} alt={user.name} /> : null}
                <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
              </Avatar>
              <div className="flex items-center gap-2">
                <Link
                  href={route('core.users.edit', { user: user.id })}
                  className="font-medium hover:underline"
                >
                  {user.name}
                </Link>
                {userIsSuperAdmin && (
                  <Badge variant="default" className="text-xs">
                    <Shield className="mr-1 h-3 w-3" />
                    Super Admin
                  </Badge>
                )}
              </div>
            </div>
          );
        },
      },
      {
        accessorKey: 'email',
        header: 'Email',
        cell: (info) => {
          const user = info.row.original;
          return <span className="text-muted-foreground">{user.email}</span>;
        },
      },
    ],
    []
  );

  const pageCount = useMemo(() => {
    if (members?.total !== undefined && members?.per_page) {
      return Math.ceil(members.total / members.per_page);
    }
    return undefined;
  }, [members?.total, members?.per_page]);

  const membersData = useMemo(() => {
    if (members?.data) {
      return members.data;
    }
    return group?.users || [];
  }, [members?.data, group?.users]);

  const { tableProps: memberTableProps } = useDatatable({
    data: membersData,
    columns: memberColumns,
    route: group?.id ? route('groups.members', { group: group.id }) : null,
    serverSide: true,
    pageSize: members?.per_page || defaultPerPage,
    initialSorting: [{ id: 'name', desc: false }],
    pageCount: pageCount,
    only: ['members', 'defaultPerPage'],
  });

  useEffect(() => {
    if (!memberTableProps.table || !members?.current_page) {
      return;
    }

    const currentPageIndex = members.current_page - 1;
    const currentPagination = memberTableProps.table.getState().pagination;
    const serverPageSize = members.per_page || defaultPerPage;

    if (
      currentPagination.pageIndex !== currentPageIndex ||
      currentPagination.pageSize !== serverPageSize
    ) {
      window.requestAnimationFrame(() => {
        memberTableProps.table.setPagination({
          pageIndex: currentPageIndex,
          pageSize: serverPageSize,
        });
      });
    }
  }, [memberTableProps.table, members?.current_page, members?.per_page, defaultPerPage]);

  /**
   * Get currently selected rows from the members table.
   */
  const table = memberTableProps.table;
  const selectedRows = useMemo(() => {
    return table?.getSelectedRowModel().rows || [];
    /**
     * Disable exhaustive-deps warning: rowSelection is a nested object that changes reference
     * on every state update. Including it would cause unnecessary recalculations.
     * The table dependency is sufficient to track selection changes.
     */
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [table, table?.getState().rowSelection]);

  /**
   * Extract user IDs from selected table rows.
   */
  const selectedMemberIds = useMemo(() => {
    return selectedRows.map((row) => row.original.id);
  }, [selectedRows]);

  /**
   * Open the add members dialog and reset selection state.
   */
  const handleAddMembersClick = () => {
    setUsersToAdd([]);
    setAddMembersSearch('');
    addMembersDialog.onOpen();
  };

  const handleToggleUserToAdd = (userId) => {
    setUsersToAdd((prev) =>
      prev.includes(userId) ? prev.filter((id) => id !== userId) : [...prev, userId]
    );
  };

  /**
   * Submit the add members request to the server.
   * Displays error toast if the request fails.
   */
  const handleAddMembersConfirm = () => {
    if (usersToAdd.length === 0) {
      return;
    }

    setIsAddingMembers(true);
    router.post(
      route('groups.add-members', { group: group.id }),
      { user_ids: usersToAdd },
      {
        onSuccess: () => {
          addMembersDialog.onClose();
          setUsersToAdd([]);
          setAddMembersSearch('');
          setIsAddingMembers(false);
          memberTableProps.table?.resetRowSelection();
        },
        onError: (errors) => {
          setIsAddingMembers(false);
          const errorMessage =
            errors?.message || errors?.user_ids?.[0] || 'Failed to add members. Please try again.';
          toast.error(errorMessage);
        },
      }
    );
  };

  /**
   * Open the bulk transfer dialog for selected members.
   */
  const handleBulkTransferClick = () => {
    if (selectedMemberIds.length === 0) {
      return;
    }
    setBulkTargetGroupId('');
    bulkTransferDialog.onOpen();
  };

  /**
   * Submit the bulk transfer request to move selected members to another group.
   * Displays error toast if the request fails.
   */
  const handleBulkTransferConfirm = () => {
    if (selectedMemberIds.length === 0 || !bulkTargetGroupId) {
      return;
    }

    setIsBulkTransferring(true);
    router.post(
      route('groups.transfer-members', { group: group.id }),
      {
        user_ids: selectedMemberIds,
        target_group_id: bulkTargetGroupId,
      },
      {
        onSuccess: () => {
          bulkTransferDialog.onClose();
          setBulkTargetGroupId('');
          setIsBulkTransferring(false);
          memberTableProps.table?.resetRowSelection();
        },
        onError: (errors) => {
          setIsBulkTransferring(false);
          const errorMessage =
            errors?.message ||
            errors?.user_ids?.[0] ||
            errors?.target_group_id?.[0] ||
            'Failed to transfer members. Please try again.';
          toast.error(errorMessage);
        },
      }
    );
  };

  /**
   * Cancel the bulk transfer operation and reset state.
   */
  const handleBulkTransferCancel = () => {
    bulkTransferDialog.onClose();
    setBulkTargetGroupId('');
  };

  /**
   * Open the remove members confirmation dialog for selected members.
   */
  const handleRemoveMembersClick = () => {
    if (selectedMemberIds.length === 0) {
      return;
    }
    setSelectedUserIds(selectedMemberIds);
    removeMembersDialog.onOpen();
  };

  /**
   * Submit the remove members request to the server.
   * Displays error toast if the request fails.
   */
  const handleRemoveMembersConfirm = () => {
    if (selectedUserIds.length === 0) {
      return;
    }

    setIsRemovingMembers(true);
    router.post(
      route('groups.remove-members', { group: group.id }),
      { user_ids: selectedUserIds },
      {
        onSuccess: () => {
          removeMembersDialog.onClose();
          setSelectedUserIds([]);
          setIsRemovingMembers(false);
          memberTableProps.table?.resetRowSelection();
        },
        onError: (errors) => {
          setIsRemovingMembers(false);
          const errorMessage =
            errors?.message ||
            errors?.user_ids?.[0] ||
            'Failed to remove members. Please try again.';
          toast.error(errorMessage);
        },
      }
    );
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
                <UserPlus className="h-5 w-5 text-primary" />
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
                  {members?.total ?? group.users?.length ?? 0}{' '}
                  {(members?.total ?? group.users?.length ?? 0) === 1 ? 'member' : 'members'}
                </Badge>
              </div>
              <CardDescription>
                Users assigned to this group. Super Admin users have full system access regardless
                of group membership.
              </CardDescription>
            </CardHeader>
            <CardContent>
              {!members?.data?.length && !group.users?.length ? (
                <div className="space-y-4">
                  <div className="py-8 text-center text-muted-foreground">
                    <Users className="mx-auto h-12 w-12 opacity-50" />
                    <p className="mt-2">No members assigned to this group</p>
                  </div>
                  <div className="flex justify-center">
                    <Button onClick={handleAddMembersClick}>
                      <Plus className="mr-2 h-4 w-4" />
                      Add Members
                    </Button>
                  </div>
                </div>
              ) : (
                <div className="space-y-4">
                  {/* Bulk Actions Toolbar */}
                  <div className="flex flex-wrap items-center gap-2">
                    <Button onClick={handleAddMembersClick} size="sm">
                      <Plus className="mr-2 h-4 w-4" />
                      Add Members
                    </Button>
                    {selectedMemberIds.length > 0 && (
                      <>
                        {availableGroups.length > 0 && (
                          <Button
                            onClick={handleBulkTransferClick}
                            variant="outline"
                            size="sm"
                            disabled={isBulkTransferring}
                          >
                            <ArrowRightLeft className="mr-2 h-4 w-4" />
                            Transfer Selected ({selectedMemberIds.length})
                          </Button>
                        )}
                        <Button
                          onClick={handleRemoveMembersClick}
                          variant="destructive"
                          size="sm"
                          disabled={isRemovingMembers}
                        >
                          <UserMinus className="mr-2 h-4 w-4" />
                          Remove Selected ({selectedMemberIds.length})
                        </Button>
                        <Button
                          onClick={() => memberTableProps.table?.resetRowSelection()}
                          variant="ghost"
                          size="sm"
                        >
                          Clear Selection
                        </Button>
                      </>
                    )}
                  </div>

                  <DataTable
                    {...memberTableProps}
                    searchable={true}
                    pagination={true}
                    sorting={true}
                    showCard={false}
                  />
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

      {/* Add Members Dialog */}
      <MemberDialog
        open={addMembersDialog.isOpen}
        onOpenChange={addMembersDialog.onClose}
        title="Add Members to Group"
        description={
          <>
            Select users to add to <strong>{group.name}</strong>
          </>
        }
        footer={
          <>
            <Button variant="outline" onClick={addMembersDialog.onClose} disabled={isAddingMembers}>
              Cancel
            </Button>
            <Button
              onClick={handleAddMembersConfirm}
              disabled={usersToAdd.length === 0 || isAddingMembers}
            >
              {isAddingMembers ? (
                <>Adding...</>
              ) : (
                <>
                  <Plus className="mr-2 h-4 w-4" />
                  Add {usersToAdd.length > 0 ? `${usersToAdd.length} ` : ''}Member
                  {usersToAdd.length !== 1 ? 's' : ''}
                </>
              )}
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          {/* Search Input */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              type="text"
              placeholder="Search users by name or email..."
              value={addMembersSearch}
              onChange={(e) => setAddMembersSearch(e.target.value)}
              className="pl-9"
            />
          </div>

          {/* Users List */}
          {isLoadingAvailableUsers ? (
            <div className="py-8 text-center text-muted-foreground">
              <Users className="mx-auto h-12 w-12 opacity-50 animate-pulse" />
              <p className="mt-2">Loading users...</p>
            </div>
          ) : filteredAvailableUsers.length === 0 ? (
            <div className="py-8 text-center text-muted-foreground">
              <Users className="mx-auto h-12 w-12 opacity-50" />
              <p className="mt-2">
                {debouncedSearch || addMembersSearch.trim()
                  ? 'No users found matching your search'
                  : availableUsers?.total === 0
                    ? 'All users are already members of this group'
                    : 'No users available'}
              </p>
            </div>
          ) : (
            <div className="max-h-[400px] space-y-2 overflow-y-auto rounded-md border p-2">
              {filteredAvailableUsers.map((user) => {
                const userIsSuperAdmin = isSuperAdmin(user);
                return (
                  <div
                    key={user.id}
                    className="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent"
                  >
                    <Checkbox
                      checked={usersToAdd.includes(user.id)}
                      onCheckedChange={() => handleToggleUserToAdd(user.id)}
                    />
                    <Avatar className="h-10 w-10 shrink-0">
                      {user.avatar?.url ? (
                        <AvatarImage src={user.avatar.url} alt={user.name} />
                      ) : null}
                      <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <div className="truncate font-medium">{user.name}</div>
                        {userIsSuperAdmin && (
                          <Badge variant="default" className="text-xs shrink-0">
                            <Shield className="mr-1 h-3 w-3" />
                            Super Admin
                          </Badge>
                        )}
                      </div>
                      <div className="truncate text-sm text-muted-foreground">{user.email}</div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {usersToAdd.length > 0 && (
            <div className="rounded-md bg-muted p-3 text-sm">
              <strong>{usersToAdd.length}</strong> user{usersToAdd.length === 1 ? '' : 's'} selected
            </div>
          )}
        </div>
      </MemberDialog>

      {/* Bulk Transfer Members Dialog */}
      <MemberDialog
        open={bulkTransferDialog.isOpen}
        onOpenChange={handleBulkTransferCancel}
        title="Transfer Members to Another Group"
        description={
          <>
            Transfer {selectedMemberIds.length} selected member
            {selectedMemberIds.length === 1 ? '' : 's'} from <strong>{group.name}</strong> to
            another group
          </>
        }
        maxWidth="500px"
        footer={
          <>
            <Button
              variant="outline"
              onClick={handleBulkTransferCancel}
              disabled={isBulkTransferring}
            >
              Cancel
            </Button>
            <Button
              onClick={handleBulkTransferConfirm}
              disabled={!bulkTargetGroupId || isBulkTransferring || availableGroups.length === 0}
            >
              {isBulkTransferring ? (
                <>Transferring...</>
              ) : (
                <>
                  <ArrowRightLeft className="mr-2 h-4 w-4" />
                  Transfer {selectedMemberIds.length} Member
                  {selectedMemberIds.length === 1 ? '' : 's'}
                </>
              )}
            </Button>
          </>
        }
      >
        <div className="space-y-6">
          {/* Selected Users Preview */}
          <div className="space-y-2">
            <Label className="text-sm font-medium">
              Selected Members ({selectedMemberIds.length})
            </Label>
            <div className="max-h-[200px] space-y-2 overflow-y-auto rounded-md border p-2">
              {selectedRows.slice(0, 10).map((row) => {
                const user = row.original;
                const userIsSuperAdmin = isSuperAdmin(user);
                return (
                  <div key={user.id} className="flex items-center gap-2 rounded-lg border p-2">
                    <Avatar className="h-8 w-8 shrink-0">
                      {user.avatar?.url ? (
                        <AvatarImage src={user.avatar.url} alt={user.name} />
                      ) : null}
                      <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <div className="truncate text-sm font-medium">{user.name}</div>
                        {userIsSuperAdmin && (
                          <Badge variant="default" className="text-xs shrink-0">
                            <Shield className="mr-1 h-3 w-3" />
                            Super Admin
                          </Badge>
                        )}
                      </div>
                      <div className="truncate text-xs text-muted-foreground">{user.email}</div>
                    </div>
                  </div>
                );
              })}
              {selectedRows.length > 10 && (
                <div className="text-center text-sm text-muted-foreground">
                  and {selectedRows.length - 10} more...
                </div>
              )}
            </div>
          </div>

          {/* Transfer Flow */}
          <div className="space-y-4">
            {/* From Group */}
            <div className="space-y-2">
              <Label className="text-sm font-medium text-muted-foreground">Current Group</Label>
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
              <Label htmlFor="bulk-target-group" className="text-sm font-medium">
                Target Group
              </Label>
              <Select value={bulkTargetGroupId} onValueChange={setBulkTargetGroupId}>
                <SelectTrigger id="bulk-target-group" className="h-11">
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
                The selected member{selectedMemberIds.length === 1 ? '' : 's'} will be{' '}
                <strong>removed</strong> from <strong>{group.name}</strong> and{' '}
                <strong>added</strong> to the selected group. Their permissions will be updated
                accordingly.
              </div>
            </div>
          </div>
        </div>
      </MemberDialog>

      {/* Remove Members Confirmation Dialog */}
      <ConfirmDialog
        isOpen={removeMembersDialog.isOpen}
        onConfirm={handleRemoveMembersConfirm}
        onCancel={() => {
          removeMembersDialog.onClose();
          setSelectedUserIds([]);
        }}
        title="Remove Members from Group"
        message={
          selectedUserIds.length > 0
            ? `Are you sure you want to remove ${selectedUserIds.length} member${selectedUserIds.length === 1 ? '' : 's'} from "${group.name}"? This action cannot be undone.`
            : 'Are you sure you want to remove these members?'
        }
        confirmLabel={isRemovingMembers ? 'Removing...' : 'Remove Members'}
        cancelLabel="Cancel"
        variant="destructive"
      />
    </DashboardLayout>
  );
}
