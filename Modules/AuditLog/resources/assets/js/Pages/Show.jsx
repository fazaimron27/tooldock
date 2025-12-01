/**
 * Audit Log detail page displaying full audit log information
 * Shows old vs new values with diff visualization
 */
import { formatDate, getInitials } from '@/Utils/format';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Show({ auditLog }) {
  if (!auditLog || !auditLog.id) {
    return (
      <DashboardLayout header="AuditLog">
        <PageShell title="Audit Log Not Found">
          <div className="text-center py-8">
            <p className="text-muted-foreground">Audit log not found or invalid.</p>
            <Link href={route('auditlog.index')}>
              <Button variant="outline" className="mt-4">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Audit Logs
              </Button>
            </Link>
          </div>
        </PageShell>
      </DashboardLayout>
    );
  }

  const getEventBadge = (event) => {
    const config = {
      created: {
        variant: 'default',
        className:
          'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-800/60 dark:hover:text-green-200',
        label: 'Created',
      },
      updated: {
        variant: 'default',
        className:
          'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-800/60 dark:hover:text-blue-200',
        label: 'Updated',
      },
      deleted: {
        variant: 'default',
        className:
          'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-800/60 dark:hover:text-red-200',
        label: 'Deleted',
      },
    };

    const eventConfig = config[event] || config.updated;

    return (
      <Badge className={eventConfig.className} variant={eventConfig.variant}>
        {eventConfig.label}
      </Badge>
    );
  };

  const getModelDisplayName = (auditableType, auditableId) => {
    if (!auditableType || !auditableId) {
      return 'N/A';
    }

    const className = auditableType.split('\\').pop();

    return `${className} #${auditableId}`;
  };

  const renderValue = (value) => {
    if (value === null) {
      return <span className="text-muted-foreground italic">null</span>;
    }

    if (typeof value === 'boolean') {
      return <span className="font-mono">{value ? 'true' : 'false'}</span>;
    }

    if (typeof value === 'object') {
      return (
        <pre className="overflow-auto rounded bg-muted p-2 text-xs">
          {JSON.stringify(value, null, 2)}
        </pre>
      );
    }

    return <span>{String(value)}</span>;
  };

  const renderDiff = () => {
    if (auditLog.event === 'created') {
      return (
        <div className="space-y-4">
          <div>
            <h4 className="mb-2 font-semibold text-green-600 dark:text-green-400">New Values</h4>
            <div className="space-y-2 rounded-lg border border-green-200 bg-green-50/50 p-4 dark:border-green-900/30 dark:bg-green-900/10">
              {Object.entries(auditLog.new_values || {}).map(([key, value]) => (
                <div
                  key={key}
                  className="border-b border-green-200 pb-2 last:border-0 dark:border-green-900/30"
                >
                  <div className="font-mono text-xs text-muted-foreground">{key}</div>
                  <div className="mt-1">{renderValue(value)}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      );
    }

    if (auditLog.event === 'deleted') {
      return (
        <div className="space-y-4">
          <div>
            <h4 className="mb-2 font-semibold text-red-600 dark:text-red-400">Deleted Values</h4>
            <div className="space-y-2 rounded-lg border border-red-200 bg-red-50/50 p-4 dark:border-red-900/30 dark:bg-red-900/10">
              {Object.entries(auditLog.old_values || {}).map(([key, value]) => (
                <div
                  key={key}
                  className="border-b border-red-200 pb-2 last:border-0 dark:border-red-900/30"
                >
                  <div className="font-mono text-xs text-muted-foreground">{key}</div>
                  <div className="mt-1">{renderValue(value)}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      );
    }

    /**
     * Updated event - show diff between old and new values.
     */
    const oldValues = auditLog.old_values || {};
    const newValues = auditLog.new_values || {};
    const allKeys = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);

    return (
      <div className="space-y-4">
        {Array.from(allKeys).map((key) => {
          const oldValue = oldValues[key];
          const newValue = newValues[key];
          const hasChanged = JSON.stringify(oldValue) !== JSON.stringify(newValue);

          if (!hasChanged) {
            return null;
          }

          return (
            <div key={key} className="space-y-2 rounded-lg border p-4">
              <div className="font-mono text-xs font-semibold">{key}</div>
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <div className="mb-1 text-xs font-medium text-red-600 dark:text-red-400">
                    Old Value
                  </div>
                  <div className="rounded border border-red-200 bg-red-50/50 p-2 dark:border-red-900/30 dark:bg-red-900/10">
                    {renderValue(oldValue)}
                  </div>
                </div>
                <div>
                  <div className="mb-1 text-xs font-medium text-green-600 dark:text-green-400">
                    New Value
                  </div>
                  <div className="rounded border border-green-200 bg-green-50/50 p-2 dark:border-green-900/30 dark:bg-green-900/10">
                    {renderValue(newValue)}
                  </div>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    );
  };

  return (
    <DashboardLayout header="AuditLog">
      <PageShell
        title="Audit Log Details"
        breadcrumbs={[
          { label: 'Audit Logs', href: route('auditlog.index') },
          { label: `#${auditLog.id}` },
        ]}
        actions={
          <Link href={route('auditlog.index')}>
            <Button variant="outline">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to Audit Logs
            </Button>
          </Link>
        }
      >
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Overview</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <div className="text-sm font-medium text-muted-foreground">Event</div>
                  <div className="mt-1">{getEventBadge(auditLog.event)}</div>
                </div>
                <div>
                  <div className="text-sm font-medium text-muted-foreground">Date</div>
                  <div className="mt-1">{formatDate(auditLog.created_at, 'full')}</div>
                </div>
                <div>
                  <div className="text-sm font-medium text-muted-foreground">User</div>
                  <div className="mt-1">
                    {auditLog.user ? (
                      <div className="flex items-center gap-2">
                        <Avatar className="h-6 w-6">
                          {auditLog.user.avatar?.url ? (
                            <AvatarImage src={auditLog.user.avatar.url} alt={auditLog.user.name} />
                          ) : null}
                          <AvatarFallback className="text-xs">
                            {getInitials(auditLog.user.name)}
                          </AvatarFallback>
                        </Avatar>
                        <span>{auditLog.user.name}</span>
                      </div>
                    ) : (
                      <span className="text-muted-foreground">System</span>
                    )}
                  </div>
                </div>
                <div>
                  <div className="text-sm font-medium text-muted-foreground">Model</div>
                  <div className="mt-1 font-mono text-sm">
                    {getModelDisplayName(auditLog.auditable_type, auditLog.auditable_id)}
                  </div>
                </div>
                {auditLog.url && (
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">URL</div>
                    <div className="mt-1 break-all text-sm">{auditLog.url}</div>
                  </div>
                )}
                {auditLog.ip_address && (
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">IP Address</div>
                    <div className="mt-1 font-mono text-sm">{auditLog.ip_address}</div>
                  </div>
                )}
                {auditLog.user_agent && (
                  <div className="md:col-span-2">
                    <div className="text-sm font-medium text-muted-foreground">User Agent</div>
                    <div className="mt-1 break-all text-sm">{auditLog.user_agent}</div>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Changes</CardTitle>
            </CardHeader>
            <CardContent>{renderDiff()}</CardContent>
          </Card>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
