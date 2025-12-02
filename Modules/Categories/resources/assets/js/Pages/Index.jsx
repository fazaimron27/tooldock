/**
 * Categories listing page with server-side pagination, sorting, search, and type filtering
 * Displays category statistics and manages category deletion
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { Link, router } from '@inertiajs/react';
import { Pencil, Plus, Tag, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import StatGrid from '@/Components/DataDisplay/StatGrid';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ categories, defaultPerPage = 20, types = [] }) {
  const deleteDialog = useDisclosure();
  const [categoryToDelete, setCategoryToDelete] = useState(null);
  const [typeFilter, setTypeFilter] = useState('');

  const handleDeleteClick = useCallback(
    (category) => {
      setCategoryToDelete(category);
      deleteDialog.onOpen();
    },
    [deleteDialog]
  );

  const handleDeleteConfirm = () => {
    if (categoryToDelete) {
      router.delete(route('categories.destroy', { category: categoryToDelete.id }), {
        onSuccess: () => {
          deleteDialog.onClose();
          setCategoryToDelete(null);
        },
      });
    }
  };

  const handleTypeFilterChange = (value) => {
    setTypeFilter(value);
    router.get(
      route('categories.index'),
      { type: value || null },
      {
        preserveState: true,
        preserveScroll: true,
        only: ['categories'],
        skipLoadingIndicator: true,
      }
    );
  };

  const stats = useMemo(() => {
    const total = categories.total ?? 0;
    const byType =
      categories.data?.reduce((acc, cat) => {
        acc[cat.type] = (acc[cat.type] || 0) + 1;
        return acc;
      }, {}) ?? {};

    const typeStats = Object.entries(byType)
      .map(([type, count]) => ({
        id: type,
        title: type.charAt(0).toUpperCase() + type.slice(1),
        value: count.toString(),
        icon: Tag,
      }))
      .sort((a, b) => a.title.localeCompare(b.title));

    return [
      {
        id: 'total',
        title: 'Total Categories',
        value: total.toString(),
        icon: Tag,
      },
      ...typeStats.slice(0, 5),
    ];
  }, [categories.total, categories.data]);

  const columns = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: 'Name',
        cell: (info) => {
          const category = info.row.original;
          return (
            <div className="flex items-center gap-2">
              {category.color && (
                <div
                  className="h-4 w-4 rounded-full border"
                  style={{ backgroundColor: category.color }}
                />
              )}
              <div className="flex flex-col">
                <span className="font-medium">{category.name}</span>
                {category.slug && (
                  <span className="text-sm text-muted-foreground">{category.slug}</span>
                )}
              </div>
            </div>
          );
        },
      },
      {
        accessorKey: 'type',
        header: 'Type',
        cell: (info) => {
          const category = info.row.original;
          const getTypeColor = (type) => {
            if (!type) return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';

            let hash = 0;
            for (let i = 0; i < type.length; i++) {
              hash = type.charCodeAt(i) + ((hash << 5) - hash);
            }

            const colors = [
              'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
              'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
              'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
              'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
              'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
              'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
              'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            ];

            return colors[Math.abs(hash) % colors.length];
          };

          return (
            <Badge className={getTypeColor(category.type)}>
              {category.type?.charAt(0).toUpperCase() + category.type?.slice(1)}
            </Badge>
          );
        },
      },
      {
        accessorKey: 'parent.name',
        header: 'Parent',
        cell: (info) => {
          const category = info.row.original;
          return category.parent ? (
            <span className="text-sm">{category.parent.name}</span>
          ) : (
            <span className="text-sm text-muted-foreground">—</span>
          );
        },
      },
      {
        accessorKey: 'description',
        header: 'Description',
        cell: (info) => {
          const category = info.row.original;
          return category.description ? (
            <span className="text-sm text-muted-foreground line-clamp-1">
              {category.description}
            </span>
          ) : (
            <span className="text-sm text-muted-foreground">—</span>
          );
        },
      },
      {
        accessorKey: 'created_at',
        header: 'Created',
        cell: (info) => {
          const category = info.row.original;
          return (
            <span className="text-sm">{new Date(category.created_at).toLocaleDateString()}</span>
          );
        },
      },
      {
        id: 'actions',
        header: 'Actions',
        cell: (info) => {
          const category = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <Link href={route('categories.edit', { category: category.id })}>
                  <Pencil className="h-4 w-4" />
                  <span className="sr-only">Edit category</span>
                </Link>
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                onClick={() => handleDeleteClick(category)}
              >
                <Trash2 className="h-4 w-4" />
                <span className="sr-only">Delete category</span>
              </Button>
            </div>
          );
        },
      },
    ],
    [handleDeleteClick]
  );

  const pageCount = useMemo(() => {
    if (categories.total !== undefined && categories.per_page) {
      return Math.ceil(categories.total / categories.per_page);
    }
    return undefined;
  }, [categories.total, categories.per_page]);

  const { tableProps } = useDatatable({
    data: categories.data || [],
    columns,
    route: route('categories.index'),
    serverSide: true,
    pageSize: categories.per_page || defaultPerPage,
    initialSorting: [{ id: 'created_at', desc: true }],
    pageCount: pageCount,
    only: ['categories'],
    initialFilters: typeFilter ? { type: typeFilter } : {},
  });

  useEffect(() => {
    if (!tableProps.table || categories.current_page === undefined) {
      return;
    }

    const currentPageIndex = categories.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = categories.per_page || defaultPerPage;

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
  }, [tableProps.table, categories.current_page, categories.per_page, defaultPerPage]);

  return (
    <DashboardLayout header="Categories">
      <PageShell
        title="Categories"
        actions={
          <Link href={route('categories.create')}>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New Category
            </Button>
          </Link>
        }
      >
        {stats.length > 0 && <StatGrid stats={stats} columns={4} />}

        <div className="space-y-4">
          <div className="flex items-center gap-4">
            <div className="space-y-2">
              <Label htmlFor="type-filter">Filter by Type</Label>
              <select
                id="type-filter"
                value={typeFilter}
                onChange={(e) => handleTypeFilterChange(e.target.value)}
                className="flex h-9 w-[180px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option value="">All Types</option>
                {types.map((type) => (
                  <option key={type} value={type}>
                    {type.charAt(0).toUpperCase() + type.slice(1)}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <DataTable
            {...tableProps}
            title="Categories"
            description="A list of all categories in your system"
            showCard={true}
          />
        </div>
      </PageShell>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          deleteDialog.onClose();
          setCategoryToDelete(null);
        }}
        title="Delete Category"
        message={
          categoryToDelete
            ? `Are you sure you want to delete "${categoryToDelete.name}"? This action cannot be undone.`
            : 'Are you sure you want to delete this category?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />
    </DashboardLayout>
  );
}
