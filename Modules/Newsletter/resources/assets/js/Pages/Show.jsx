/**
 * Newsletter campaign detail page displaying full campaign content
 * Includes edit and delete actions with campaign metadata
 */
import { useDisclosure } from '@/Hooks/useDisclosure';
import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, Pencil, Send, Trash2 } from 'lucide-react';
import { useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Show({ campaign, selectedPosts = [] }) {
  const deleteDialog = useDisclosure();
  const [isDeleting, setIsDeleting] = useState(false);

  if (!campaign || !campaign.id) {
    return (
      <DashboardLayout header="Newsletter">
        <PageShell title="Campaign Not Found">
          <div className="text-center py-8">
            <p className="text-muted-foreground">Campaign not found or invalid.</p>
            <Link href={route('newsletter.index')}>
              <Button variant="outline" className="mt-4">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Campaigns
              </Button>
            </Link>
          </div>
        </PageShell>
      </DashboardLayout>
    );
  }

  const editUrl = route('newsletter.edit', { newsletter: campaign.id });
  const deleteUrl = route('newsletter.destroy', { newsletter: campaign.id });
  const sendUrl = route('newsletter.send', { newsletter: campaign.id });

  const canEdit = campaign.status === 'draft';
  const canDelete = campaign.status !== 'sending';
  const canSend = campaign.status === 'draft';

  const handleDeleteClick = () => {
    deleteDialog.onOpen();
  };

  const handleDeleteConfirm = () => {
    setIsDeleting(true);
    router.delete(deleteUrl, {
      onSuccess: () => {
        deleteDialog.onClose();
        setIsDeleting(false);
      },
      onError: () => {
        setIsDeleting(false);
      },
    });
  };

  const handleSend = () => {
    router.post(sendUrl);
  };

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

  const formatDate = (dateString) => {
    if (!dateString) {
      return '';
    }
    try {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
    } catch {
      return dateString;
    }
  };

  return (
    <DashboardLayout header="Newsletter">
      <PageShell title={campaign.subject}>
        <div className="space-y-6">
          <div className="flex items-center justify-between">
            <Link href={route('newsletter.index')}>
              <Button variant="outline">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Campaigns
              </Button>
            </Link>
            <div className="flex gap-2">
              {canEdit && (
                <Link href={editUrl}>
                  <Button variant="outline">
                    <Pencil className="mr-2 h-4 w-4" />
                    Edit
                  </Button>
                </Link>
              )}
              {canSend && (
                <Button variant="default" onClick={handleSend}>
                  <Send className="mr-2 h-4 w-4" />
                  Send Campaign
                </Button>
              )}
              {canDelete && (
                <Button variant="destructive" onClick={handleDeleteClick}>
                  <Trash2 className="mr-2 h-4 w-4" />
                  Delete
                </Button>
              )}
            </div>
          </div>

          <Card>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <CardTitle className="text-3xl">{campaign.subject}</CardTitle>
                  <div className="mt-4 flex items-center gap-4 text-sm text-muted-foreground">
                    <span className="flex items-center gap-1">
                      <Send className="h-4 w-4" />
                      Status: {getStatusBadge(campaign.status)}
                    </span>
                    {campaign.scheduled_at && (
                      <span className="flex items-center gap-1">
                        <Calendar className="h-4 w-4" />
                        Scheduled: {formatDate(campaign.scheduled_at)}
                      </span>
                    )}
                    <span>Created: {formatDate(campaign.created_at)}</span>
                  </div>
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="prose max-w-none dark:prose-invert">
                <div className="whitespace-pre-wrap">{campaign.content}</div>
              </div>

              {selectedPosts.length > 0 && (
                <div className="mt-6 space-y-2">
                  <h3 className="text-lg font-semibold">Selected Blog Posts</h3>
                  <div className="space-y-2">
                    {selectedPosts.map((post) => (
                      <Card key={post.id}>
                        <CardContent className="p-4">
                          <div className="flex items-start justify-between">
                            <div>
                              <h4 className="font-medium">{post.title}</h4>
                              {post.published_at && (
                                <p className="text-sm text-muted-foreground mt-1">
                                  Published: {formatDate(post.published_at)}
                                </p>
                              )}
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                </div>
              )}

              {(!selectedPosts || selectedPosts.length === 0) && (
                <div className="mt-6 rounded-lg bg-muted p-4">
                  <p className="text-sm text-muted-foreground">
                    No blog posts selected for this campaign.
                  </p>
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
        title="Delete Campaign"
        message={
          campaign
            ? `Are you sure you want to delete "${campaign.subject}"? This action cannot be undone.`
            : 'Are you sure you want to delete this campaign?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
        processing={isDeleting}
      />
    </DashboardLayout>
  );
}
