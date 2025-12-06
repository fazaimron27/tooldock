/**
 * Audit Log detail page displaying full audit log information
 * Shows old vs new values with diff visualization
 */
import { formatDate, getInitials } from '@/Utils/format';
import { Deferred, Link } from '@inertiajs/react';
import { ArrowLeft, Eye, EyeOff, Tag } from 'lucide-react';

import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

import DashboardLayout from '@/Layouts/DashboardLayout';

import { getEventBadge, getModelDisplayName, parseTags } from '../Utils/auditLogHelpers.jsx';
import { MYSQL_DATETIME_REGEX, isDateString } from '../Utils/datePatterns';

export default function Show({ auditLog, oldValues, newValues, formattedDiff, causerParams }) {
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

  const renderValue = (value) => {
    if (value === null || value === undefined) {
      return <span className="text-muted-foreground italic">null</span>;
    }

    if (value === '***REDACTED***') {
      return (
        <span className="inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
          <EyeOff className="h-3 w-3" />
          REDACTED
        </span>
      );
    }

    if (isDateString(value)) {
      try {
        /**
         * Format date strings (ISO, MySQL datetime, or date-only formats).
         * Converts MySQL datetime format (space) to ISO format (T) for parsing.
         */
        const dateString = MYSQL_DATETIME_REGEX.test(value) ? value.replace(' ', 'T') : value;
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
          return <span>{formatDate(date, 'full')}</span>;
        }
      } catch {
        // Falls back to string rendering below
      }
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

  /**
   * Render diff view based on event type.
   * Login events only have new values (no old values to compare).
   */
  const renderDiff = () => {
    if (auditLog.event === 'login') {
      const loginData = newValues || {};
      return (
        <div className="space-y-4">
          <div>
            <h4 className="mb-2 font-semibold text-indigo-600 dark:text-indigo-400">
              Login Details
            </h4>
            <div className="space-y-2 rounded-lg border border-indigo-200 bg-indigo-50/50 p-4 dark:border-indigo-900/30 dark:bg-indigo-900/10">
              {Object.entries(loginData).map(([key, value]) => (
                <div
                  key={key}
                  className="border-b border-indigo-200 pb-2 last:border-0 dark:border-indigo-900/30"
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

    if (auditLog.event === 'logout') {
      const logoutData = oldValues || {};
      return (
        <div className="space-y-4">
          <div>
            <h4 className="mb-2 font-semibold text-amber-600 dark:text-amber-400">
              Logout Details
            </h4>
            <div className="space-y-2 rounded-lg border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/30 dark:bg-amber-900/10">
              {Object.entries(logoutData).map(([key, value]) => (
                <div
                  key={key}
                  className="border-b border-amber-200 pb-2 last:border-0 dark:border-amber-900/30"
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

    if (auditLog.event === 'registered') {
      const registeredData = newValues || {};
      return (
        <div className="space-y-4">
          <div>
            <h4 className="mb-2 font-semibold text-emerald-600 dark:text-emerald-400">
              Registration Details
            </h4>
            <div className="space-y-2 rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-900/30 dark:bg-emerald-900/10">
              {Object.entries(registeredData).map(([key, value]) => (
                <div
                  key={key}
                  className="border-b border-emerald-200 pb-2 last:border-0 dark:border-emerald-900/30"
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

    if (auditLog.event === 'created') {
      return (
        <div className="space-y-4">
          <div>
            <h4 className="mb-2 font-semibold text-green-600 dark:text-green-400">New Values</h4>
            <div className="space-y-2 rounded-lg border border-green-200 bg-green-50/50 p-4 dark:border-green-900/30 dark:bg-green-900/10">
              {Object.entries(newValues || {}).map(([key, value]) => (
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

    /**
     * Events that display single data view instead of old/new comparison.
     */
    const authEvents = [
      'password_reset',
      'password_changed',
      'password_reset_requested',
      'email_verified',
      'email_changed',
      'account_deleted',
      'export',
      'file_uploaded',
      'file_deleted',
      'relationship_synced',
    ];

    if (authEvents.includes(auditLog.event)) {
      const eventData =
        auditLog.event === 'email_changed'
          ? { ...oldValues, ...newValues }
          : newValues || oldValues || {};

      const eventConfig = {
        password_reset: {
          label: 'Password Reset Details',
          headerClass: 'mb-2 font-semibold text-purple-600 dark:text-purple-400',
          containerClass:
            'space-y-2 rounded-lg border border-purple-200 bg-purple-50/50 p-4 dark:border-purple-900/30 dark:bg-purple-900/10',
          itemClass: 'border-b border-purple-200 pb-2 last:border-0 dark:border-purple-900/30',
        },
        password_changed: {
          label: 'Password Change Details',
          headerClass: 'mb-2 font-semibold text-violet-600 dark:text-violet-400',
          containerClass:
            'space-y-2 rounded-lg border border-violet-200 bg-violet-50/50 p-4 dark:border-violet-900/30 dark:bg-violet-900/10',
          itemClass: 'border-b border-violet-200 pb-2 last:border-0 dark:border-violet-900/30',
        },
        password_reset_requested: {
          label: 'Password Reset Request Details',
          headerClass: 'mb-2 font-semibold text-fuchsia-600 dark:text-fuchsia-400',
          containerClass:
            'space-y-2 rounded-lg border border-fuchsia-200 bg-fuchsia-50/50 p-4 dark:border-fuchsia-900/30 dark:bg-fuchsia-900/10',
          itemClass: 'border-b border-fuchsia-200 pb-2 last:border-0 dark:border-fuchsia-900/30',
        },
        email_verified: {
          label: 'Email Verification Details',
          headerClass: 'mb-2 font-semibold text-teal-600 dark:text-teal-400',
          containerClass:
            'space-y-2 rounded-lg border border-teal-200 bg-teal-50/50 p-4 dark:border-teal-900/30 dark:bg-teal-900/10',
          itemClass: 'border-b border-teal-200 pb-2 last:border-0 dark:border-teal-900/30',
        },
        email_changed: {
          label: 'Email Change Details',
          headerClass: 'mb-2 font-semibold text-cyan-600 dark:text-cyan-400',
          containerClass:
            'space-y-2 rounded-lg border border-cyan-200 bg-cyan-50/50 p-4 dark:border-cyan-900/30 dark:bg-cyan-900/10',
          itemClass: 'border-b border-cyan-200 pb-2 last:border-0 dark:border-cyan-900/30',
        },
        account_deleted: {
          label: 'Account Deletion Details',
          headerClass: 'mb-2 font-semibold text-rose-600 dark:text-rose-400',
          containerClass:
            'space-y-2 rounded-lg border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-900/30 dark:bg-rose-900/10',
          itemClass: 'border-b border-rose-200 pb-2 last:border-0 dark:border-rose-900/30',
        },
        export: {
          label: 'Export Details',
          headerClass: 'mb-2 font-semibold text-slate-600 dark:text-slate-400',
          containerClass:
            'space-y-2 rounded-lg border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-900/30 dark:bg-slate-900/10',
          itemClass: 'border-b border-slate-200 pb-2 last:border-0 dark:border-slate-900/30',
        },
        file_uploaded: {
          label: 'File Upload Details',
          headerClass: 'mb-2 font-semibold text-lime-600 dark:text-lime-400',
          containerClass:
            'space-y-2 rounded-lg border border-lime-200 bg-lime-50/50 p-4 dark:border-lime-900/30 dark:bg-lime-900/10',
          itemClass: 'border-b border-lime-200 pb-2 last:border-0 dark:border-lime-900/30',
        },
        file_deleted: {
          label: 'File Deletion Details',
          headerClass: 'mb-2 font-semibold text-orange-600 dark:text-orange-400',
          containerClass:
            'space-y-2 rounded-lg border border-orange-200 bg-orange-50/50 p-4 dark:border-orange-900/30 dark:bg-orange-900/10',
          itemClass: 'border-b border-orange-200 pb-2 last:border-0 dark:border-orange-900/30',
        },
        relationship_synced: {
          label: 'Relationship Sync Details',
          headerClass: 'mb-2 font-semibold text-pink-600 dark:text-pink-400',
          containerClass:
            'space-y-2 rounded-lg border border-pink-200 bg-pink-50/50 p-4 dark:border-pink-900/30 dark:bg-pink-900/10',
          itemClass: 'border-b border-pink-200 pb-2 last:border-0 dark:border-pink-900/30',
        },
      };

      const config = eventConfig[auditLog.event] || eventConfig.export;

      return (
        <div className="space-y-4">
          <div>
            <h4 className={config.headerClass}>{config.label}</h4>
            <div className={config.containerClass}>
              {Object.entries(eventData).map(([key, value]) => (
                <div key={key} className={config.itemClass}>
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
              {Object.entries(oldValues || {}).map(([key, value]) => (
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
     * Render side-by-side comparison for updated events.
     * Only display fields that actually changed.
     */
    const oldVals = oldValues || {};
    const newVals = newValues || {};
    const allKeys = new Set([...Object.keys(oldVals), ...Object.keys(newVals)]);

    return (
      <div className="space-y-4">
        {Array.from(allKeys).map((key) => {
          const oldValue = oldVals[key];
          const newValue = newVals[key];
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
                  <div className="text-sm font-medium text-muted-foreground">Causer</div>
                  <div className="mt-1">
                    <Deferred
                      data={['causerParams']}
                      fallback={<span className="text-muted-foreground">Loading...</span>}
                    >
                      {auditLog.user ? (
                        <div className="flex items-center gap-2">
                          <Avatar className="h-6 w-6">
                            {auditLog.user.avatar?.url ? (
                              <AvatarImage
                                src={auditLog.user.avatar.url}
                                alt={auditLog.user.name}
                              />
                            ) : null}
                            <AvatarFallback className="text-xs">
                              {getInitials(auditLog.user.name)}
                            </AvatarFallback>
                          </Avatar>
                          <span>{causerParams || auditLog.user.name}</span>
                        </div>
                      ) : (
                        <span className="text-muted-foreground">{causerParams || 'System'}</span>
                      )}
                    </Deferred>
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
                {auditLog.tags && (
                  <div className="md:col-span-2">
                    <div className="text-sm font-medium text-muted-foreground">Tags</div>
                    <div className="mt-1 flex flex-wrap gap-1">
                      {parseTags(auditLog.tags).map((tag, index) => (
                        <Badge key={index} variant="outline" className="bg-muted/50 hover:bg-muted">
                          <Tag className="mr-1 h-3 w-3" />
                          {tag}
                        </Badge>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          {formattedDiff && formattedDiff.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Eye className="h-5 w-5" />
                  Summary of Changes
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  {formattedDiff.map((change, index) => (
                    <div
                      key={index}
                      className="rounded-lg border-l-4 border-primary bg-muted/30 p-3 text-sm"
                    >
                      {change}
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle>Detailed Changes</CardTitle>
            </CardHeader>
            <CardContent>
              <Deferred
                data={['oldValues', 'newValues']}
                fallback={
                  <div className="flex items-center justify-center py-8">
                    <div className="text-muted-foreground">Loading changes...</div>
                  </div>
                }
              >
                {renderDiff()}
              </Deferred>
            </CardContent>
          </Card>
        </div>
      </PageShell>
    </DashboardLayout>
  );
}
