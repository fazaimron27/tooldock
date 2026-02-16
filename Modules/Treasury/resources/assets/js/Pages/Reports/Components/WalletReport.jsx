/**
 * Wallet Report Component
 * Displays per-wallet balances and transaction summaries
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { cn } from '@/Utils/utils';
import EmptyState from '@Treasury/Components/EmptyState';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import { Target, TrendingDown, TrendingUp, Wallet } from 'lucide-react';
import { useMemo } from 'react';

import DataTable from '@/Components/DataDisplay/DataTable';
import { Card, CardContent } from '@/Components/ui/card';

export default function WalletReport({ data, totals, formatCurrency, referenceCurrency }) {
  const columns = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: 'Wallet',
        cell: (info) => <span className="font-medium">{info.row.original.name}</span>,
      },
      {
        accessorKey: 'current_balance',
        header: 'Balance',
        cell: (info) =>
          formatCurrency(info.row.original.current_balance, info.row.original.currency),
      },
      {
        accessorKey: 'income',
        header: 'Income',
        cell: (info) => (
          <span className="text-emerald-600">
            {formatCurrency(info.row.original.income, info.row.original.currency)}
          </span>
        ),
      },
      {
        accessorKey: 'expense',
        header: 'Expense',
        cell: (info) => (
          <span className="text-rose-600">
            {formatCurrency(info.row.original.expense, info.row.original.currency)}
          </span>
        ),
      },
      {
        accessorKey: 'savings',
        header: 'Savings',
        cell: (info) => {
          const savings = info.row.original.savings || 0;
          return savings > 0 ? (
            <span className="text-blue-600">
              {formatCurrency(savings, info.row.original.currency)}
            </span>
          ) : (
            <span className="text-muted-foreground">-</span>
          );
        },
      },
      {
        accessorKey: 'net_change',
        header: 'Net Change',
        cell: (info) => {
          const net = info.row.original.net_change;
          return (
            <span className={cn(net >= 0 ? 'text-emerald-600' : 'text-rose-600', 'font-medium')}>
              {formatCurrency(net, info.row.original.currency)}
            </span>
          );
        },
      },
      {
        accessorKey: 'transaction_count',
        header: 'Transactions',
        cell: (info) => info.row.original.transaction_count,
      },
    ],
    [formatCurrency]
  );

  const { tableProps } = useDatatable({
    data: data || [],
    columns,
    serverSide: false,
    pageSize: 20,
  });

  return (
    <>
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <QuickStatCard
          title="Total Income"
          value={formatCurrency(totals?.income || 0, referenceCurrency)}
          icon={TrendingUp}
          color="green"
        />
        <QuickStatCard
          title="Total Expenses"
          value={formatCurrency(totals?.expense || 0, referenceCurrency)}
          icon={TrendingDown}
          color="red"
        />
        <QuickStatCard
          title="Total Savings"
          value={formatCurrency(totals?.savings || 0, referenceCurrency)}
          icon={Target}
          color="blue"
        />
        <QuickStatCard
          title="Net Change"
          value={formatCurrency(totals?.net_change || 0, referenceCurrency)}
          icon={totals?.net_change >= 0 ? TrendingUp : TrendingDown}
          color={totals?.net_change >= 0 ? 'green' : 'red'}
        />
      </div>

      {data && data.length > 0 ? (
        <DataTable {...tableProps} title="Wallet Summary" showCard={true} />
      ) : (
        <Card>
          <CardContent className="p-8">
            <EmptyState icon={Wallet} message="No wallet data found." />
          </CardContent>
        </Card>
      )}
    </>
  );
}
