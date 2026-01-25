/**
 * Signal Index Page - Notification Inbox
 *
 * Full notification history with:
 * - All/Unread filter tabs
 * - Paginated notification list
 * - Checkbox selection for bulk actions
 * - Bulk mark as read / bulk delete with confirmation
 * - Individual delete actions with confirmation
 * - Real-time updates via WebSocket
 */
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Bell, Check, CheckCheck, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';

import DashboardLayout from '@/Layouts/DashboardLayout';

import SignalItem from '../Components/SignalItem';

export default function Index({ notifications, filter, counts }) {
  const { auth } = usePage().props;
  const [isMarkingAll, setIsMarkingAll] = useState(false);
  const [isBulkAction, setIsBulkAction] = useState(false);
  const [deletingId, setDeletingId] = useState(null);
  const [selectedIds, setSelectedIds] = useState([]);

  const [deleteConfirmId, setDeleteConfirmId] = useState(null);
  const [showBulkDeleteConfirm, setShowBulkDeleteConfirm] = useState(false);

  useEffect(() => {
    if (!auth?.user?.id || typeof window.Echo === 'undefined') {
      return;
    }

    const channel = window.Echo.private(`App.Models.User.${auth.user.id}`);

    const handleNewNotification = () => {
      router.reload({ only: ['notifications', 'counts'], preserveScroll: true });
    };

    channel.listen('.notification.received', handleNewNotification);
    channel.notification(handleNewNotification);

    return () => {
      channel.stopListening('.notification.received');
      channel.stopListening('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated');
    };
  }, [auth?.user?.id]);

  const allSelected = useMemo(() => {
    if (!notifications.data?.length) return false;
    return notifications.data.every((n) => selectedIds.includes(n.id));
  }, [notifications.data, selectedIds]);

  const someSelected = useMemo(() => {
    if (!notifications.data?.length) return false;
    return selectedIds.length > 0 && !allSelected;
  }, [notifications.data, selectedIds, allSelected]);

  const handleFilterChange = (newFilter) => {
    setSelectedIds([]);
    router.get(route('notifications.index'), { filter: newFilter }, { preserveState: true });
  };

  const handleMarkAllRead = async () => {
    setIsMarkingAll(true);
    router.post(
      route('notifications.read-all'),
      {},
      {
        preserveScroll: true,
        onFinish: () => setIsMarkingAll(false),
      }
    );
  };

  const handleMarkAsRead = (id) => {
    router.post(route('notifications.read', { notification: id }), {}, { preserveScroll: true });
  };

  const handleDeleteClick = (id) => {
    setDeleteConfirmId(id);
  };
  const handleDeleteConfirm = () => {
    if (!deleteConfirmId) return;
    setDeletingId(deleteConfirmId);
    router.delete(route('notifications.destroy', { notification: deleteConfirmId }), {
      preserveScroll: true,
      onFinish: () => {
        setDeletingId(null);
        setDeleteConfirmId(null);
      },
    });
  };

  const handleSelectAll = (checked) => {
    if (checked) {
      setSelectedIds(notifications.data?.map((n) => n.id) || []);
    } else {
      setSelectedIds([]);
    }
  };

  const handleSelectItem = (id, checked) => {
    if (checked) {
      setSelectedIds((prev) => [...prev, id]);
    } else {
      setSelectedIds((prev) => prev.filter((i) => i !== id));
    }
  };

  const handleBulkMarkAsRead = () => {
    if (!selectedIds.length) return;
    setIsBulkAction(true);
    router.post(
      route('notifications.bulk-read'),
      { ids: selectedIds },
      {
        preserveScroll: true,
        onSuccess: () => setSelectedIds([]),
        onFinish: () => setIsBulkAction(false),
      }
    );
  };

  const handleBulkDeleteClick = () => {
    if (!selectedIds.length) return;
    setShowBulkDeleteConfirm(true);
  };
  const handleBulkDeleteConfirm = () => {
    setIsBulkAction(true);
    router.delete(route('notifications.bulk-destroy'), {
      data: { ids: selectedIds },
      preserveScroll: true,
      onSuccess: () => setSelectedIds([]),
      onFinish: () => {
        setIsBulkAction(false);
        setShowBulkDeleteConfirm(false);
      },
    });
  };

  const hasUnread = notifications.data?.some((n) => !n.read_at);
  const selectedUnreadCount = notifications.data?.filter(
    (n) => selectedIds.includes(n.id) && !n.read_at
  ).length;

  return (
    <DashboardLayout>
      <Head title="Notifications" />

      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Notifications</h1>
            <p className="text-muted-foreground">
              Stay updated with your latest alerts and messages
            </p>
          </div>
          <div className="flex items-center gap-2">
            {selectedIds.length > 0 ? (
              <>
                <span className="text-sm text-muted-foreground mr-2">
                  {selectedIds.length} selected
                </span>
                {selectedUnreadCount > 0 && (
                  <Button
                    onClick={handleBulkMarkAsRead}
                    disabled={isBulkAction}
                    variant="outline"
                    size="sm"
                  >
                    <CheckCheck className="mr-2 h-4 w-4" />
                    Mark as read
                  </Button>
                )}
                <Button
                  onClick={handleBulkDeleteClick}
                  disabled={isBulkAction}
                  variant="destructive"
                  size="sm"
                >
                  <Trash2 className="mr-2 h-4 w-4" />
                  Delete
                </Button>
              </>
            ) : (
              hasUnread && (
                <Button onClick={handleMarkAllRead} disabled={isMarkingAll} variant="outline">
                  <Check className="mr-2 h-4 w-4" />
                  {isMarkingAll ? 'Marking...' : 'Mark all as read'}
                </Button>
              )
            )}
          </div>
        </div>

        {/* Filters */}
        <Tabs value={filter} onValueChange={handleFilterChange}>
          <TabsList>
            <TabsTrigger value="all">
              All{' '}
              {counts?.all > 0 && (
                <span className="ml-1.5 text-xs text-muted-foreground">({counts.all})</span>
              )}
            </TabsTrigger>
            <TabsTrigger value="unread">
              Unread{' '}
              {counts?.unread > 0 && (
                <span className="ml-1.5 text-xs text-muted-foreground">({counts.unread})</span>
              )}
            </TabsTrigger>
          </TabsList>
        </Tabs>

        {/* Notifications List */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Bell className="h-5 w-5" />
              {filter === 'unread' ? 'Unread Notifications' : 'All Notifications'}
            </CardTitle>
            <CardDescription>
              {notifications.total} notification{notifications.total !== 1 ? 's' : ''}
            </CardDescription>
          </CardHeader>
          <CardContent>
            {notifications.data?.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-12 text-center">
                <Bell className="h-12 w-12 text-muted-foreground/30 mb-4" />
                <h3 className="font-medium text-lg">No notifications</h3>
                <p className="text-sm text-muted-foreground mt-1">
                  {filter === 'unread'
                    ? "You're all caught up! No unread notifications."
                    : "You don't have any notifications yet."}
                </p>
              </div>
            ) : (
              <>
                {/* Select All Header */}
                <div className="flex items-center gap-3 pb-3 border-b mb-2">
                  <Checkbox
                    checked={allSelected}
                    onCheckedChange={handleSelectAll}
                    aria-label="Select all notifications"
                    className={someSelected ? 'data-[state=checked]:bg-primary/50' : ''}
                    {...(someSelected ? { 'data-state': 'checked' } : {})}
                  />
                  <span className="text-sm text-muted-foreground">
                    {allSelected
                      ? 'All selected'
                      : someSelected
                        ? `${selectedIds.length} selected`
                        : 'Select all'}
                  </span>
                </div>

                {/* Notification Items */}
                <div className="divide-y">
                  {notifications.data?.map((notification) => (
                    <div key={notification.id} className="flex items-start gap-3 py-2">
                      <div className="pt-3">
                        <Checkbox
                          checked={selectedIds.includes(notification.id)}
                          onCheckedChange={(checked) => handleSelectItem(notification.id, checked)}
                          aria-label={`Select notification: ${notification.title}`}
                        />
                      </div>
                      <div className="flex-1">
                        <SignalItem notification={notification} onMarkAsRead={handleMarkAsRead} />
                      </div>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8 text-muted-foreground hover:text-destructive mt-2"
                        onClick={() => handleDeleteClick(notification.id)}
                        disabled={deletingId === notification.id}
                      >
                        <Trash2 className="h-4 w-4" />
                        <span className="sr-only">Delete notification</span>
                      </Button>
                    </div>
                  ))}
                </div>
              </>
            )}

            {/* Pagination */}
            {notifications.last_page > 1 && (
              <div className="flex items-center justify-between mt-6 pt-4 border-t">
                <p className="text-sm text-muted-foreground">
                  Page {notifications.current_page} of {notifications.last_page}
                </p>
                <div className="flex items-center gap-2">
                  {notifications.links?.map((link, index) => (
                    <Button
                      key={index}
                      variant={link.active ? 'default' : 'outline'}
                      size="sm"
                      disabled={!link.url}
                      asChild={!!link.url}
                    >
                      {link.url ? (
                        <Link href={link.url} preserveScroll>
                          <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Link>
                      ) : (
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                      )}
                    </Button>
                  ))}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Individual Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmId} onOpenChange={() => setDeleteConfirmId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Notification</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this notification? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteConfirm}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Bulk Delete Confirmation Dialog */}
      <AlertDialog open={showBulkDeleteConfirm} onOpenChange={setShowBulkDeleteConfirm}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete {selectedIds.length} Notification(s)</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete {selectedIds.length} selected notification
              {selectedIds.length !== 1 ? 's' : ''}? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleBulkDeleteConfirm}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Delete {selectedIds.length} Notification{selectedIds.length !== 1 ? 's' : ''}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </DashboardLayout>
  );
}
