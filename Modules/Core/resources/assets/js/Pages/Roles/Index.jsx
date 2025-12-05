/**
 * Roles listing page with server-side pagination, sorting, and search
 * Displays roles with their permissions count
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { Link, router } from '@inertiajs/react';
import { Pencil, Plus, Shield, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

import { ROLES } from '../../constants';

export default function Index({ roles, defaultPerPage = 20 }) {
  const deleteDialog = useDisclosure();
  const [roleToDelete, setRoleToDelete] = useState(null);

  const handleDeleteClick = useCallback(
    (role) => {
      setRoleToDelete(role);
      deleteDialog.onOpen();
    },
    [deleteDialog]
  );

  const handleDeleteConfirm = () => {
    if (roleToDelete) {
      router.delete(route('core.roles.destroy', { role: roleToDelete.id }), {
        onSuccess: () => {
          deleteDialog.onClose();
          setRoleToDelete(null);
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
          const role = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <Shield className="h-4 w-4 text-muted-foreground" />
              <span className="font-medium">{role.name}</span>
            </div>
          );
        },
      },
      {
        id: 'permissions',
        header: 'Permissions',
        cell: (info) => {
          const role = info.row.original;

          if (role.name === ROLES.SUPER_ADMIN) {
            return (
              <Badge
                variant="default"
                className="bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground transition-colors"
              >
                All permissions (bypass)
              </Badge>
            );
          }

          const permissionCount = role.permissions?.length || 0;
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
        id: 'inherit',
        header: 'Inherit',
        cell: (info) => {
          const role = info.row.original;
          const groups = role.groups || [];
          if (groups.length === 0) {
            return <span className="text-muted-foreground text-sm">—</span>;
          }
          return (
            <div className="flex flex-wrap gap-2">
              {groups.map((group) => (
                <Badge key={group.id} variant="outline">
                  {group.name}
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
          const role = info.row.original;
          return <span>{new Date(role.created_at).toLocaleDateString()}</span>;
        },
      },
      {
        id: 'actions',
        header: 'Actions',
        cell: (info) => {
          const role = info.row.original;
          const isSuperAdmin = role.name === ROLES.SUPER_ADMIN;

          return (
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <Link href={route('core.roles.edit', { role: role.id })}>
                  <Pencil className="h-4 w-4" />
                  <span className="sr-only">Edit role</span>
                </Link>
              </Button>
              {!isSuperAdmin && (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                  onClick={() => handleDeleteClick(role)}
                >
                  <Trash2 className="h-4 w-4" />
                  <span className="sr-only">Delete role</span>
                </Button>
              )}
            </div>
          );
        },
      },
    ],
    [handleDeleteClick]
  );

  const pageCount = useMemo(() => {
    if (roles.total !== undefined && roles.per_page) {
      return Math.ceil(roles.total / roles.per_page);
    }
    return undefined;
  }, [roles.total, roles.per_page]);

  const { tableProps } = useDatatable({
    data: roles.data || [],
    columns,
    route: route('core.roles.index'),
    serverSide: true,
    pageSize: roles.per_page || defaultPerPage,
    initialSorting: [{ id: 'created_at', desc: true }],
    pageCount: pageCount,
    only: ['roles'],
  });

  useEffect(() => {
    if (!tableProps.table || roles.current_page === undefined) {
      return;
    }

    const currentPageIndex = roles.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = roles.per_page || defaultPerPage;

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
  }, [tableProps.table, roles.current_page, roles.per_page, defaultPerPage]);

  return (
    <DashboardLayout header="Roles">
      <PageShell
        title="Roles"
        actions={
          <Link href={route('core.roles.create')}>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New Role
            </Button>
          </Link>
        }
      >
        <DataTable
          {...tableProps}
          title="Roles"
          description="A list of all roles in the system"
          showCard={true}
        />
      </PageShell>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          deleteDialog.onClose();
          setRoleToDelete(null);
        }}
        title="Delete Role"
        message={
          roleToDelete
            ? `Are you sure you want to delete the role "${roleToDelete.name}"? This action cannot be undone.`
            : 'Are you sure you want to delete this role?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />
    </DashboardLayout>
  );
}
