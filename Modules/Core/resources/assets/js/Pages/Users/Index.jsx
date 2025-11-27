/**
 * Users listing page with server-side pagination, sorting, and search
 * Displays users with their assigned roles as badges
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ users, defaultPerPage = 20 }) {
  const deleteDialog = useDisclosure();
  const [userToDelete, setUserToDelete] = useState(null);

  const handleDeleteClick = useCallback(
    (user) => {
      setUserToDelete(user);
      deleteDialog.onOpen();
    },
    [deleteDialog]
  );

  const handleDeleteConfirm = () => {
    if (userToDelete) {
      router.delete(route('core.users.destroy', { user: userToDelete.id }), {
        onSuccess: () => {
          deleteDialog.onClose();
          setUserToDelete(null);
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
          const user = info.row.original;
          const initials = user.name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
          return (
            <div className="flex items-center gap-2">
              <Avatar className="h-8 w-8">
                <AvatarFallback className="text-xs">{initials}</AvatarFallback>
              </Avatar>
              <span className="font-medium">{user.name}</span>
            </div>
          );
        },
      },
      {
        accessorKey: 'email',
        header: 'Email',
      },
      {
        id: 'roles',
        header: 'Roles',
        cell: (info) => {
          const user = info.row.original;
          const roles = user.roles || [];
          if (roles.length === 0) {
            return <span className="text-muted-foreground text-sm">No roles</span>;
          }
          return (
            <div className="flex flex-wrap gap-2">
              {roles.map((role) => (
                <Badge key={role.id} variant="secondary">
                  {role.name}
                </Badge>
              ))}
            </div>
          );
        },
      },
      {
        accessorKey: 'created_at',
        header: 'Created',
        cell: (info) => {
          const user = info.row.original;
          return <span>{new Date(user.created_at).toLocaleDateString()}</span>;
        },
      },
      {
        id: 'actions',
        header: 'Actions',
        cell: (info) => {
          const user = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <Link href={route('core.users.edit', { user: user.id })}>
                  <Pencil className="h-4 w-4" />
                  <span className="sr-only">Edit user</span>
                </Link>
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                onClick={() => handleDeleteClick(user)}
              >
                <Trash2 className="h-4 w-4" />
                <span className="sr-only">Delete user</span>
              </Button>
            </div>
          );
        },
      },
    ],
    [handleDeleteClick]
  );

  const pageCount = useMemo(() => {
    if (users.total !== undefined && users.per_page) {
      return Math.ceil(users.total / users.per_page);
    }
    return undefined;
  }, [users.total, users.per_page]);

  const { tableProps } = useDatatable({
    data: users.data || [],
    columns,
    route: route('core.users.index'),
    serverSide: true,
    pageSize: users.per_page || defaultPerPage,
    initialSorting: [{ id: 'created_at', desc: true }],
    pageCount: pageCount,
    only: ['users'],
  });

  useEffect(() => {
    if (!tableProps.table || users.current_page === undefined) {
      return;
    }

    const currentPageIndex = users.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = users.per_page || defaultPerPage;

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
  }, [tableProps.table, users.current_page, users.per_page, defaultPerPage]);

  return (
    <DashboardLayout header="Core">
      <PageShell
        title="Users"
        actions={
          <Link href={route('core.users.create')}>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New User
            </Button>
          </Link>
        }
      >
        <DataTable
          {...tableProps}
          title="Users"
          description="A list of all users in the system"
          showCard={true}
        />
      </PageShell>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          deleteDialog.onClose();
          setUserToDelete(null);
        }}
        title="Delete User"
        message={
          userToDelete
            ? `Are you sure you want to delete "${userToDelete.name}"? This action cannot be undone.`
            : 'Are you sure you want to delete this user?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />
    </DashboardLayout>
  );
}
