/**
 * Transaction Report Component
 * Displays transaction ledger with filtering and export capabilities
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { cn } from '@/Utils/utils';
import EmptyState from '@Treasury/Components/EmptyState';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import {
  ArrowDownCircle,
  ArrowRightCircle,
  ArrowUpCircle,
  Calendar,
  FileText,
  TrendingDown,
  TrendingUp,
} from 'lucide-react';
import { useMemo } from 'react';

import DataTable from '@/Components/DataDisplay/DataTable';
import { Card, CardContent } from '@/Components/ui/card';

const typeConfig = {
  income: { icon: ArrowUpCircle, color: 'text-emerald-500', bgColor: 'bg-emerald-500/10' },
  expense: { icon: ArrowDownCircle, color: 'text-rose-500', bgColor: 'bg-rose-500/10' },
  transfer: { icon: ArrowRightCircle, color: 'text-blue-500', bgColor: 'bg-blue-500/10' },
};

export default function TransactionReport({
  transactions,
  summary,
  formatCurrency,
  formatDate,
  referenceCurrency,
}) {
  const columns = useMemo(
    () => [
      {
        accessorKey: 'date',
        header: 'Date',
        cell: (info) => {
          const tx = info.row.original;
          return (
            <div className="flex items-center gap-2 text-sm">
              <Calendar className="w-4 h-4 text-muted-foreground" />
              {formatDate(new Date(tx.date))}
            </div>
          );
        },
      },
      {
        accessorKey: 'type',
        header: 'Type',
        cell: (info) => {
          const tx = info.row.original;
          const config = typeConfig[tx.type] || typeConfig.expense;
          const TypeIcon = config.icon;
          return (
            <div
              className={cn(
                'inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium',
                config.bgColor,
                config.color
              )}
            >
              <TypeIcon className="w-3.5 h-3.5" />
              {tx.type.charAt(0).toUpperCase() + tx.type.slice(1)}
            </div>
          );
        },
      },
      {
        accessorKey: 'category',
        header: 'Category',
        cell: (info) => {
          const tx = info.row.original;
          if (!tx.category) return <span className="text-muted-foreground">-</span>;
          return (
            <div className="flex items-center gap-2">
              <div
                className="w-2.5 h-2.5 rounded-full"
                style={{ backgroundColor: tx.category.color }}
              />
              <span className="truncate max-w-[120px]">{tx.category.name}</span>
            </div>
          );
        },
      },
      {
        accessorKey: 'description',
        header: 'Description',
        cell: (info) => {
          const tx = info.row.original;
          return (
            <span className="truncate max-w-[200px] block text-sm">
              {tx.description || tx.notes || '-'}
            </span>
          );
        },
      },
      {
        accessorKey: 'wallet',
        header: 'Wallet',
        cell: (info) => {
          const tx = info.row.original;
          if (tx.type === 'transfer' && tx.destination_wallet) {
            return (
              <div className="flex items-center gap-1 text-sm">
                <span>{tx.wallet?.name}</span>
                <ArrowRightCircle className="w-3 h-3 text-blue-500" />
                <span>{tx.destination_wallet.name}</span>
              </div>
            );
          }
          return <span className="text-sm">{tx.wallet?.name || '-'}</span>;
        },
      },
      {
        accessorKey: 'amount',
        header: 'Amount',
        cell: (info) => {
          const tx = info.row.original;
          return (
            <span
              className={cn(
                'font-mono font-semibold text-sm',
                tx.type === 'income' && 'text-emerald-600',
                tx.type === 'expense' && 'text-rose-600',
                tx.type === 'transfer' && 'text-blue-600'
              )}
            >
              {tx.type === 'income' ? '+' : tx.type === 'expense' ? '-' : ''}
              {formatCurrency(tx.amount, tx.wallet?.currency)}
            </span>
          );
        },
      },
    ],
    [formatCurrency, formatDate]
  );

  const { tableProps } = useDatatable({
    data: transactions || [],
    columns,
    serverSide: false,
    pageSize: 25,
    initialSorting: [{ id: 'date', desc: true }],
  });

  return (
    <>
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <QuickStatCard
          title="Total Income"
          value={formatCurrency(summary?.income || 0, referenceCurrency)}
          icon={TrendingUp}
          color="green"
        />
        <QuickStatCard
          title="Total Expenses"
          value={formatCurrency(summary?.expense || 0, referenceCurrency)}
          icon={TrendingDown}
          color="red"
        />
        <QuickStatCard
          title="Net Flow"
          value={formatCurrency(summary?.net || 0, referenceCurrency)}
          icon={summary?.net >= 0 ? TrendingUp : TrendingDown}
          color={summary?.net >= 0 ? 'green' : 'red'}
        />
        <QuickStatCard
          title="Transactions"
          value={summary?.count || 0}
          icon={FileText}
          color="blue"
        />
      </div>

      {transactions && transactions.length > 0 ? (
        <DataTable {...tableProps} title="Transactions" showCard={true} />
      ) : (
        <Card>
          <CardContent className="p-8">
            <EmptyState icon={FileText} message="No transactions found for the selected period." />
          </CardContent>
        </Card>
      )}
    </>
  );
}
