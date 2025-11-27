/**
 * Roles listing page displaying all roles with their permissions
 */
import { useDisclosure } from '@/Hooks/useDisclosure';
import { Link, router } from '@inertiajs/react';
import { Pencil, Plus, Shield, Trash2 } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ roles = [] }) {
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

  return (
    <DashboardLayout header="Core">
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
        <div className="space-y-4">
          {roles.length === 0 ? (
            <Card>
              <CardContent className="py-10 text-center">
                <Shield className="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 className="mt-4 text-lg font-semibold">No roles found</h3>
                <p className="mt-2 text-sm text-muted-foreground">
                  Get started by creating a new role.
                </p>
                <Link href={route('core.roles.create')}>
                  <Button className="mt-4">
                    <Plus className="mr-2 h-4 w-4" />
                    Create Role
                  </Button>
                </Link>
              </CardContent>
            </Card>
          ) : (
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              {roles.map((role) => (
                <Card key={role.id}>
                  <CardHeader>
                    <div className="flex items-center justify-between">
                      <CardTitle className="text-lg">{role.name}</CardTitle>
                      <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                          <Link href={route('core.roles.edit', { role: role.id })}>
                            <Pencil className="h-4 w-4" />
                            <span className="sr-only">Edit role</span>
                          </Link>
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                          onClick={() => handleDeleteClick(role)}
                        >
                          <Trash2 className="h-4 w-4" />
                          <span className="sr-only">Delete role</span>
                        </Button>
                      </div>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-2">
                      <CardDescription>Permissions</CardDescription>
                      {role.permissions && role.permissions.length > 0 ? (
                        <div className="flex flex-wrap gap-2">
                          {role.permissions.map((permission) => (
                            <Badge key={permission.id} variant="outline">
                              {permission.name}
                            </Badge>
                          ))}
                        </div>
                      ) : (
                        <p className="text-sm text-muted-foreground">No permissions assigned</p>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </div>
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
