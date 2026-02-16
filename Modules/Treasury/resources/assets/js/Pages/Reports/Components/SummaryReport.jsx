/**
 * Summary Report Component
 * Displays monthly/yearly financial summaries with savings rates
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { cn } from '@/Utils/utils';
import EmptyState from '@Treasury/Components/EmptyState';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import { BarChart3, Percent, Target, TrendingDown, TrendingUp } from 'lucide-react';
import { useMemo } from 'react';

import DataTable from '@/Components/DataDisplay/DataTable';
import { Card, CardContent } from '@/Components/ui/card';

export default function SummaryReport({ data, totals, formatCurrency, referenceCurrency }) {
  const columns = useMemo(
    () => [
      {
        accessorKey: 'period',
        header: 'Period',
        cell: (info) => <span className="font-medium">{info.row.original.period}</span>,
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
        accessorKey: 'savings_rate',
        header: 'Savings Rate',
        cell: (info) => {
          const rate = info.row.original.savings_rate;
          return <span className={cn(rate >= 0 ? 'text-blue-600' : 'text-rose-600')}>{rate}%</span>;
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
      <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
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
        <QuickStatCard
          title="Avg Savings Rate"
          value={`${totals?.average_savings_rate?.toFixed(1) || 0}%`}
          icon={Percent}
          color="blue"
        />
      </div>

      {data && data.length > 0 ? (
        <DataTable {...tableProps} title="Period Summary" showCard={true} />
      ) : (
        <Card>
          <CardContent className="p-8">
            <EmptyState icon={BarChart3} message="No summary data found for the selected period." />
          </CardContent>
        </Card>
      )}
    </>
  );
}
