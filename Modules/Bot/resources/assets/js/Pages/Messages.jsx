/**
 * Bot Message Log Page
 *
 * Paginated table of all outbound/inbound messages for the current user.
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

import DataTable from '@/Components/DataDisplay/DataTable';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';

const STATUS_VARIANT = {
  delivered: 'default',
  failed: 'destructive',
  pending: 'secondary',
};

const DIRECTION_VARIANT = {
  outbound: 'outline-solid',
  inbound: 'secondary',
};

export default function Messages() {
  const { messages } = usePage().props;

  const columns = useMemo(
    () => [
      {
        accessorKey: 'platform',
        header: 'Platform',
        cell: ({ row }) => (
          <span className="font-medium">
            {row.original.platform ?? <span className="text-muted-foreground">—</span>}
            {row.original.driver && (
              <span className="ml-1.5 text-xs text-muted-foreground">({row.original.driver})</span>
            )}
          </span>
        ),
      },
      {
        accessorKey: 'direction',
        header: 'Direction',
        cell: ({ getValue }) => (
          <Badge variant={DIRECTION_VARIANT[getValue()] ?? 'outline-solid'}>{getValue()}</Badge>
        ),
      },
      {
        accessorKey: 'command_key',
        header: 'Command',
        cell: ({ getValue }) =>
          getValue() ? (
            <code className="text-xs bg-muted px-1 py-0.5 rounded">{getValue()}</code>
          ) : (
            <span className="text-muted-foreground text-xs">—</span>
          ),
      },
      {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => (
          <div>
            <Badge variant={STATUS_VARIANT[row.original.status] ?? 'secondary'}>
              {row.original.status}
            </Badge>
            {row.original.error_message && (
              <p className="text-xs text-destructive mt-0.5 max-w-xs truncate">
                {row.original.error_message}
              </p>
            )}
          </div>
        ),
      },
      {
        accessorKey: 'created_at',
        header: 'Sent At',
        cell: ({ getValue }) => (
          <span className="text-xs text-muted-foreground whitespace-nowrap">{getValue()}</span>
        ),
      },
    ],
    []
  );

  const { table } = useDatatable({
    data: messages?.data ?? [],
    columns,
    route: route('bot.messages.index'),
    pageCount: messages?.last_page ?? 1,
    serverSide: true,
  });

  return (
    <PageShell title="Bot Messages" description="Delivery log for all bot messages.">
      <DataTable table={table} title="Messages" description="Outbound and inbound bot messages." />
    </PageShell>
  );
}
