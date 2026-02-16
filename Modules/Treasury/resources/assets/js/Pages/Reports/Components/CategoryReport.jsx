/**
 * Category Report Component
 * Displays income/expense/savings breakdown by category
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { cn } from '@/Utils/utils';
import EmptyState from '@Treasury/Components/EmptyState';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import { BarChart3, Target, TrendingDown, TrendingUp } from 'lucide-react';
import { useMemo } from 'react';

import DataTable from '@/Components/DataDisplay/DataTable';
import { Card, CardContent } from '@/Components/ui/card';

export default function CategoryReport({ data, totals, formatCurrency, referenceCurrency }) {
  const columns = useMemo(
    () => [
      {
        accessorKey: 'category',
        header: 'Category',
        cell: (info) => {
          const item = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <div
                className="w-3 h-3 rounded-full"
                style={{ backgroundColor: item.category?.color }}
              />
              <span className="font-medium">{item.category?.name || 'Uncategorized'}</span>
            </div>
          );
        },
      },
      {
        accessorKey: 'income',
        header: 'Income',
        cell: (info) => (
          <span className="text-emerald-600">
            {formatCurrency(info.row.original.income, referenceCurrency)}
          </span>
        ),
      },
      {
        accessorKey: 'expense',
        header: 'Expense',
        cell: (info) => (
          <span className="text-rose-600">
            {formatCurrency(info.row.original.expense, referenceCurrency)}
          </span>
        ),
      },
      {
        accessorKey: 'savings',
        header: 'Savings',
        cell: (info) => {
          const savings = info.row.original.savings || 0;
          return savings > 0 ? (
            <span className="text-blue-600">{formatCurrency(savings, referenceCurrency)}</span>
          ) : (
            <span className="text-muted-foreground">-</span>
          );
        },
      },
      {
        accessorKey: 'net',
        header: 'Net',
        cell: (info) => {
          const net = info.row.original.net;
          return (
            <span className={cn(net >= 0 ? 'text-emerald-600' : 'text-rose-600', 'font-medium')}>
              {formatCurrency(net, referenceCurrency)}
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
    [formatCurrency, referenceCurrency]
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
          title="Net"
          value={formatCurrency(totals?.net || 0, referenceCurrency)}
          icon={totals?.net >= 0 ? TrendingUp : TrendingDown}
          color={totals?.net >= 0 ? 'green' : 'red'}
        />
      </div>

      {data && data.length > 0 ? (
        <DataTable {...tableProps} title="Category Breakdown" showCard={true} />
      ) : (
        <Card>
          <CardContent className="p-8">
            <EmptyState
              icon={BarChart3}
              message="No category data found for the selected period."
            />
          </CardContent>
        </Card>
      )}
    </>
  );
}
