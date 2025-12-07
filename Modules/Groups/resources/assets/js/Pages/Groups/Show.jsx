/**
 * Group detail page displaying full group information
 * Shows group details, list of users, and permissions attached to the group
 */
import { useDisclosure } from '@/Hooks/useDisclosure';
import { getInitials } from '@/Utils/format';
import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Shield, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';

import DashboardLayout from '@/Layouts/DashboardLayout';

import { isSuperAdmin } from '../../Utils/userUtils';
import AddMembersDialog from './Components/AddMembersDialog';
import GroupDetailsCard from './Components/GroupDetailsCard';
import MembersCard from './Components/MembersCard';
import PermissionsCard from './Components/PermissionsCard';
import TransferMembersDialog from './Components/TransferMembersDialog';
import { useAvailableUsers } from './Hooks/useAvailableUsers';
import { useGroupMembers } from './Hooks/useGroupMembers';

export default function Show({
  group,
  groupedPermissions = {},
  availableGroups = [],
  members = null,
  defaultPerPage = 10,
  availableUsers: initialAvailableUsers = null,
}) {
  const deleteDialog = useDisclosure();
  const bulkTransferDialog = useDisclosure();
  const addMembersDialog = useDisclosure();
  const removeMembersDialog = useDisclosure();
  const [selectedUserIds, setSelectedUserIds] = useState([]);
  const [isRemovingMembers, setIsRemovingMembers] = useState(false);

  const permissionCount = useMemo(() => {
    return Object.values(groupedPermissions).reduce(
      (total, resources) =>
        total + Object.values(resources).reduce((sum, perms) => sum + perms.length, 0),
      0
    );
  }, [groupedPermissions]);

  const {
    availableUsers,
    isLoadingAvailableUsers,
    search: addMembersSearch,
    setSearch: setAddMembersSearch,
  } = useAvailableUsers({
    group,
    addMembersDialog,
    availableUsers: initialAvailableUsers,
  });

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

  const { memberTableProps, selectedRows, selectedMemberIds } = useGroupMembers({
    group,
    members,
    defaultPerPage,
    columns: memberColumns,
  });

  const handleAddMembersClick = () => {
    addMembersDialog.onOpen();
  };

  const handleMemberOperationSuccess = () => {
    memberTableProps.table?.resetRowSelection();
  };

  const handleBulkTransferClick = () => {
    if (selectedMemberIds.length === 0) {
      return;
    }
    bulkTransferDialog.onOpen();
  };

  const handleBulkTransferCancel = () => {
    bulkTransferDialog.onClose();
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
          handleMemberOperationSuccess();
        },
        onError: (errors) => {
          setIsRemovingMembers(false);
          const errorMessage =
            errors?.message ||
            errors?.user_ids?.[0] ||
            'Failed to remove members. Please try again.';
          toast.error(errorMessage);
        },
        preserveScroll: true,
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
          <GroupDetailsCard group={group} />

          <MembersCard
            group={group}
            members={members}
            memberTableProps={memberTableProps}
            selectedMemberIds={selectedMemberIds}
            availableGroups={availableGroups}
            isBulkTransferring={false}
            isRemovingMembers={isRemovingMembers}
            onAddMembersClick={handleAddMembersClick}
            onBulkTransferClick={handleBulkTransferClick}
            onRemoveMembersClick={handleRemoveMembersClick}
            onClearSelection={() => memberTableProps.table?.resetRowSelection()}
          />

          <PermissionsCard
            group={group}
            groupedPermissions={groupedPermissions}
            permissionCount={permissionCount}
          />
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

      <AddMembersDialog
        open={addMembersDialog.isOpen}
        onOpenChange={addMembersDialog.onClose}
        group={group}
        availableUsers={availableUsers}
        isLoadingAvailableUsers={isLoadingAvailableUsers}
        search={addMembersSearch}
        setSearch={setAddMembersSearch}
        onSuccess={handleMemberOperationSuccess}
      />

      <TransferMembersDialog
        open={bulkTransferDialog.isOpen}
        onOpenChange={handleBulkTransferCancel}
        group={group}
        availableGroups={availableGroups}
        selectedRows={selectedRows}
        selectedMemberIds={selectedMemberIds}
        onSuccess={handleMemberOperationSuccess}
      />

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
