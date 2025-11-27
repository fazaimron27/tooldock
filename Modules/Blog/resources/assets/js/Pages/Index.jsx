/**
 * Blog posts listing page with server-side pagination, sorting, and search
 * Displays post statistics, manages post deletion, and syncs pagination state
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { Link, router } from '@inertiajs/react';
import { Eye, FileText, Pencil, Plus, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import StatGrid from '@/Components/DataDisplay/StatGrid';
import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ posts }) {
  const deleteDialog = useDisclosure();
  const [postToDelete, setPostToDelete] = useState(null);

  const handleDeleteClick = useCallback(
    (post) => {
      setPostToDelete(post);
      deleteDialog.onOpen();
    },
    [deleteDialog]
  );

  const handleDeleteConfirm = () => {
    if (postToDelete) {
      router.delete(route('blog.destroy', { blog: postToDelete.id }), {
        onSuccess: () => {
          deleteDialog.onClose();
          setPostToDelete(null);
        },
      });
    }
  };

  const stats = useMemo(() => {
    const total = posts.total ?? 0;
    const now = new Date();
    const published =
      posts.data?.filter((post) => post.published_at && new Date(post.published_at) <= now)
        .length ?? 0;
    const drafts = total - published;

    return [
      {
        id: 'total',
        title: 'Total Posts',
        value: total.toString(),
        icon: FileText,
      },
      {
        id: 'published',
        title: 'Published',
        value: published.toString(),
        icon: Eye,
        change: `${total > 0 ? Math.round((published / total) * 100) : 0}%`,
        trend: 'up',
      },
      {
        id: 'drafts',
        title: 'Drafts',
        value: drafts.toString(),
        icon: FileText,
      },
    ];
  }, [posts.total, posts.data]);

  const columns = useMemo(
    () => [
      {
        accessorKey: 'title',
        header: 'Title',
        cell: (info) => {
          const post = info.row.original;
          return (
            <div className="flex flex-col">
              <span className="font-medium">{post.title}</span>
              {post.excerpt && (
                <span className="text-sm text-muted-foreground line-clamp-1">{post.excerpt}</span>
              )}
            </div>
          );
        },
      },
      {
        accessorKey: 'user.name',
        header: 'Author',
        cell: (info) => {
          const post = info.row.original;
          const userName = post.user?.name || 'Unknown';
          const initials = userName
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
              <span>{userName}</span>
            </div>
          );
        },
      },
      {
        id: 'status',
        header: 'Status',
        cell: (info) => {
          const post = info.row.original;
          const isPublished = post.published_at && new Date(post.published_at) <= new Date();
          return (
            <span
              className={`rounded-full px-3 py-1 text-xs font-medium ${
                isPublished
                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                  : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
              }`}
            >
              {isPublished ? 'Published' : 'Draft'}
            </span>
          );
        },
      },
      {
        accessorKey: 'published_at',
        header: 'Published Date',
        cell: (info) => {
          const post = info.row.original;
          return post.published_at ? (
            <span>{new Date(post.published_at).toLocaleDateString()}</span>
          ) : (
            <span className="text-muted-foreground">Not published</span>
          );
        },
      },
      {
        accessorKey: 'created_at',
        header: 'Created',
        cell: (info) => {
          const post = info.row.original;
          return <span>{new Date(post.created_at).toLocaleDateString()}</span>;
        },
      },
      {
        id: 'actions',
        header: 'Actions',
        cell: (info) => {
          const post = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <Link href={route('blog.show', { blog: post.id })}>
                  <Eye className="h-4 w-4" />
                  <span className="sr-only">View post</span>
                </Link>
              </Button>
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <Link href={route('blog.edit', { blog: post.id })}>
                  <Pencil className="h-4 w-4" />
                  <span className="sr-only">Edit post</span>
                </Link>
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                onClick={() => handleDeleteClick(post)}
              >
                <Trash2 className="h-4 w-4" />
                <span className="sr-only">Delete post</span>
              </Button>
            </div>
          );
        },
      },
    ],
    [handleDeleteClick]
  );

  const pageCount = useMemo(() => {
    if (posts.total !== undefined && posts.per_page) {
      return Math.ceil(posts.total / posts.per_page);
    }
    return undefined;
  }, [posts.total, posts.per_page]);

  const { tableProps } = useDatatable({
    data: posts.data || [],
    columns,
    route: route('blog.index'),
    serverSide: true,
    pageSize: posts.per_page || 10,
    initialSorting: [{ id: 'created_at', desc: true }],
    pageCount: pageCount,
    only: ['posts'],
  });

  useEffect(() => {
    if (!tableProps.table || posts.current_page === undefined) {
      return;
    }

    const currentPageIndex = posts.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = posts.per_page || 10;

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
  }, [tableProps.table, posts.current_page, posts.per_page]);

  return (
    <DashboardLayout header="Blog">
      <PageShell
        title="Blog Posts"
        actions={
          <Link href={route('blog.create')}>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New Post
            </Button>
          </Link>
        }
      >
        {stats.length > 0 && <StatGrid stats={stats} columns={3} />}

        <DataTable
          {...tableProps}
          title="Blog Posts"
          description="A list of all blog posts in your account"
          showCard={true}
        />
      </PageShell>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          deleteDialog.onClose();
          setPostToDelete(null);
        }}
        title="Delete Post"
        message={
          postToDelete
            ? `Are you sure you want to delete "${postToDelete.title}"? This action cannot be undone.`
            : 'Are you sure you want to delete this post?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />
    </DashboardLayout>
  );
}
