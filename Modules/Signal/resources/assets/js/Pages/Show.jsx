/**
 * Signal Show Page - Notification Details
 *
 * Displays a single notification with:
 * - Type-based icon and styling
 * - Full title and message
 * - Timestamp and module source
 * - Previous/Next navigation
 * - Action button (if action_url exists)
 * - Delete button with confirmation
 * - Back to notifications link
 */
import { Head, Link, router } from '@inertiajs/react';
import {
  AlertTriangle,
  ArrowLeft,
  CheckCircle,
  ChevronLeft,
  ChevronRight,
  Clock,
  ExternalLink,
  Info,
  ShieldAlert,
  Trash2,
} from 'lucide-react';
import { useState } from 'react';

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
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

const typeConfig = {
  info: {
    icon: Info,
    label: 'Information',
    className: 'text-blue-500 bg-blue-500/10',
    badgeVariant: 'secondary',
  },
  success: {
    icon: CheckCircle,
    label: 'Success',
    className: 'text-green-500 bg-green-500/10',
    badgeVariant: 'default',
  },
  warning: {
    icon: AlertTriangle,
    label: 'Warning',
    className: 'text-yellow-500 bg-yellow-500/10',
    badgeVariant: 'outline',
  },
  error: {
    icon: ShieldAlert,
    label: 'Alert',
    className: 'text-red-500 bg-red-500/10',
    badgeVariant: 'destructive',
  },
};

export default function Show({ notification, navigation }) {
  const [isDeleting, setIsDeleting] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const config = typeConfig[notification.type] || typeConfig.info;
  const Icon = config.icon;

  const handleDeleteClick = () => {
    setShowDeleteConfirm(true);
  };

  const handleDeleteConfirm = () => {
    setIsDeleting(true);
    router.delete(route('notifications.destroy', { notification: notification.id }), {
      onFinish: () => {
        setIsDeleting(false);
        setShowDeleteConfirm(false);
      },
    });
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  return (
    <DashboardLayout>
      <Head title={notification.title} />

      <div className="max-w-3xl mx-auto space-y-6">
        {/* Navigation Header */}
        <div className="flex items-center justify-between">
          <Button variant="ghost" size="sm" asChild className="gap-2">
            <Link href={route('notifications.index')}>
              <ArrowLeft className="h-4 w-4" />
              Back to Notifications
            </Link>
          </Button>

          {/* Prev/Next Navigation */}
          <div className="flex items-center gap-2">
            <span className="text-sm text-muted-foreground">
              {navigation?.current} of {navigation?.total}
            </span>
            <div className="flex items-center">
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8"
                disabled={!navigation?.prev}
                asChild={!!navigation?.prev}
              >
                {navigation?.prev ? (
                  <Link href={route('notifications.show', { notification: navigation.prev })}>
                    <ChevronLeft className="h-4 w-4" />
                    <span className="sr-only">Previous notification</span>
                  </Link>
                ) : (
                  <span>
                    <ChevronLeft className="h-4 w-4" />
                  </span>
                )}
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8"
                disabled={!navigation?.next}
                asChild={!!navigation?.next}
              >
                {navigation?.next ? (
                  <Link href={route('notifications.show', { notification: navigation.next })}>
                    <ChevronRight className="h-4 w-4" />
                    <span className="sr-only">Next notification</span>
                  </Link>
                ) : (
                  <span>
                    <ChevronRight className="h-4 w-4" />
                  </span>
                )}
              </Button>
            </div>
          </div>
        </div>

        {/* Notification Card */}
        <Card>
          <CardHeader className="pb-4">
            <div className="flex items-start gap-4">
              {/* Icon */}
              <div className={`flex-shrink-0 p-3 rounded-full ${config.className}`}>
                <Icon className="h-6 w-6" />
              </div>

              {/* Header Content */}
              <div className="flex-1 min-w-0">
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <CardTitle className="text-xl">{notification.title}</CardTitle>
                    <CardDescription className="flex flex-wrap items-center gap-2 mt-2">
                      <Badge variant={config.badgeVariant}>{config.label}</Badge>
                      {notification.module_source && (
                        <span className="text-muted-foreground">
                          via {notification.module_source}
                        </span>
                      )}
                    </CardDescription>
                  </div>
                </div>
              </div>
            </div>
          </CardHeader>

          <CardContent className="space-y-6">
            {/* Message */}
            <div className="prose prose-sm dark:prose-invert max-w-none">
              <p className="text-foreground leading-relaxed">{notification.message}</p>
            </div>

            {/* Metadata */}
            <div className="flex items-center gap-2 text-sm text-muted-foreground border-t pt-4">
              <Clock className="h-4 w-4" />
              <span>{formatDate(notification.created_at)}</span>
              <span className="text-muted-foreground/50">•</span>
              <span>{notification.created_at_human}</span>
            </div>

            {/* Actions */}
            <div className="flex items-center gap-3 pt-2">
              {notification.action_url && (
                <Button asChild>
                  <a href={notification.action_url}>
                    <ExternalLink className="mr-2 h-4 w-4" />
                    Go to Action
                  </a>
                </Button>
              )}
              <Button variant="destructive" onClick={handleDeleteClick} disabled={isDeleting}>
                <Trash2 className="mr-2 h-4 w-4" />
                {isDeleting ? 'Deleting...' : 'Delete Notification'}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={showDeleteConfirm} onOpenChange={setShowDeleteConfirm}>
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
    </DashboardLayout>
  );
}
