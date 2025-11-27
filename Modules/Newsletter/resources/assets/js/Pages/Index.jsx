/**
 * Newsletter campaigns listing page with server-side pagination, sorting, and search
 * Displays campaign statistics, manages campaign deletion, and syncs pagination state
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { Link, router } from '@inertiajs/react';
import { Calendar, Eye, Pencil, Plus, Send, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import StatGrid from '@/Components/DataDisplay/StatGrid';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ campaigns, defaultPerPage = 20 }) {
  const deleteDialog = useDisclosure();
  const [campaignToDelete, setCampaignToDelete] = useState(null);

  const handleDeleteClick = useCallback(
    (campaign) => {
      setCampaignToDelete(campaign);
      deleteDialog.onOpen();
    },
    [deleteDialog]
  );

  const handleDeleteConfirm = () => {
    if (campaignToDelete) {
      router.delete(route('newsletter.destroy', { newsletter: campaignToDelete.id }), {
        onSuccess: () => {
          deleteDialog.onClose();
          setCampaignToDelete(null);
        },
      });
    }
  };

  const stats = useMemo(() => {
    const total = campaigns.total ?? 0;
    const draft = campaigns.data?.filter((campaign) => campaign.status === 'draft').length ?? 0;
    const sent = campaigns.data?.filter((campaign) => campaign.status === 'sent').length ?? 0;
    const sending = campaigns.data?.filter((campaign) => campaign.status === 'sending').length ?? 0;

    return [
      {
        id: 'total',
        title: 'Total Campaigns',
        value: total.toString(),
        icon: Send,
      },
      {
        id: 'draft',
        title: 'Drafts',
        value: draft.toString(),
        icon: Calendar,
        change: `${total > 0 ? Math.round((draft / total) * 100) : 0}%`,
        trend: 'up',
      },
      {
        id: 'sent',
        title: 'Sent',
        value: sent.toString(),
        icon: Send,
      },
      {
        id: 'sending',
        title: 'Sending',
        value: sending.toString(),
        icon: Send,
      },
    ];
  }, [campaigns.total, campaigns.data]);

  const getStatusBadge = (status) => {
    const statusConfig = {
      draft: {
        className: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        label: 'Draft',
      },
      sending: {
        className: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        label: 'Sending',
      },
      sent: {
        className: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        label: 'Sent',
      },
    };

    const config = statusConfig[status] || statusConfig.draft;

    return (
      <span className={`rounded-full px-3 py-1 text-xs font-medium ${config.className}`}>
        {config.label}
      </span>
    );
  };

  const columns = useMemo(
    () => [
      {
        accessorKey: 'subject',
        header: 'Subject',
        cell: (info) => {
          const campaign = info.row.original;
          return (
            <div className="flex flex-col">
              <span className="font-medium">{campaign.subject}</span>
              {campaign.content && (
                <span className="text-sm text-muted-foreground line-clamp-1">
                  {campaign.content.substring(0, 100)}
                  {campaign.content.length > 100 ? '...' : ''}
                </span>
              )}
            </div>
          );
        },
      },
      {
        accessorKey: 'status',
        header: 'Status',
        cell: (info) => {
          const campaign = info.row.original;
          return getStatusBadge(campaign.status);
        },
      },
      {
        id: 'posts',
        header: 'Posts',
        cell: (info) => {
          const campaign = info.row.original;
          const postCount = Array.isArray(campaign.selected_posts)
            ? campaign.selected_posts.length
            : 0;
          return (
            <span>
              {postCount} post{postCount !== 1 ? 's' : ''}
            </span>
          );
        },
      },
      {
        accessorKey: 'scheduled_at',
        header: 'Scheduled At',
        cell: (info) => {
          const campaign = info.row.original;
          return campaign.scheduled_at ? (
            <span>{new Date(campaign.scheduled_at).toLocaleDateString()}</span>
          ) : (
            <span className="text-muted-foreground">Not scheduled</span>
          );
        },
      },
      {
        accessorKey: 'created_at',
        header: 'Created',
        cell: (info) => {
          const campaign = info.row.original;
          return <span>{new Date(campaign.created_at).toLocaleDateString()}</span>;
        },
      },
      {
        id: 'actions',
        header: 'Actions',
        cell: (info) => {
          const campaign = info.row.original;
          const canEdit = campaign.status === 'draft';
          const canDelete = campaign.status !== 'sending';
          const canSend = campaign.status === 'draft';

          const handleSend = (e) => {
            e.preventDefault();
            router.post(route('newsletter.send', { newsletter: campaign.id }));
          };

          return (
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <Link href={route('newsletter.show', { newsletter: campaign.id })}>
                  <Eye className="h-4 w-4" />
                  <span className="sr-only">View campaign</span>
                </Link>
              </Button>
              {canEdit && (
                <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                  <Link href={route('newsletter.edit', { newsletter: campaign.id })}>
                    <Pencil className="h-4 w-4" />
                    <span className="sr-only">Edit campaign</span>
                  </Link>
                </Button>
              )}
              {canSend && (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8 text-primary hover:text-primary hover:bg-primary/10"
                  onClick={handleSend}
                  title="Send campaign"
                >
                  <Send className="h-4 w-4" />
                  <span className="sr-only">Send campaign</span>
                </Button>
              )}
              {canDelete && (
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                  onClick={() => handleDeleteClick(campaign)}
                >
                  <Trash2 className="h-4 w-4" />
                  <span className="sr-only">Delete campaign</span>
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
    if (campaigns.total !== undefined && campaigns.per_page) {
      return Math.ceil(campaigns.total / campaigns.per_page);
    }
    return undefined;
  }, [campaigns.total, campaigns.per_page]);

  const { tableProps } = useDatatable({
    data: campaigns.data || [],
    columns,
    route: route('newsletter.index'),
    serverSide: true,
    pageSize: campaigns.per_page || defaultPerPage,
    initialSorting: [{ id: 'created_at', desc: true }],
    pageCount: pageCount,
    only: ['campaigns'],
  });

  useEffect(() => {
    if (!tableProps.table || campaigns.current_page === undefined) {
      return;
    }

    const currentPageIndex = campaigns.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = campaigns.per_page || defaultPerPage;

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
  }, [tableProps.table, campaigns.current_page, campaigns.per_page, defaultPerPage]);

  return (
    <DashboardLayout header="Newsletter">
      <PageShell
        title="Campaigns"
        actions={
          <Link href={route('newsletter.create')}>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New Campaign
            </Button>
          </Link>
        }
      >
        {stats.length > 0 && <StatGrid stats={stats} columns={4} />}

        <DataTable
          {...tableProps}
          title="Email Campaigns"
          description="A list of all email campaigns"
          showCard={true}
        />
      </PageShell>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          deleteDialog.onClose();
          setCampaignToDelete(null);
        }}
        title="Delete Campaign"
        message={
          campaignToDelete
            ? `Are you sure you want to delete "${campaignToDelete.subject}"? This action cannot be undone.`
            : 'Are you sure you want to delete this campaign?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />
    </DashboardLayout>
  );
}
