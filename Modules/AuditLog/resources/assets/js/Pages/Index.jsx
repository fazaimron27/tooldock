/**
 * Audit Logs listing page with server-side pagination, sorting, search, and filters
 * Displays audit logs with user avatars, event badges, model information, and time ago dates
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { formatDate, getInitials } from '@/Utils/format';
import { Link, router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { Download, Filter, X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import DataTable from '@/Components/DataDisplay/DataTable';
import DatePicker from '@/Components/Form/DatePicker';
import PageShell from '@/Components/Layouts/PageShell';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({
  auditLogs,
  users,
  modelTypes = [],
  eventTypes = [],
  defaultPerPage = 20,
  filters = {},
}) {
  const [showFilters, setShowFilters] = useState(false);
  const [localFilters, setLocalFilters] = useState({
    user_id: filters.user_id || '',
    system: filters.system || '',
    event: filters.event || '',
    auditable_type: filters.auditable_type || '',
    start_date: filters.start_date || '',
    end_date: filters.end_date || '',
  });

  const handleFilterChange = useCallback((key, value) => {
    setLocalFilters((prev) => {
      const newFilters = { ...prev, [key]: value };

      if (key === 'user_id' && value) {
        newFilters.system = '';
      }

      const params = {
        ...newFilters,
        page: 1,
      };

      Object.keys(params).forEach((k) => {
        if (params[k] === '' || params[k] === null || params[k] === undefined) {
          delete params[k];
        }
      });

      router.get(route('auditlog.index'), params, {
        preserveState: true,
        preserveScroll: true,
        skipLoadingIndicator: true,
      });

      return newFilters;
    });
  }, []);

  const clearFilters = useCallback(() => {
    setLocalFilters({
      user_id: '',
      system: '',
      event: '',
      auditable_type: '',
      start_date: '',
      end_date: '',
    });
    router.get(
      route('auditlog.index'),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        skipLoadingIndicator: true,
      }
    );
  }, []);

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

  const columns = useMemo(
    () => [
      {
        accessorKey: 'user',
        header: 'User',
        cell: (info) => {
          const auditLog = info.row.original;
          const user = auditLog.user;

          if (!user) {
            return (
              <div className="flex items-center gap-2">
                <Avatar className="h-8 w-8">
                  <AvatarFallback className="text-xs bg-muted">SY</AvatarFallback>
                </Avatar>
                <span className="text-muted-foreground text-sm">System</span>
              </div>
            );
          }

          const initials = getInitials(user.name);

          return (
            <div className="flex items-center gap-2">
              <Avatar className="h-8 w-8">
                {user.avatar?.url ? <AvatarImage src={user.avatar.url} alt={user.name} /> : null}
                <AvatarFallback className="text-xs">{initials}</AvatarFallback>
              </Avatar>
              <span className="font-medium">{user.name}</span>
            </div>
          );
        },
      },
      {
        accessorKey: 'event',
        header: 'Event',
        cell: (info) => {
          const auditLog = info.row.original;
          return getEventBadge(auditLog.event);
        },
      },
      {
        id: 'auditable',
        header: 'Module/Model',
        cell: (info) => {
          const auditLog = info.row.original;
          return (
            <Link
              href={route('auditlog.show', { auditLog: auditLog.id })}
              className="font-medium hover:underline"
            >
              {getModelDisplayName(auditLog.auditable_type, auditLog.auditable_id)}
            </Link>
          );
        },
      },
      {
        accessorKey: 'created_at',
        header: 'Date',
        cell: (info) => {
          const auditLog = info.row.original;
          return (
            <span className="text-sm text-muted-foreground">
              {formatDate(auditLog.created_at, 'relative')}
            </span>
          );
        },
      },
    ],
    []
  );

  const pageCount = useMemo(() => {
    if (auditLogs.total !== undefined && auditLogs.per_page) {
      return Math.ceil(auditLogs.total / auditLogs.per_page);
    }
    return undefined;
  }, [auditLogs.total, auditLogs.per_page]);

  const { tableProps } = useDatatable({
    data: auditLogs.data || [],
    columns,
    route: route('auditlog.index'),
    serverSide: true,
    pageSize: auditLogs.per_page || defaultPerPage,
    initialSorting: [{ id: 'created_at', desc: true }],
    pageCount: pageCount,
    only: ['auditLogs'],
    initialFilters: localFilters,
  });

  useEffect(() => {
    if (!tableProps.table || auditLogs.current_page === undefined) {
      return;
    }

    const currentPageIndex = auditLogs.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = auditLogs.per_page || defaultPerPage;

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
  }, [tableProps.table, auditLogs.current_page, auditLogs.per_page, defaultPerPage]);

  const hasActiveFilters = useMemo(() => {
    return Object.values(localFilters).some((value) => value !== '' && value !== null);
  }, [localFilters]);

  const hasInvalidDateRange = useMemo(() => {
    return (
      localFilters.start_date &&
      localFilters.end_date &&
      localFilters.start_date > localFilters.end_date
    );
  }, [localFilters.start_date, localFilters.end_date]);

  return (
    <DashboardLayout header="AuditLog">
      <PageShell
        title="Audit Logs"
        actions={
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              onClick={() => {
                const queryParams = [];
                Object.entries(localFilters).forEach(([key, value]) => {
                  if (value && value !== '') {
                    queryParams.push(`${encodeURIComponent(key)}=${encodeURIComponent(value)}`);
                  }
                });
                const queryString = queryParams.length > 0 ? `?${queryParams.join('&')}` : '';
                window.location.href = route('auditlog.export') + queryString;
              }}
            >
              <Download className="mr-2 h-4 w-4" />
              Export CSV
            </Button>
            <Button
              variant={showFilters ? 'default' : 'outline'}
              onClick={() => setShowFilters(!showFilters)}
            >
              <Filter className="mr-2 h-4 w-4" />
              Filters
              {hasActiveFilters && <span className="ml-2 h-2 w-2 rounded-full bg-primary"></span>}
            </Button>
          </div>
        }
      >
        <AnimatePresence>
          {showFilters && (
            <motion.div
              className="mb-6 rounded-lg border bg-card p-4 overflow-hidden"
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              transition={{ duration: 0.2, ease: [0.4, 0, 0.2, 1] }}
            >
              <div className="mb-4 flex items-center justify-between">
                <h3 className="text-lg font-semibold">Filters</h3>
                {hasActiveFilters && (
                  <Button variant="ghost" size="sm" onClick={clearFilters}>
                    <X className="mr-2 h-4 w-4" />
                    Clear Filters
                  </Button>
                )}
              </div>
              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                <div className="space-y-2">
                  <Label htmlFor="user_id">User</Label>
                  <select
                    id="user_id"
                    value={localFilters.user_id}
                    onChange={(e) => handleFilterChange('user_id', e.target.value)}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                  >
                    <option value="">All Users</option>
                    {users.map((user) => (
                      <option key={user.id} value={user.id}>
                        {user.name} ({user.email})
                      </option>
                    ))}
                  </select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="system">System</Label>
                  <select
                    id="system"
                    value={localFilters.system}
                    onChange={(e) => handleFilterChange('system', e.target.value)}
                    disabled={!!localFilters.user_id}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                    title={
                      localFilters.user_id
                        ? 'System filter is disabled when a specific user is selected'
                        : ''
                    }
                  >
                    <option value="">All Actions</option>
                    <option value="user">User Actions</option>
                    <option value="system">System Actions</option>
                  </select>
                  {localFilters.user_id && (
                    <p className="text-xs text-muted-foreground">Disabled when filtering by user</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="event">Event Type</Label>
                  <select
                    id="event"
                    value={localFilters.event}
                    onChange={(e) => handleFilterChange('event', e.target.value)}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                  >
                    <option value="">All Events</option>
                    {eventTypes.map((eventType) => (
                      <option key={eventType.value} value={eventType.value}>
                        {eventType.label}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="auditable_type">Model Type</Label>
                  <select
                    id="auditable_type"
                    value={localFilters.auditable_type}
                    onChange={(e) => handleFilterChange('auditable_type', e.target.value)}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                  >
                    <option value="">All Models</option>
                    {modelTypes.map((type) => (
                      <option key={type.value} value={type.value}>
                        {type.label}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="start_date">Start Date</Label>
                  <DatePicker
                    id="start_date"
                    value={localFilters.start_date}
                    onChange={(date) => handleFilterChange('start_date', date || '')}
                    placeholder="Pick start date"
                  />
                  {hasInvalidDateRange && (
                    <p className="text-xs text-destructive">Start date must be before end date</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="end_date">End Date</Label>
                  <DatePicker
                    id="end_date"
                    value={localFilters.end_date}
                    onChange={(date) => handleFilterChange('end_date', date || '')}
                    placeholder="Pick end date"
                  />
                  {hasInvalidDateRange && (
                    <p className="text-xs text-destructive">End date must be after start date</p>
                  )}
                </div>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        <DataTable
          {...tableProps}
          title="Audit Logs"
          description="A comprehensive log of all changes made across the system"
          showCard={true}
        />
      </PageShell>
    </DashboardLayout>
  );
}
