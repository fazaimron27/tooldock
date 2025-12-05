/**
 * Groups listing page with server-side pagination, sorting, and search
 * Displays groups with their member count and permission count
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2, Users } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ groups, defaultPerPage = 20 }) {
  const deleteDialog = useDisclosure();
  const [groupToDelete, setGroupToDelete] = useState(null);

  const handleDeleteClick = useCallback(
    (group) => {
      setGroupToDelete(group);
      deleteDialog.onOpen();
    },
    [deleteDialog]
  );

  const handleDeleteConfirm = () => {
    if (groupToDelete) {
      router.delete(route('groups.groups.destroy', { group: groupToDelete.id }), {
        onSuccess: () => {
          deleteDialog.onClose();
          setGroupToDelete(null);
        },
      });
    }
  };

  const columns = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: 'Name',
        cell: (info) => {
          const group = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <Users className="h-4 w-4 text-muted-foreground" />
              <Link
                href={route('groups.groups.show', { group: group.id })}
                className="font-medium hover:underline"
              >
                {group.name}
              </Link>
            </div>
          );
        },
      },
      {
        accessorKey: 'slug',
        header: 'Slug',
        cell: (info) => {
          const group = info.row.original;
          return <span className="text-muted-foreground text-sm font-mono">{group.slug}</span>;
        },
      },
      {
        id: 'members',
        header: 'Members',
        cell: (info) => {
          const group = info.row.original;
          const memberCount = group.users_count || 0;
          if (memberCount === 0) {
            return <span className="text-muted-foreground text-sm">No members</span>;
          }
          return (
            <Badge variant="secondary">
              {memberCount} {memberCount === 1 ? 'member' : 'members'}
            </Badge>
          );
        },
      },
      {
        id: 'permissions',
        header: 'Permissions',
        cell: (info) => {
          const group = info.row.original;
          const permissionCount = group.permissions_count || 0;
          if (permissionCount === 0) {
            return <span className="text-muted-foreground text-sm">No permissions</span>;
          }
          return (
            <Badge variant="secondary">
              {permissionCount} {permissionCount === 1 ? 'permission' : 'permissions'}
            </Badge>
          );
        },
      },
      {
        accessorKey: 'created_at',
        header: 'Created',
        cell: (info) => {
          const group = info.row.original;
          return <span>{new Date(group.created_at).toLocaleDateString()}</span>;
        },
      },
      {
        id: 'actions',
        header: 'Actions',
        cell: (info) => {
          const group = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <Link href={route('groups.groups.show', { group: group.id })}>
                  <Eye className="h-4 w-4" />
                  <span className="sr-only">View group</span>
                </Link>
              </Button>
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <Link href={route('groups.groups.edit', { group: group.id })}>
                  <Pencil className="h-4 w-4" />
                  <span className="sr-only">Edit group</span>
                </Link>
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                onClick={() => handleDeleteClick(group)}
              >
                <Trash2 className="h-4 w-4" />
                <span className="sr-only">Delete group</span>
              </Button>
            </div>
          );
        },
      },
    ],
    [handleDeleteClick]
  );

  const pageCount = useMemo(() => {
    if (groups.total !== undefined && groups.per_page) {
      return Math.ceil(groups.total / groups.per_page);
    }
    return undefined;
  }, [groups.total, groups.per_page]);

  const { tableProps } = useDatatable({
    data: groups.data || [],
    columns,
    route: route('groups.groups.index'),
    serverSide: true,
    pageSize: groups.per_page || defaultPerPage,
    initialSorting: [{ id: 'created_at', desc: true }],
    pageCount: pageCount,
    only: ['groups'],
  });

  useEffect(() => {
    if (!tableProps.table || groups.current_page === undefined) {
      return;
    }

    const currentPageIndex = groups.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = groups.per_page || defaultPerPage;

    if (
      currentPagination.pageIndex !== currentPageIndex ||
      currentPagination.pageSize !== serverPageSize
    ) {
      window.requestAnimationFrame(() => {
        tableProps.table.setPagination({
          pageIndex: currentPageIndex,
          pageSize: serverPageSize,
        });
      });
    }
  }, [tableProps.table, groups.current_page, groups.per_page, defaultPerPage]);

  return (
    <DashboardLayout header="Groups">
      <PageShell
        title="Groups"
        actions={
          <Link href={route('groups.groups.create')}>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New Group
            </Button>
          </Link>
        }
      >
        <DataTable
          {...tableProps}
          title="Groups"
          description="A list of all groups in the system"
          showCard={true}
        />
      </PageShell>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          deleteDialog.onClose();
          setGroupToDelete(null);
        }}
        title="Delete Group"
        message={
          groupToDelete
            ? `Are you sure you want to delete the group "${groupToDelete.name}"? This action cannot be undone.`
            : 'Are you sure you want to delete this group?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />
    </DashboardLayout>
  );
}
